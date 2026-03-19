<?php

/**
 * Lightweight mail delivery helpers for Lighthouse.
 *
 * Testing uses a file outbox for deterministic assertions. Other environments
 * use the configured SMTP server, which is Mailpit in local development.
 */

function lh_mail_outbox_dir(): string
{
    return lh_project_root() . '/data/mail';
}

function lh_mail_prepare_outbox(): string
{
    $dir = lh_mail_outbox_dir();

    if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
        throw new RuntimeException("Unable to create mail outbox directory: {$dir}");
    }

    return $dir;
}

/**
 * @param string $to
 * @param string $subject
 * @param string $textBody
 * @param array<string, string> $meta
 * @return string Path or delivery target description
 */
function lh_mail_send(string $to, string $subject, string $textBody, array $meta = []): string
{
    if (lh_is_testing()) {
        return lh_mail_write_outbox($to, $subject, $textBody, $meta);
    }

    lh_mail_send_smtp($to, $subject, $textBody);

    return 'smtp://' . (string) lh_config('mail.host', '127.0.0.1') . ':' . (string) lh_config('mail.port', 1025);
}

/**
 * @param string $to
 * @param string $subject
 * @param string $textBody
 * @param array<string, string> $meta
 * @return string
 */
function lh_mail_write_outbox(string $to, string $subject, string $textBody, array $meta = []): string
{
    $dir = lh_mail_prepare_outbox();
    $timestamp = gmdate('Ymd_His');
    $random = bin2hex(random_bytes(4));
    $path = "{$dir}/{$timestamp}_{$random}.txt";

    $payload = [
        'to' => $to,
        'from_address' => (string) lh_config('mail.from_address', 'noreply@example.test'),
        'from_name' => (string) lh_config('mail.from_name', 'LighthousePHP'),
        'subject' => $subject,
        'meta' => $meta,
        'body' => $textBody,
        'sent_at' => gmdate(DATE_ATOM),
    ];

    file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);

    return $path;
}

function lh_mail_read_response($socket): string
{
    $response = '';

    while (!feof($socket)) {
        $line = fgets($socket);

        if ($line === false) {
            break;
        }

        $response .= $line;

        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }
    }

    return $response;
}

function lh_mail_expect_response($socket, array $codes, string $context): string
{
    $response = lh_mail_read_response($socket);
    $code = (int) substr($response, 0, 3);

    if (!in_array($code, $codes, true)) {
        throw new RuntimeException("SMTP {$context} failed: {$response}");
    }

    return $response;
}

/**
 * Return whether an EHLO response advertises a capability.
 *
 * @param string $response
 * @param string $capability
 * @return bool
 */
function lh_mail_supports_capability(string $response, string $capability): bool
{
    $lines = preg_split("/\r\n|\n|\r/", $response) ?: [];
    $needle = strtoupper($capability);

    foreach ($lines as $line) {
        if (stripos($line, $needle) !== false) {
            return true;
        }
    }

    return false;
}

function lh_mail_write_command($socket, string $command): void
{
    fwrite($socket, $command . "\r\n");
}

function lh_mail_build_message(string $to, string $subject, string $textBody): string
{
    $fromAddress = (string) lh_config('mail.from_address', 'noreply@example.test');
    $fromName = (string) lh_config('mail.from_name', 'LighthousePHP');

    $headers = [
        'From: ' . sprintf('"%s" <%s>', addslashes($fromName), $fromAddress),
        'To: <' . $to . '>',
        'Subject: ' . $subject,
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
    ];

    return implode("\r\n", $headers) . "\r\n\r\n" . str_replace(["\r\n", "\r"], "\n", $textBody) . "\r\n";
}

function lh_mail_send_smtp(string $to, string $subject, string $textBody): void
{
    $encryption = (string) lh_config('mail.encryption', 'none');

    if ($encryption !== 'none') {
        throw new RuntimeException('Only unencrypted SMTP is currently supported.');
    }

    $host = (string) lh_config('mail.host', '127.0.0.1');
    $port = (int) lh_config('mail.port', 1025);
    $username = (string) lh_config('mail.username', '');
    $password = (string) lh_config('mail.password', '');
    $fromAddress = (string) lh_config('mail.from_address', 'noreply@example.test');

    $socket = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 5);

    if (!is_resource($socket)) {
        throw new RuntimeException("Unable to connect to SMTP server {$host}:{$port} ({$errstr}).");
    }

    try {
        stream_set_timeout($socket, 5);

        lh_mail_expect_response($socket, [220], 'connect');
        lh_mail_write_command($socket, 'EHLO localhost');
        $ehloResponse = lh_mail_expect_response($socket, [250], 'EHLO');

        if (($username !== '' || $password !== '') && lh_mail_supports_capability($ehloResponse, 'AUTH')) {
            lh_mail_write_command($socket, 'AUTH LOGIN');
            lh_mail_expect_response($socket, [334], 'AUTH LOGIN');
            lh_mail_write_command($socket, base64_encode($username));
            lh_mail_expect_response($socket, [334], 'AUTH username');
            lh_mail_write_command($socket, base64_encode($password));
            lh_mail_expect_response($socket, [235], 'AUTH password');
        }

        lh_mail_write_command($socket, 'MAIL FROM:<' . $fromAddress . '>');
        lh_mail_expect_response($socket, [250], 'MAIL FROM');
        lh_mail_write_command($socket, 'RCPT TO:<' . $to . '>');
        lh_mail_expect_response($socket, [250, 251], 'RCPT TO');
        lh_mail_write_command($socket, 'DATA');
        lh_mail_expect_response($socket, [354], 'DATA');
        lh_mail_write_command($socket, rtrim(lh_mail_build_message($to, $subject, $textBody), "\r\n") . "\r\n.");
        lh_mail_expect_response($socket, [250], 'message body');
        lh_mail_write_command($socket, 'QUIT');
        lh_mail_read_response($socket);
    } finally {
        fclose($socket);
    }
}

/**
 * @return array<int, array{path:string, payload:array<string, mixed>}>
 */
function lh_mail_outbox_messages(): array
{
    $dir = lh_mail_outbox_dir();

    if (!is_dir($dir)) {
        return [];
    }

    $files = glob($dir . '/*.txt') ?: [];
    sort($files);
    $messages = [];

    foreach ($files as $path) {
        $decoded = json_decode((string) file_get_contents($path), true);

        if (!is_array($decoded)) {
            continue;
        }

        $messages[] = [
            'path' => $path,
            'payload' => $decoded,
        ];
    }

    return $messages;
}

function lh_mail_clear_outbox(): void
{
    foreach (glob(lh_mail_outbox_dir() . '/*.txt') ?: [] as $path) {
        unlink($path);
    }
}
