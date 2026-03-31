<?php

declare(strict_types=1);

namespace App\Shared\Application;

final class SafeUrlGuard
{
    public function assertPublicHttpUrl(string $url): void
    {
        $trimmed = trim($url);

        if ($trimmed === '' || filter_var($trimmed, \FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException('La URL no es valida.');
        }

        $parts = parse_url($trimmed);
        $scheme = mb_strtolower((string) ($parts['scheme'] ?? ''));
        $host = mb_strtolower((string) ($parts['host'] ?? ''));

        if (!\in_array($scheme, ['http', 'https'], true) || $host === '') {
            throw new \InvalidArgumentException('Solo se permiten URLs http o https publicas.');
        }

        if (\in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            throw new \InvalidArgumentException('No se permiten hosts locales.');
        }

        if (filter_var($host, \FILTER_VALIDATE_IP) !== false) {
            $this->assertPublicIp($host);

            return;
        }

        $ips = gethostbynamel($host);

        if ($ips === false || $ips === []) {
            throw new \InvalidArgumentException('No se ha podido resolver el host indicado.');
        }

        foreach ($ips as $ip) {
            $this->assertPublicIp($ip);
        }
    }

    private function assertPublicIp(string $ip): void
    {
        $valid = filter_var(
            $ip,
            \FILTER_VALIDATE_IP,
            \FILTER_FLAG_NO_PRIV_RANGE | \FILTER_FLAG_NO_RES_RANGE,
        );

        if ($valid === false) {
            throw new \InvalidArgumentException('La URL apunta a una direccion no publica.');
        }
    }
}
