<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class HealthCheckResultPresenter
{
    /**
     * @param  iterable<int, object|array<string, mixed>>  $results
     * @return array<int, array<string, mixed>>
     */
    public static function present(iterable $results): array
    {
        return collect($results)
            ->map(fn (object|array $result): array => self::presentResult($result))
            ->values()
            ->all();
    }

    /**
     * @param  object|array<string, mixed>  $result
     * @return array<string, mixed>
     */
    public static function presentResult(object|array $result): array
    {
        $data = self::normalizeResult($result);
        $meta = is_array($data['meta'] ?? null) ? $data['meta'] : [];
        $securityAdvisories = self::isSecurityAdvisoriesMeta($meta)
            ? self::presentSecurityAdvisories($meta)
            : null;

        return [
            'name' => (string) ($data['name'] ?? ''),
            'label' => (string) ($data['label'] ?? ''),
            'status' => (string) ($data['status'] ?? ''),
            'shortSummary' => (string) ($data['shortSummary'] ?? ''),
            'notificationMessage' => self::stringOrNull($data['notificationMessage'] ?? null),
            'metaRows' => self::presentMetaRows($securityAdvisories ? [] : $meta),
            'securityAdvisories' => $securityAdvisories,
        ];
    }

    /**
     * @param  object|array<string, mixed>  $result
     * @return array<string, mixed>
     */
    protected static function normalizeResult(object|array $result): array
    {
        if (is_array($result)) {
            return $result;
        }

        return [
            'name' => $result->name ?? null,
            'label' => $result->label ?? null,
            'status' => $result->status ?? null,
            'shortSummary' => $result->shortSummary ?? null,
            'notificationMessage' => $result->notificationMessage ?? null,
            'meta' => $result->meta ?? [],
        ];
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<int, array<string, mixed>>
     */
    protected static function presentMetaRows(array $meta): array
    {
        return collect($meta)
            ->map(function (mixed $value, string|int $key): ?array {
                $normalizedValue = self::normalizeMetaValue($value);

                if ($normalizedValue === null) {
                    return null;
                }

                return [
                    'label' => Str::headline((string) $key),
                    ...$normalizedValue,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    protected static function normalizeMetaValue(mixed $value): ?array
    {
        if (is_array($value) && array_is_list($value) && self::isScalarList($value)) {
            $items = collect($value)
                ->map(fn (mixed $item): ?string => self::stringOrNull($item))
                ->filter()
                ->values()
                ->all();

            if ($items === []) {
                return null;
            }

            $display = implode(', ', $items);

            return [
                'value' => $display,
                'isBlock' => false,
                'isUrl' => self::isUrl($display),
            ];
        }

        if (! is_array($value)) {
            $display = self::stringOrNull($value);

            if ($display === null) {
                return null;
            }

            return [
                'value' => $display,
                'isBlock' => false,
                'isUrl' => self::isUrl($display),
            ];
        }

        $json = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (! is_string($json) || in_array($json, ['[]', '{}'], true)) {
            return null;
        }

        return [
            'value' => $json,
            'isBlock' => true,
            'isUrl' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    protected static function isSecurityAdvisoriesMeta(array $meta): bool
    {
        if ($meta === [] || array_is_list($meta)) {
            return false;
        }

        $packageCount = 0;

        foreach ($meta as $packageName => $advisories) {
            if (! is_string($packageName) || ! is_array($advisories) || ! array_is_list($advisories) || $advisories === []) {
                return false;
            }

            foreach ($advisories as $advisory) {
                if (! is_array($advisory) || ! self::looksLikeSecurityAdvisory($advisory)) {
                    return false;
                }
            }

            $packageCount++;
        }

        return $packageCount > 0;
    }

    /**
     * @param  array<string, mixed>  $advisory
     */
    protected static function looksLikeSecurityAdvisory(array $advisory): bool
    {
        return collect(['advisoryId', 'affectedVersions', 'title', 'link', 'cve'])
            ->contains(static fn (string $key): bool => array_key_exists($key, $advisory));
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $meta
     * @return array<string, mixed>
     */
    protected static function presentSecurityAdvisories(array $meta): array
    {
        $packages = collect($meta)
            ->map(function (array $advisories, string $packageName): array {
                $presentedAdvisories = collect($advisories)
                    ->map(fn (array $advisory): array => self::presentSecurityAdvisory($advisory))
                    ->values()
                    ->all();

                return [
                    'packageName' => $packageName,
                    'advisoryCount' => count($presentedAdvisories),
                    'advisories' => $presentedAdvisories,
                ];
            })
            ->values();

        return [
            'packageCount' => $packages->count(),
            'advisoryCount' => $packages->sum('advisoryCount'),
            'packages' => $packages->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $advisory
     * @return array<string, mixed>
     */
    protected static function presentSecurityAdvisory(array $advisory): array
    {
        $link = self::firstUrl([
            $advisory['link'] ?? null,
            $advisory['url'] ?? null,
        ]);

        return [
            'title' => self::headlineForAdvisory($advisory),
            'link' => $link,
            'cve' => self::stringOrNull($advisory['cve'] ?? null),
            'affectedVersions' => self::stringOrNull($advisory['affectedVersions'] ?? null),
            'reportedAt' => self::formatTimestamp($advisory['reportedAt'] ?? null),
            'sources' => self::presentSources($advisory, $link),
            'details' => self::presentMetaRows(array_filter([
                'advisoryId' => $advisory['advisoryId'] ?? null,
                'severity' => $advisory['severity'] ?? null,
                'source' => $advisory['source'] ?? null,
            ], static fn (mixed $value): bool => ! in_array($value, [null, '', []], true))),
        ];
    }

    /**
     * @param  array<string, mixed>  $advisory
     */
    protected static function headlineForAdvisory(array $advisory): string
    {
        return self::stringOrNull($advisory['title'] ?? null)
            ?? self::stringOrNull($advisory['advisoryId'] ?? null)
            ?? self::stringOrNull($advisory['cve'] ?? null)
            ?? 'Security advisory';
    }

    /**
     * @param  array<string, mixed>  $advisory
     * @return array<int, array{label: string, url: string}>
     */
    protected static function presentSources(array $advisory, ?string $fallbackLink): array
    {
        $sources = collect($advisory['sources'] ?? [])
            ->map(function (mixed $source): ?array {
                if (is_string($source) && self::isUrl($source)) {
                    return [
                        'label' => self::hostLabel($source),
                        'url' => $source,
                    ];
                }

                if (! is_array($source)) {
                    return null;
                }

                $url = self::firstUrl([
                    $source['url'] ?? null,
                    $source['link'] ?? null,
                ]);

                if ($url === null) {
                    return null;
                }

                return [
                    'label' => self::stringOrNull($source['name'] ?? null)
                        ?? self::stringOrNull($source['title'] ?? null)
                        ?? self::hostLabel($url),
                    'url' => $url,
                ];
            })
            ->filter(static fn (?array $source): bool => $source !== null);

        if ($fallbackLink !== null && ! $sources->contains(static fn (array $source): bool => $source['url'] === $fallbackLink)) {
            $sources->prepend([
                'label' => self::hostLabel($fallbackLink),
                'url' => $fallbackLink,
            ]);
        }

        return $sources
            ->unique('url')
            ->values()
            ->all();
    }

    protected static function hostLabel(string $url): string
    {
        return parse_url($url, PHP_URL_HOST) ?: 'Reference';
    }

    protected static function formatTimestamp(mixed $value): ?string
    {
        $timestamp = self::stringOrNull($value);

        if ($timestamp === null) {
            return null;
        }

        try {
            return Carbon::parse($timestamp)->format('M j, Y');
        } catch (\Throwable) {
            return $timestamp;
        }
    }

    /**
     * @param  array<int, mixed>  $values
     */
    protected static function firstUrl(array $values): ?string
    {
        foreach ($values as $value) {
            $url = self::stringOrNull($value);

            if ($url !== null && self::isUrl($url)) {
                return $url;
            }
        }

        return null;
    }

    protected static function stringOrNull(mixed $value): ?string
    {
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_scalar($value)) {
            $display = trim((string) $value);

            return $display === '' ? null : $display;
        }

        return null;
    }

    /**
     * @param  array<int, mixed>  $values
     */
    protected static function isScalarList(array $values): bool
    {
        return collect($values)->every(static fn (mixed $item): bool => is_scalar($item) || $item === null || is_bool($item));
    }

    protected static function isUrl(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }
}
