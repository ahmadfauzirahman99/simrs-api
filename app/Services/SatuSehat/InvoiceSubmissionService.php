<?php

namespace App\Services\SatuSehat;

use App\SatuSehat\Http\HttpException;
use App\SatuSehat\SatuSehatManager;

class InvoiceSubmissionService
{
    public function __construct(private readonly SatuSehatManager $ss) {}

    public function send(array $payload): array
    {
        try {
            $builder = $this->ss->invoice()
                ->setSubject($payload['patient_satu_sehat'])
                ->setDate($payload['date'])
                ->setAccount($payload['account_id'])
                ->setTotalNet((int) $payload['total_net']);

            foreach ($payload['line_items'] as $index => $item) {
                $builder->addLineItem(
                    $index + 1,
                    $item['charge_item_id'],
                    $item['kptl_code'],
                    $item['kptl_display'],
                    (int) $item['amount']
                );
            }

            $fhirPayload = $builder->toArray();
            $response    = $this->ss->fhir()->post('Invoice', $fhirPayload);

            return [
                'ok'       => true,
                'code'     => 201,
                'message'  => 'Berhasil kirim Invoice ke SATUSEHAT',
                'response' => [
                    'satusehat_response' => $response,
                    'request_payload'    => $fhirPayload,
                ],
            ];
        } catch (HttpException $e) {
            return [
                'ok'       => false,
                'code'     => $e->getStatusCode(),
                'message'  => 'Gagal kirim Invoice ke SATUSEHAT',
                'response' => json_decode($e->getBody(), true),
            ];
        }
    }
}
