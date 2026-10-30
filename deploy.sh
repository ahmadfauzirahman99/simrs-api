#!/usr/bin/env bash
#
# deploy.sh — Deploy SIMRS Gateway (Laravel 12 + Inertia) ke VPS pribadi
#             https://haurakirana.my.id  (Docker, di balik nginx + Cloudflare)
#
# Cara pakai:
#   ./deploy.sh --check     Cek koneksi + status server (tanpa mengubah apa pun)
#   ./deploy.sh --dry-run   Cek + build aset lokal + tampilkan file yang akan di-upload
#   ./deploy.sh             Deploy sungguhan
#
# Yang dilakukan saat deploy:
#   1. Tes lokal (php artisan test) — deploy batal bila ada yang gagal
#   2. Build aset frontend (npm run build)
#   3. Backup database + storage di server  -> ~/simrs-api/backups/
#   4. Upload source (tanpa .env, vendor, node_modules, file sampel)
#   5. Build image baru; image lama disimpan sebagai simrs-api:previous
#   6. Health check /up — bila gagal, otomatis kembali ke image lama
#   7. (Sekali saja) arahkan nginx domain utama ke container
#
# Data di server TIDAK disentuh deploy: ~/simrs-api/.env dan ~/simrs-api/data/.
# Password admin pertama tersimpan di ~/simrs-api/admin-password.txt (chmod 600).
#
# JANGAN menaruh password / private key di file ini.

set -euo pipefail

# ============================ KONFIGURASI ============================
# Nilai server (IP, user, key, domain) ada di deploy/deploy.env — TIDAK di-commit,
# karena repo ini publik dan IP origin sengaja disembunyikan di balik Cloudflare.
# Salin deploy/deploy.env.example -> deploy/deploy.env lalu isi.
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
[ -f "$ROOT/deploy/deploy.env" ] || { echo "deploy/deploy.env belum ada — salin dari deploy/deploy.env.example" >&2; exit 1; }
# shellcheck source=/dev/null
source "$ROOT/deploy/deploy.env"
: "${SSH_HOST:?}" "${SSH_USER:?}" "${SSH_KEY:?}" "${DOMAIN:?}"
REMOTE_DIR="${REMOTE_DIR:-simrs-api}"   # relatif ke home user di server
LOCAL_PORT="${LOCAL_PORT:-8090}"        # container hanya bind ke 127.0.0.1
KEEP_BACKUPS="${KEEP_BACKUPS:-10}"
# =====================================================================

MODE="${1:-}"
# Satu koneksi SSH dipakai ulang (multiplexing): server membatasi SSH (ufw LIMIT,
# maks 6 koneksi baru / 30 detik), jadi jangan buka koneksi baru di tiap langkah.
SSH_OPTS=(-i "$SSH_KEY" -o BatchMode=yes -o ConnectTimeout=15
          -o ControlMaster=auto -o "ControlPath=/tmp/simrs-deploy-%C" -o ControlPersist=120)
SSH_TARGET="$SSH_USER@$SSH_HOST"

log()  { printf '\033[1;36m[deploy]\033[0m %s\n' "$*"; }
ok()   { printf '\033[1;32m[ok]\033[0m %s\n' "$*"; }
warn() { printf '\033[1;33m[warn]\033[0m %s\n' "$*"; }
die()  { printf '\033[1;31m[error]\033[0m %s\n' "$*" >&2; exit 1; }

remote() { ssh "${SSH_OPTS[@]}" "$SSH_TARGET" "$@"; }

RSYNC_EXCLUDES=(
  --exclude=.env --exclude=.git --exclude=node_modules --exclude=vendor --exclude=dist
  --exclude=tests --exclude=deploy --exclude=deploy.sh --exclude=scripts
  --exclude='sampling*.json' --exclude='bundle-*.json' --exclude=satusehat-laravel-skill
  --exclude=.DS_Store --exclude=.phpunit.result.cache --exclude='database/*.sqlite'
  --exclude='storage/logs/*' --exclude='storage/framework/*/*'
  --exclude='storage/app/private/*' --exclude='storage/app/public/payment_proofs'
  --exclude=public/hot --exclude=public/storage
)

# --- 1. Cek koneksi & status server (read-only) -----------------------
check_server() {
  log "Cek koneksi SSH ..."
  remote 'echo connected' >/dev/null || die "Tidak bisa login ke $SSH_TARGET dengan key $SSH_KEY"

  log "Status server:"
  remote "cd ~/$REMOTE_DIR 2>/dev/null && docker compose ps --format '{{.Name}}  {{.Status}}' || echo 'belum pernah deploy'; \
          df -h / | tail -1; ls -1t ~/$REMOTE_DIR/backups 2>/dev/null | head -3" || true

  log "Cek publik:"
  for u in "https://$DOMAIN/" "https://$DOMAIN/up"; do
    printf '  %-40s %s\n' "$u" "$(curl -s -o /dev/null -m 15 -w '%{http_code}' "$u")"
  done
}

# --- 2. Tes & build lokal ---------------------------------------------
build_local() {
  log "Jalankan tes (php artisan test) ..."
  (cd "$ROOT" && php artisan test | tail -3) || die "Tes gagal — deploy dibatalkan."
  log "Build aset frontend (npm run build) ..."
  (cd "$ROOT" && npm run build >/dev/null)
}

# --- 3. Backup data server --------------------------------------------
backup_remote() {
  log "Backup database + storage di server ..."
  remote "bash -s" <<EOF
set -e
cd ~/$REMOTE_DIR 2>/dev/null || exit 0
[ -d data ] || exit 0
mkdir -p backups
STAMP=\$(date +%Y%m%d-%H%M%S)
if docker ps --format '{{.Names}}' | grep -qx simrs-api; then
  # Salinan SQLite yang konsisten walau aplikasi sedang jalan
  docker exec simrs-api php -r '\$db=new PDO("sqlite:/var/www/html/database/data/database.sqlite"); \$db->exec("VACUUM INTO \"/var/www/html/database/data/snapshot.sqlite\"");'
fi
sudo tar -czf backups/data-\$STAMP.tar.gz data
sudo rm -f data/database/snapshot.sqlite
sudo chown $SSH_USER: backups/data-\$STAMP.tar.gz
chmod 700 backups; chmod 600 backups/*.tar.gz
ls -1t backups/data-*.tar.gz | tail -n +$((KEEP_BACKUPS + 1)) | xargs -r rm -f
echo "  -> ~/$REMOTE_DIR/backups/data-\$STAMP.tar.gz"
EOF
}

# --- 4. Upload source -------------------------------------------------
upload() {
  if [[ "$MODE" == "--dry-run" ]]; then
    log "File yang akan berubah di server (dry-run, maks 50 baris):"
    rsync -az --delete --dry-run --itemize-changes "${RSYNC_EXCLUDES[@]}" \
      -e "ssh ${SSH_OPTS[*]}" "$ROOT/" "$SSH_TARGET:$REMOTE_DIR/src/" 2>/dev/null | head -50 || true
    return
  fi
  log "Upload source -> ~/$REMOTE_DIR/src ..."
  remote "mkdir -p ~/$REMOTE_DIR/src"
  rsync -az --delete "${RSYNC_EXCLUDES[@]}" \
    -e "ssh ${SSH_OPTS[*]}" "$ROOT/" "$SSH_TARGET:$REMOTE_DIR/src/"
  rsync -az -e "ssh ${SSH_OPTS[*]}" "$ROOT/deploy/Dockerfile" "$ROOT/deploy/docker-compose.yml" "$SSH_TARGET:$REMOTE_DIR/"
}

# --- 5. Build, jalankan, health check, rollback -----------------------
release() {
  log "Build image & jalankan container ..."
  remote "bash -s" <<EOF
set -euo pipefail
cd ~/$REMOTE_DIR

if [ ! -f .env ]; then
  echo "  .env belum ada — membuat .env produksi"
  # TRUSTED_PROXIES=* aman di sini: container hanya bind 127.0.0.1 dan nginx host selalu
  # menimpa X-Forwarded-For/Proto, jadi header tidak bisa dipalsukan pengunjung.
  cp src/.env.example .env
  sed -i \\
    -e 's|^APP_NAME=.*|APP_NAME="SIMRS Gateway"|' \\
    -e 's|^APP_ENV=.*|APP_ENV=production|' \\
    -e 's|^APP_DEBUG=.*|APP_DEBUG=false|' \\
    -e 's|^APP_URL=.*|APP_URL=https://$DOMAIN|' \\
    -e 's|^APP_LOCALE=.*|APP_LOCALE=id|' \\
    -e 's|^APP_TIMEZONE=.*|APP_TIMEZONE=Asia/Jakarta|' \\
    -e 's|^LOG_STACK=.*|LOG_STACK=daily|' \\
    -e 's|^LOG_LEVEL=.*|LOG_LEVEL=warning|' \\
    -e 's|^SESSION_ENCRYPT=.*|SESSION_ENCRYPT=true|' \\
    -e 's|^TRUSTED_PROXIES=.*|TRUSTED_PROXIES=*|' \\
    -e '/^APP_KEY=/d' \\
    .env
  printf 'APP_KEY=base64:%s\nDB_DATABASE=/var/www/html/database/data/database.sqlite\nSESSION_SECURE_COOKIE=true\n' "\$(openssl rand -base64 32)" >> .env
  chmod 600 .env
fi
grep -q '^APP_TIMEZONE=' .env && sed -i 's|^APP_TIMEZONE=.*|APP_TIMEZONE=Asia/Jakarta|' .env || echo 'APP_TIMEZONE=Asia/Jakarta' >> .env

sudo mkdir -p data/storage/{app/public,app/private,framework/{cache/data,sessions,views},logs} data/database
[ -f data/database/database.sqlite ] || sudo touch data/database/database.sqlite
sudo chown -R 33:33 data

HAD_PREVIOUS=0
if docker image inspect simrs-api:local >/dev/null 2>&1; then
  docker tag simrs-api:local simrs-api:previous
  HAD_PREVIOUS=1
fi

docker compose build -q
docker compose up -d --force-recreate

healthy() { for i in \$(seq 1 45); do curl -fs -o /dev/null http://127.0.0.1:$LOCAL_PORT/up && return 0; sleep 2; done; return 1; }

if ! healthy; then
  echo "  !! Health check gagal. Log terakhir:"
  docker logs --tail 40 simrs-api || true
  if [ "\$HAD_PREVIOUS" = 1 ]; then
    echo "  !! Rollback ke image sebelumnya"
    docker tag simrs-api:previous simrs-api:local
    docker compose up -d --force-recreate
    healthy && echo "  Rollback berhasil — versi lama kembali jalan." || echo "  !! Rollback juga gagal, cek manual."
  fi
  exit 1
fi

if [ ! -f admin-password.txt ]; then
  docker exec simrs-api php artisan db:seed --force >/dev/null
  PW=\$(openssl rand -base64 18 | tr -d '/+=')
  docker exec simrs-api php artisan tinker --execute \\
    "App\\\\Models\\\\User::where('email','admin@simrs.local')->update(['password'=>bcrypt('\$PW')]);" >/dev/null
  echo "\$PW" > admin-password.txt; chmod 600 admin-password.txt
  echo "  Akun superadmin: admin@simrs.local — password di ~/$REMOTE_DIR/admin-password.txt"
fi
docker image prune -f >/dev/null
EOF
}

# --- 6. Nginx domain utama -> container (idempoten) -------------------
setup_nginx() {
  log "Cek nginx $DOMAIN ..."
  remote "bash -s" <<'EOF'
set -euo pipefail
F=/etc/nginx/sites-available/default
if sudo grep -q "127.0.0.1:8090" $F; then echo "  sudah terarah ke container"; exit 0; fi
B=/root/backup-infra/$(date +%Y%m%d-%H%M%S)-simrs; sudo mkdir -p $B
sudo cp -a /etc/nginx $B/nginx; sudo cp -a /var/www/html $B/www-html
echo "  backup nginx + website statis lama: $B"
sudo python3 - $F <<'PY'
import re, sys
p = sys.argv[1]; s = open(p).read()
# Blok bawaan Debian (boleh berisi baris komentar), bukan yang sudah dikomentari.
pat = re.compile(r"^\tlocation / \{\n(?:\t\t#.*\n)*\t\ttry_files \$uri \$uri/ =404;\n\t\}\n", re.M)
found = pat.findall(s)
assert len(found) == 1, "blok 'location /' bawaan tidak ditemukan persis sekali"
cf = ("173.245.48.0/20 103.21.244.0/22 103.22.200.0/22 103.31.4.0/22 141.101.64.0/18 108.162.192.0/18 "
      "190.93.240.0/20 188.114.96.0/20 197.234.240.0/22 198.41.128.0/17 162.158.0.0/15 104.16.0.0/13 "
      "104.24.0.0/14 172.64.0.0/13 131.0.72.0/22 2400:cb00::/32 2606:4700::/32 2803:f800::/32 "
      "2405:b500::/32 2405:8100::/32 2a06:98c0::/29 2c0f:f248::/32")
new = ("\t# SIMRS Gateway (container simrs-api). Website statis lama ada di /root/backup-infra.\n"
       + "".join(f"\tset_real_ip_from {c};\n" for c in cf.split())
       + "\treal_ip_header CF-Connecting-IP;\n"
       "\tclient_max_body_size 6m;\n"
       "\tlocation / {\n"
       "\t\tproxy_pass http://127.0.0.1:8090;\n"
       "\t\tproxy_http_version 1.1;\n"
       "\t\tproxy_set_header Host $host;\n"
       "\t\tproxy_set_header X-Real-IP $remote_addr;\n"
       "\t\tproxy_set_header X-Forwarded-For $remote_addr;\n"
       "\t\tproxy_set_header X-Forwarded-Proto https;\n"
       "\t\tproxy_set_header X-Forwarded-Host $host;\n"
       "\t\tproxy_set_header X-Forwarded-Port 443;\n"
       "\t\tproxy_read_timeout 120s;\n"
       "\t}\n")
open(p, "w").write(pat.sub(lambda m: new, s, count=1))
PY
if sudo nginx -t 2>/dev/null; then
  sudo systemctl reload nginx; echo "  nginx diarahkan ke container & di-reload"
else
  echo "  !! nginx -t gagal — rollback config"; sudo cp $B/nginx/sites-available/default $F; exit 1
fi
EOF
}

verify() {
  log "Verifikasi publik:"
  local fail=0
  for u in "https://$DOMAIN/" "https://$DOMAIN/up" "https://$DOMAIN/login" \
           "https://$DOMAIN:8443/" "https://$DOMAIN:2053/"; do
    code="$(curl -s -o /dev/null -m 15 -w '%{http_code}' "$u")"
    printf '  %-40s %s\n' "$u" "$code"
    [[ "$code" =~ ^(2|3) ]] || fail=1
  done
  [[ $fail == 0 ]] || warn "Ada endpoint yang tidak 2xx/3xx — cek output di atas."
}

main() {
  case "$MODE" in
    --check)   check_server ;;
    --dry-run) check_server; build_local; upload ;;
    "")        check_server; build_local; backup_remote; upload; release; setup_nginx; verify ;;
    *)         echo "Usage: ./deploy.sh [--check|--dry-run]" >&2; exit 1 ;;
  esac
  ok "Selesai."
}

main
