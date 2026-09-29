<?php

namespace App\SatuSehat\FHIR;

class InvoiceBuilder extends BaseFhirResource
{
    private string  $status    = 'issued';
    private ?array  $subject   = null;
    private ?string $date      = null;
    private ?string $accountId = null;
    private array   $lineItems = [];
    private ?int    $totalNet  = null;

    public function setSubject(string $patientId): self
    {
        $this->subject = ['reference' => "Patient/{$patientId}"];
        return $this;
    }

    public function setDate(string $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function setAccount(string $accountId): self
    {
        $this->accountId = $accountId;
        return $this;
    }

    public function addLineItem(int $sequence, string $chargeItemId, string $kptlCode, string $kptlDisplay, int $amount): self
    {
        $this->lineItems[] = [
            'sequence'            => $sequence,
            'chargeItemReference' => ['reference' => "ChargeItem/{$chargeItemId}"],
            'priceComponent'      => [
                [
                    'type'   => 'surcharge',
                    'code'   => [
                        'coding' => [
                            [
                                'system'  => 'http://terminology.kemkes.go.id/CodeSystem/kptl',
                                'code'    => $kptlCode,
                                'display' => $kptlDisplay,
                            ],
                        ],
                    ],
                    'amount' => ['value' => $amount, 'currency' => 'IDR'],
                ],
            ],
        ];
        return $this;
    }

    public function setTotalNet(int $amount): self
    {
        $this->totalNet = $amount;
        return $this;
    }

    public function toArray(): array
    {
        $data = [
            'resourceType' => 'Invoice',
            'status'       => $this->status,
            'subject'      => $this->subject,
            'issuer'       => ['reference' => "Organization/{$this->orgId}"],
            'lineItem'     => $this->lineItems,
        ];

        if ($this->date !== null) {
            $data['date'] = $this->date;
        }

        if ($this->accountId !== null) {
            $data['account'] = ['reference' => "Account/{$this->accountId}"];
        }

        if ($this->totalNet !== null) {
            $data['totalNet'] = ['value' => $this->totalNet, 'currency' => 'IDR'];
        }

        return $data;
    }
}
