<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PaymentProof;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminPaymentProofController extends Controller
{
    private function authorizeSuperadmin(Request $request): void
    {
        abort_unless($request->user() && $request->user()->hasRole('superadmin'), 403, 'Akses khusus Superadmin.');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeSuperadmin($request);

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'proof_image' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:5120',
            ],
            'plan_name' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'integer', 'min:0'],
            'bank_source' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
            'auto_approve' => ['nullable', 'boolean'],
            'extend_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ], [
            'proof_image.required' => 'File bukti transfer wajib diunggah.',
            'proof_image.image' => 'File harus berupa gambar (JPG, JPEG, PNG, WEBP). File dokumen/format lain tidak diperbolehkan.',
            'proof_image.mimes' => 'Hanya format gambar JPG, JPEG, PNG, dan WEBP yang diperbolehkan.',
            'proof_image.max' => 'Ukuran gambar maksimal 5 MB.',
        ]);

        $file = $request->file('proof_image');
        $storedPath = $file->store(PaymentProof::DIRECTORY, PaymentProof::DISK);

        $isAutoApprove = !empty($validated['auto_approve']);
        $extendDays = (int) ($validated['extend_days'] ?? 30);

        $proof = PaymentProof::create([
            'user_id' => $validated['user_id'],
            'plan_name' => $validated['plan_name'],
            'amount' => $validated['amount'],
            'proof_image' => $storedPath,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size_bytes' => $file->getSize(),
            'bank_source' => $validated['bank_source'] ?? null,
            'notes' => $validated['notes'] ?? 'Diunggah oleh Superadmin',
            'status' => $isAutoApprove ? 'approved' : 'pending',
            'uploaded_by_role' => 'admin',
            'verified_at' => $isAutoApprove ? now() : null,
            'verified_by' => $isAutoApprove ? $request->user()->id : null,
        ]);

        $targetUser = User::findOrFail($validated['user_id']);

        if ($isAutoApprove) {
            if ($targetUser->subscription_ends_at && $targetUser->subscription_ends_at->isFuture()) {
                $targetUser->subscription_ends_at = $targetUser->subscription_ends_at->addDays($extendDays);
            } else {
                $targetUser->subscription_ends_at = now()->addDays($extendDays);
            }
            $targetUser->save();

            return back()->with('success', "Bukti transfer {$targetUser->name} berhasil diunggah & langsung disetujui. Masa aktif diperpanjang {$extendDays} hari.");
        }

        return back()->with('success', "Bukti transfer {$targetUser->name} berhasil disimpan.");
    }

    public function approve(Request $request, PaymentProof $paymentProof): RedirectResponse
    {
        $this->authorizeSuperadmin($request);

        abort_if($paymentProof->status === 'approved', 422, 'Bukti pembayaran ini sudah disetujui sebelumnya.');

        $days = (int) $request->input('days', 30);
        $user = $paymentProof->user;

        $paymentProof->update([
            'status' => 'approved',
            'verified_at' => now(),
            'verified_by' => $request->user()->id,
            'admin_notes' => $request->input('admin_notes', 'Diverifikasi dan disetujui Superadmin'),
        ]);

        if ($user->subscription_ends_at && $user->subscription_ends_at->isFuture()) {
            $user->subscription_ends_at = $user->subscription_ends_at->addDays($days);
        } else {
            $user->subscription_ends_at = now()->addDays($days);
        }
        $user->save();

        return back()->with('success', "Pembayaran {$user->name} disetujui. Langganan aktif hingga {$user->subscription_ends_at->format('d M Y')}.");
    }

    public function reject(Request $request, PaymentProof $paymentProof): RedirectResponse
    {
        $this->authorizeSuperadmin($request);

        $paymentProof->update([
            'status' => 'rejected',
            'verified_at' => now(),
            'verified_by' => $request->user()->id,
            'admin_notes' => $request->input('admin_notes', 'Bukti pembayaran tidak sesuai atau tidak valid.'),
        ]);

        return back()->with('success', "Bukti transfer {$paymentProof->user->name} ditolak.");
    }

    public function destroy(Request $request, PaymentProof $paymentProof): RedirectResponse
    {
        $this->authorizeSuperadmin($request);

        if ($paymentProof->proof_image) {
            Storage::disk(PaymentProof::DISK)->delete($paymentProof->proof_image);
        }

        $paymentProof->delete();

        return back()->with('success', 'Data bukti transfer berhasil dihapus.');
    }
}
