<?php

/**
 * Global view helpers.
 *
 * Guarded function declarations, the same pattern the vendor packages use
 * (vendor/enlivenapp/flight-csrf/src/Helpers/csrf_helper.php), loaded from
 * app/config/services.php so every boot path (web, CLI) has them.
 *
 * @package Pubvana\Support
 */

declare(strict_types=1);

if (!function_exists('trust_badge')) {
    /**
     * The trust status badge for an addon, one framing across the plugins
     * table and the theme cards.
     *
     * Statuses come from the Pubvana trust service cache: trusted, known,
     * malicious, unknown, and 'none' for anything not checked yet. A
     * warning from the trust service reads as the badge tooltip: it is an
     * answer to look at, not just a verdict to trust.
     *
     * @param string      $status  'trusted'|'known'|'malicious'|'unknown'|'none'
     * @param string|null $warning Warning text for the badge tooltip
     */
    function trust_badge(string $status, ?string $warning = null): string
    {
        $warningAttr = $warning !== null && $warning !== ''
            ? ' title="' . htmlspecialchars($warning, ENT_QUOTES, 'UTF-8') . '"'
            : '';

        return match ($status) {
            'trusted'   => '<span class="badge bg-green-lt text-success"><i class="ti ti-shield-check me-1"></i>Trusted</span>',
            'known'     => '<span class="badge bg-azure-lt"' . $warningAttr . '><i class="ti ti-shield me-1"></i>Known</span>',
            'malicious' => '<span class="badge bg-red-lt text-danger"' . $warningAttr . '><i class="ti ti-alert-triangle me-1"></i>Malicious</span>',
            'unknown'   => '<span class="badge bg-yellow-lt text-yellow"><i class="ti ti-help me-1"></i>Unknown</span>',
            default     => '<span class="badge bg-secondary-lt"><i class="ti ti-circle-dashed me-1"></i>Not checked</span>',
        };
    }
}
