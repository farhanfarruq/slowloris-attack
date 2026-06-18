<?php

namespace App\Services;

class ToolProfileService
{
    private ?array $fallbackConfig = null;

    public function all(): array
    {
        return $this->configValue('profiles', []);
    }

    public function keys(): array
    {
        return array_keys($this->all());
    }

    public function defaultKey(): string
    {
        return (string) $this->configValue('default', 'slowloris');
    }

    public function normalize(?string $key): string
    {
        $key = strtolower(trim((string) $key));

        return array_key_exists($key, $this->all()) ? $key : $this->defaultKey();
    }

    public function get(?string $key): array
    {
        $normalized = $this->normalize($key);

        return $this->all()[$normalized] + ['key' => $normalized];
    }

    public function options(): array
    {
        return collect($this->all())
            ->map(fn (array $profile, string $key) => [
                'key' => $key,
                'label' => $profile['label'] ?? $key,
                'owner' => $profile['owner'] ?? null,
                'detected_label' => $profile['detected_label'] ?? $this->detectedLabel($key),
                'attack_patterns' => $profile['attack_patterns'] ?? [],
                'default_attack_pattern' => $profile['default_attack_pattern'] ?? null,
            ])
            ->values()
            ->all();
    }

    public function labels(): array
    {
        return collect($this->all())
            ->mapWithKeys(fn (array $profile, string $key) => [$key => $profile['label'] ?? $key])
            ->all();
    }

    public function detectedLabel(?string $key): string
    {
        $profile = $this->get($key);

        return (string) ($profile['detected_label'] ?? (($profile['label'] ?? 'DDoS') . ' Detected'));
    }

    public function detectedAllowedKey(?string $key): string
    {
        return $this->normalize($key) . '_detected_allowed';
    }

    public function detectedLabels(): array
    {
        return collect($this->all())
            ->map(fn (array $profile, string $key) => $profile['detected_label'] ?? $this->detectedLabel($key))
            ->values()
            ->all();
    }

    public function isDetectedLabel(string $classification, ?string $key = null): bool
    {
        if ($key !== null) {
            return $classification === $this->detectedLabel($key);
        }

        return in_array($classification, $this->detectedLabels(), true);
    }

    private function configValue(string $key, mixed $default): mixed
    {
        try {
            return config('tool_profiles.' . $key, $default);
        } catch (\Throwable) {
            $this->fallbackConfig ??= require dirname(__DIR__, 2) . '/config/tool_profiles.php';

            return $this->fallbackConfig[$key] ?? $default;
        }
    }
}
