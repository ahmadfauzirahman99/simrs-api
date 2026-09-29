<?php

namespace App\Services\Bpjs\MedicalRecord;

use InvalidArgumentException;

class GenerateBpjsMedicalRecordBundleService
{
    public function fromSample(?string $path = null): array
    {
        $path = $path ?: config('services.bpjs_medical_record.sample_path');

        if (!$path || !is_file($path)) {
            throw new InvalidArgumentException('Sample bundle BPJS medical record tidak ditemukan.');
        }

        $content = file_get_contents($path);

        if ($content === false) {
            throw new InvalidArgumentException('Gagal membaca sample bundle BPJS medical record.');
        }

        $data = json_decode($content, true);

        if (!is_array($data)) {
            throw new InvalidArgumentException('Sample bundle BPJS medical record bukan JSON yang valid.');
        }

        return $data;
    }

    public function makeSnapshot(array $bundle): array
    {
        $entryCount = count($bundle['entry'] ?? []);
        $resourceTypes = [];

        foreach (($bundle['entry'] ?? []) as $entry) {
            $type = $entry['resource']['resourceType'] ?? null;

            if (!$type) {
                continue;
            }

            $resourceTypes[$type] = ($resourceTypes[$type] ?? 0) + 1;
        }

        ksort($resourceTypes);

        return [
            'bundle_id' => $bundle['id'] ?? null,
            'resource_type' => $bundle['resourceType'] ?? null,
            'bundle_type' => $bundle['type'] ?? null,
            'identifier_system' => $bundle['identifier']['system'] ?? null,
            'identifier_value' => $bundle['identifier']['value'] ?? null,
            'entry_count' => $entryCount,
            'resource_summary' => $resourceTypes,
        ];
    }
}
