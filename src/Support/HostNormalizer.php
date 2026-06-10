<?php

declare(strict_types=1);

namespace Tenancy\Support;

use Tenancy\Contracts\Support\HostNormalizerInterface;

final class HostNormalizer implements HostNormalizerInterface
{
    public function normalize(?string $host): ?string
    {
        if ($host === null) {
            return null;
        }

        $host = mb_trim(mb_strtolower($host));

        if ($host === '') {
            return null;
        }

        $host = $this->stripSchemePathQueryAndFragment($host);
        $host = $this->stripPort($host);
        $host = mb_trim($host, '.');

        return $host !== '' ? $host : null;
    }

    private function stripSchemePathQueryAndFragment(string $host): string
    {
        if (preg_match('#^[a-z][a-z0-9+\-.]*://#i', $host) === 1) {
            $parsedHost = parse_url($host, PHP_URL_HOST);

            return is_string($parsedHost) ? $parsedHost : '';
        }

        return preg_split('/[\/?#]/', $host, 2)[0] ?? $host;
    }

    private function stripPort(string $host): string
    {
        // Keeps IPv6 literals safer, although tenant domains usually should not be IPv6.
        if (str_starts_with($host, '[')) {
            $closingBracketPosition = mb_strpos($host, ']');

            if ($closingBracketPosition === false) {
                return $host;
            }

            return mb_substr($host, 1, $closingBracketPosition - 1);
        }

        return explode(':', $host, 2)[0];
    }
}
