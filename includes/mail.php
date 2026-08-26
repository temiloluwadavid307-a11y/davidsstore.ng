<?php
/**
 * Simple SMTP email helper for David's Store.
 *
 * Uses environment-configured SMTP settings when available,
 * otherwise falls back to PHP mail().
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Mailer.php';

function sanitize_email_header(string $value): string
{
    return trim(str_replace(["\r", "\n"], '', $value));
}

function build_email_boundary(): string
{
    return '==boundary_' . bin2hex(random_bytes(12));
}

function build_email_message(string $fromName, string $fromEmail, string $to, string $subject, string $html, string $text = ''): array
{
    $boundary = build_email_boundary();
    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
    $headers[] = 'From: ' . $fromName . ' <' . $fromEmail . '>';
    $headers[] = 'To: ' . $to;
    $headers[] = 'Reply-To: ' . $fromEmail;
    $headers[] = 'X-Mailer: PHP/' . phpversion();
    $headers[] = 'Date: ' . date('r');
    $headers[] = 'Content-Transfer-Encoding: 7bit';

    $body = "--{$boundary}\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $body .= ($text ?: strip_tags(str_replace(['<br>', '<br/>', '<p>'], "\r\n", $html))) . "\r\n\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $body .= $html . "\r\n\r\n";
    $body .= "--{$boundary}--\r\n";

    return [
        'headers' => implode("\r\n", $headers),
        'body' => $body,
    ];
}

function smtp_send_message(string $to, string $subject, string $message, string $headers): bool
{
    $host = MAIL_HOST;
    $port = (int) MAIL_PORT;
    $username = MAIL_USERNAME;
    $password = MAIL_PASSWORD;
    $encryption = strtolower(MAIL_ENCRYPTION);
    $from = MAIL_FROM_EMAIL;

    if (empty($host) || empty($from)) {
        return false;
    }

    $remote = ($encryption === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
    $socket = stream_socket_client($remote, $errno, $errstr, 20, STREAM_CLIENT_CONNECT);
    if (!$socket) {
        error_log("SMTP connection failed: [$errno] $errstr");
        return false;
    }

    $response = trim(fgets($socket, 515));
    if (substr($response, 0, 3) !== '220') {
        fclose($socket);
        return false;
    }

    $hostname = sanitize_email_header(gethostname() ?: 'localhost');
    fwrite($socket, "EHLO {$hostname}\r\n");
    $response = ''; while (!feof($socket) && substr($response, -4) !== "\r\n") { $response .= fgets($socket, 515); }

    if ($encryption === 'tls') {
        fwrite($socket, "STARTTLS\r\n");
        $response = trim(fgets($socket, 515));
        if (substr($response, 0, 3) !== '220') {
            fclose($socket);
            return false;
        }
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT)) {
            fclose($socket);
            return false;
        }
        fwrite($socket, "EHLO {$hostname}\r\n");
        $response = ''; while (!feof($socket) && substr($response, -4) !== "\r\n") { $response .= fgets($socket, 515); }
    }

    if ($username !== '') {
        fwrite($socket, "AUTH LOGIN\r\n");
        fgets($socket, 515);
        fwrite($socket, base64_encode($username) . "\r\n");
        fgets($socket, 515);
        fwrite($socket, base64_encode($password) . "\r\n");
        $authResponse = trim(fgets($socket, 515));
        if (substr($authResponse, 0, 3) !== '235') {
            fclose($socket);
            return false;
        }
    }

    fwrite($socket, "MAIL FROM:<{$from}>\r\n");
    fgets($socket, 515);
    fwrite($socket, "RCPT TO:<{$to}>\r\n");
    $recipientResponse = trim(fgets($socket, 515));
    if (substr($recipientResponse, 0, 3) !== '250') {
        fclose($socket);
        return false;
    }
    fwrite($socket, "DATA\r\n");
    fgets($socket, 515);

    $messageData = "Subject: {$subject}\r\n" . $headers . "\r\n\r\n" . $message . "\r\n.";
    fwrite($socket, $messageData . "\r\n");
    $dataResponse = trim(fgets($socket, 515));
    fwrite($socket, "QUIT\r\n");
    fclose($socket);

    return substr($dataResponse, 0, 3) === '250';
}

function send_email_message(string $to, string $subject, string $html, string $text = ''): bool
{
    $fromName = sanitize_email_header(MAIL_FROM_NAME);
    $fromEmail = sanitize_email_header(MAIL_FROM_EMAIL);
    $to = filter_var($to, FILTER_VALIDATE_EMAIL);
    if (!$to) {
        return false;
    }

    $subject = sanitize_email_header($subject);
    // Prefer Mailer wrapper (uses PHPMailer if available)
    $mailer = new Mailer();
    $sent = $mailer->sendMail($fromEmail, $fromName, $to, $subject, $html, $text);
    if ($sent) {
        return true;
    }

    // Fallback: build raw message and attempt socket SMTP or mail()
    $messageData = build_email_message($fromName, $fromEmail, $to, $subject, $html, $text);
    if (!empty(MAIL_HOST)) {
        return smtp_send_message($to, $subject, $messageData['body'], $messageData['headers']);
    }

    return mail($to, $subject, $messageData['body'], $messageData['headers']);
}
