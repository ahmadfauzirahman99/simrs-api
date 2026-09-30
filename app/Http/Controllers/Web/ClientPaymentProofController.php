<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PaymentProof;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClientPaymentProofController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'proof_image' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:5120', // Maksimal 5MB
            ],
            'plan_name' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'integer', 'min:0'],
            'bank_source' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'proof_image.required' => 'File bukti transfer wajib diunggah.',
            'proof_image.image' => 'File harus berupa gambar (JPG, JPEG, PNG, WEBP). File dokumen/format lain tidak diperbolehkan.',
            'proof_image.mimes' => 'Hanya format gambar JPG, JPEG, PNG, dan WEBP yang diperbolehkan.',
            'proof_image.max' => 'Ukuran gambar maksimal 5 MB.',
        ]);

        $file = $request->file('proof_image');
        $storedPath = $file->store(PaymentProof::DIRECTORY, PaymentProof::DISK);

        $user = $request->user();

        PaymentProof::create([
            'user_id' => $user->id,
            'plan_name' => $validated['plan_name'],
            'amount' => $validated['amount'],
            'proof_image' => $storedPath,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size_bytes' => $file->getSize(),
            'bank_source' => $validated['bank_source'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending',
            'uploaded_by_role' => 'client',
        ]);

        $telegramUser = SiteSetting::get('telegram_contact', 'FauziRahman95');
        $telegramClean = ltrim($telegramUser, '@');

        return back()->with(
            'success',
            "Bukti transfer berhasil diunggah! Tim kami akan segera memverifikasi. Anda juga dapat konfirmasi via Telegram @{$telegramClean}."
        );
    }
}
