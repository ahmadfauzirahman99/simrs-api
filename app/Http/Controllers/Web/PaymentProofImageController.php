<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PaymentProof;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentProofImageController extends Controller
{
    public function __invoke(Request $request, PaymentProof $paymentProof): StreamedResponse
    {
        $user = $request->user();

        // 404 (bukan 403) agar keberadaan bukti milik orang lain tidak bisa ditebak.
        abort_unless(
            $user->hasRole('superadmin') || $paymentProof->user_id === $user->id,
            404
        );

        $disk = Storage::disk(PaymentProof::DISK);
        abort_unless($paymentProof->proof_image && $disk->exists($paymentProof->proof_image), 404);

        return $disk->response($paymentProof->proof_image, null, [
            'Content-Type' => $paymentProof->mime_type ?: $disk->mimeType($paymentProof->proof_image),
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; img-src 'self'; style-src 'unsafe-inline'",
        ]);
    }
}
