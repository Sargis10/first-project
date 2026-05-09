<?php

declare(strict_types=1);

/**
 * Client IP for abuse controls. Behind Apache reverse proxy, REMOTE_ADDR may be loopback;
 * then the first X-Forwarded-For / X-Real-IP hop is used (same trust model as HTTPS detection).
 */
function sskClientIp(): string
{
    $direct = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
    if ($direct !== '' && $direct !== '127.0.0.1' && $direct !== '::1') {
        return $direct;
    }
    $xff = (string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
    if ($xff !== '') {
        $first = trim(explode(',', $xff)[0]);
        if ($first !== '') {
            return $first;
        }
    }
    $xri = (string)($_SERVER['HTTP_X_REAL_IP'] ?? '');
    if ($xri !== '') {
        $first = trim(explode(',', $xri)[0]);
        if ($first !== '') {
            return $first;
        }
    }
    return $direct !== '' ? $direct : '0.0.0.0';
}

/**
 * Fixed-window rate limiter (file + flock). Fails open if storage cannot be used.
 *
 * @return bool true if the request should be rejected (limit exceeded)
 */
function sskRateLimitExceeded(string $bucket, int $maxAttempts, int $windowSeconds): bool
{
    if ($maxAttempts < 1 || $windowSeconds < 1) {
        return false;
    }
    $dir = sys_get_temp_dir() . '/ssk-rate';
    if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
        return false;
    }
    $key = hash('sha256', sskClientIp() . "\0" . $bucket);
    $path = $dir . '/' . $key . '.json';
    $now = time();
    $fp = @fopen($path, 'c+');
    if ($fp === false) {
        return false;
    }
    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        return false;
    }
    $raw = stream_get_contents($fp);
    $data = json_decode($raw !== false && $raw !== '' ? $raw : '{}', true);
    if (!is_array($data)) {
        $data = [];
    }
    $start = (int)($data['start'] ?? $now);
    $count = (int)($data['count'] ?? 0);
    if ($now - $start >= $windowSeconds) {
        $start = $now;
        $count = 0;
    }
    if ($count >= $maxAttempts) {
        flock($fp, LOCK_UN);
        fclose($fp);
        return true;
    }
    $count++;
    try {
        $out = json_encode(['start' => $start, 'count' => $count], JSON_THROW_ON_ERROR);
    } catch (Throwable $e) {
        flock($fp, LOCK_UN);
        fclose($fp);
        return false;
    }
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, $out);
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return false;
}
