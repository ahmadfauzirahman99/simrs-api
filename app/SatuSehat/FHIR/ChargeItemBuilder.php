<?php

namespace App\SatuSehat\FHIR;

class ChargeItemBuilder extends BaseFhirResource
{
    private string  $status          = 'billable';
    private ?array  $code            = null;
    private ?array  $subject         = null;
    private ?string $encounterId     = null;
    private ?array  $occurrencePeriod = null;
    private ?array  $performer       = null;
    private int     $quantity        = 1;
    private ?string $accountId       = null;
    private ?int    $unitPrice       = null;
    private ?int    $totalPrice      = null;

    public function setCode(string $kptlCode, string $kptlDisplay): self
    {
        $this->code = [
            'coding' => [
                [
                    'system'  => 'http://terminology.kemkes.go.id/CodeSystem/kptl',
                    'code'    => $kptlCode,
                    'display' => $kptlDisplay,
                ],
            ],
        ];
        return $this;
    }

    public function setSubject(string $patientId): self
    {
        $this->subject = ['reference' => "Patient/{$patientId}"];
        return $this;
    }

    public function setEncounter(string $encounterId): self
    {
        $this->encounterId = $encounterId;
        return $this;
    }

    public function setOccurrencePeriod(string $start, string $end): self
    {
        $this->occurrencePeriod = ['start' => $start, 'end' => $end];
        return $this;
    }

    public function setPerformer(string $practitionerId, string $snomedCode = '39677007', string $snomedDisplay = 'Internal medicine specialist'): self
    {
        $this->performer = [
            [
                'function' => [
                    'coding' => [
                        [
                            'system'  => 'http://snomed.info/sct',
                            'code'    => $snomedCode,
                            'display' => $snomedDisplay,
                        ],
                    ],
                ],
                'actor' => ['reference' => "Practitioner/{$practitionerId}"],
            ],
        ];
        return $this;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function setAccount(string $accountId): self
    {
        $this->accountId = $accountId;
        return $this;
    }

    public function setPrice(int $unitPrice, ?int $totalPrice = null): self
    {
        $this->unitPrice  = $unitPrice;
        $this->totalPrice = $totalPrice ?? ($unitPrice * $this->quantity);
        return $this;
    }

    public function toArray(): array
    {
        $data = [
            'resourceType'     => 'ChargeItem',
            'status'           => $this->status,
            'code'             => $this->code,
            'subject'          => $this->subject,
            'quantity'         => ['value' => $this->quantity],
        ];

        if ($this->encounterId !== null) {
            $data['context'] = ['reference' => "Encounter/{$this->encounterId}"];
        }

        if ($this->occurrencePeriod !== null) {
            $data['occurrencePeriod'] = $this->occurrencePeriod;
        }

        if ($this->performer !== null) {
            $data['performer'] = $this->performer;
        }

        if ($this->accountId !== null) {
            $data['account'] = [['reference' => "Account/{$this->accountId}"]];
        }

        $extensions = [];
        if ($this->unitPrice !== null) {
            $extensions[] = [
                'url'        => 'https://fhir.kemkes.go.id/r4/StructureDefinition/unitPrice',
                'valueMoney' => ['value' => $this->unitPrice, 'currency' => 'IDR'],
            ];
        }
        if ($this->totalPrice !== null) {
            $extensions[] = [
                'url'        => 'https://fhir.kemkes.go.id/r4/StructureDefinition/totalPrice',
                'valueMoney' => ['value' => $this->totalPrice, 'currency' => 'IDR'],
            ];
        }
        if ($extensions !== []) {
            $data['extension'] = $extensions;
        }

        return $data;
    }
}
