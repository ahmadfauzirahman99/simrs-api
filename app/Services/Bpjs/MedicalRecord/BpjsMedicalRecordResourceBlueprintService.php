<?php

namespace App\Services\Bpjs\MedicalRecord;

use InvalidArgumentException;

class BpjsMedicalRecordResourceBlueprintService
{
    public function all(): array
    {
        return config('bpjs_medical_record.resources', []);
    }

    public function get(string $resource): array
    {
        $definition = $this->all()[$resource] ?? null;

        if (!$definition) {
            throw new InvalidArgumentException("Blueprint resource [$resource] tidak ditemukan.");
        }

        return [
            'resource' => $resource,
            ...$definition,
        ];
    }

    public function template(string $resource, array $overrides = []): array
    {
        $definition = $this->get($resource);
        $template = $definition['template'] ?? [];

        foreach ($overrides as $path => $value) {
            $this->setNestedValue($template, $path, $value);
        }

        return $template;
    }

    protected function setNestedValue(array &$target, string $path, mixed $value): void
    {
        $segments = preg_split('/\.(?![^\[]*\])/', $path) ?: [];
        $cursor = &$target;

        foreach ($segments as $index => $segment) {
            preg_match('/^([^\[]+)(?:\[(\d+)\])?$/', $segment, $matches);
            $key = $matches[1] ?? $segment;
            $position = isset($matches[2]) ? (int) $matches[2] : null;
            $last = $index === count($segments) - 1;

            if ($position === null) {
                if ($last) {
                    $cursor[$key] = $value;
                    return;
                }

                if (!isset($cursor[$key]) || !is_array($cursor[$key])) {
                    $cursor[$key] = [];
                }

                $cursor = &$cursor[$key];
                continue;
            }

            if (!isset($cursor[$key]) || !is_array($cursor[$key])) {
                $cursor[$key] = [];
            }

            if (!isset($cursor[$key][$position]) || !is_array($cursor[$key][$position])) {
                $cursor[$key][$position] = [];
            }

            if ($last) {
                $cursor[$key][$position] = $value;
                return;
            }

            $cursor = &$cursor[$key][$position];
        }
    }
}
