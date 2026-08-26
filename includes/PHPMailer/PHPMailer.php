<?php
namespace PHPMailer\PHPMailer;

use PHPMailer\PHPMailer\Exception;

class PHPMailer {
    public $SMTPDebug = 0;
    public $isSMTP = false;
    public $Host = '';
    public $SMTPAuth = true;
    public $Username = '';
    public $Password = '';
    public $SMTPSecure = '';
    public $Port = 587;
    public $CharSet = 'UTF-8';
    public $SMTPAutoTLS = true;
    public $SMTPOptions = [];
    public $From = '';
    public $FromName = '';
    public $Sender = '';
    public $Subject = '';
    public $Body = '';
    public $AltBody = '';
    public $WordWrap = 0;
    private $addresses = [];
    private $replyTo = [];

    public function isSMTP() {
        $this->isSMTP = true;
    }

    public function setFrom(string $address, string $name = ''): void {
        $this->From = $address;
        $this->FromName = $name;
    }

    public function addAddress(string $address, string $name = ''): void {
        $this->addresses[] = ['address' => $address, 'name' => $name];
    }

    public function addReplyTo(string $address, string $name = ''): void {
        $this->replyTo[] = ['address' => $address, 'name' => $name];
    }

    public function isHTML(bool $isHtml = true): void {
        // handled by the message body format
    }

    private function formatAddress(array $recipient): string
    {
        return trim($recipient['name'] !== '' ? $recipient['name'] . ' <' . $recipient['address'] . '>' : $recipient['address']);
    }

    private function readSmtpLines($socket): string
    {
        $response = '';
        while (!feof($socket)) {
            $line = fgets($socket, 515);
            if ($line === false) {
                break;
            }
            $response .= $line;
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        return $response;
    }

    private function assertSmtpResponse($socket, string $expected): void
    {
        $response = trim($this->readSmtpLines($socket));
        if (substr($response, 0, 3) !== $expected) {
            throw new Exception('SMTP error: ' . $response);
        }
    }

    public function send(): bool
    {
        if (!$this->isSMTP) {
            throw new Exception('Only SMTP transport is supported by this mailer stub.');
        }

        $toRecipient = $this->addresses[0]['address'] ?? '';
        if (empty($toRecipient)) {
            throw new Exception('No recipient provided.');
        }

        $fromEmail = $this->From;
        if (empty($fromEmail)) {
            throw new Exception('No From address provided.');
        }

        $fromName = $this->FromName ?: $fromEmail;
        $replyTo = $this->replyTo[0]['address'] ?? $fromEmail;
        $replyName = $this->replyTo[0]['name'] ?? $fromName;
        $subject = $this->Subject;
        $plainText = $this->AltBody !== '' ? $this->AltBody : strip_tags(str_replace(['<br>', '<br/>', '<br />', '<p>'], "\r\n", $this->Body));
        $boundary = '==boundary_' . bin2hex(random_bytes(12));

        $messageBody = "--{$boundary}\r\n";
        $messageBody .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $messageBody .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $messageBody .= $plainText . "\r\n\r\n";
        $messageBody .= "--{$boundary}\r\n";
        $messageBody .= "Content-Type: text/html; charset=UTF-8\r\n";
        $messageBody .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $messageBody .= $this->Body . "\r\n\r\n";
        $messageBody .= "--{$boundary}--\r\n";

        $headers = [];
        $headers[] = 'From: ' . $this->formatAddress(['address' => $fromEmail, 'name' => $fromName]);
        $headers[] = 'To: ' . $this->formatAddress(['address' => $toRecipient, 'name' => $this->addresses[0]['name']]);
        $headers[] = 'Reply-To: ' . $this->formatAddress(['address' => $replyTo, 'name' => $replyName]);
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
        $headers[] = 'Date: ' . date('r');
        $headers[] = 'X-Mailer: PHP/' . phpversion();

        $payload = "Subject: {$subject}\r\n" . implode("\r\n", $headers) . "\r\n\r\n" . $messageBody;

        $remote = strtolower($this->SMTPSecure) === 'ssl'
            ? 'ssl://' . $this->Host . ':' . $this->Port
            : $this->Host . ':' . $this->Port;

        $socket = stream_socket_client($remote, $errno, $errstr, 20, STREAM_CLIENT_CONNECT);
        if (!$socket) {
            throw new Exception('SMTP connection failed: [' . $errno . '] ' . $errstr);
        }

        $response = trim(fgets($socket, 515));
        if (substr($response, 0, 3) !== '220') {
            fclose($socket);
            throw new Exception('SMTP greeting failed: ' . $response);
        }

        $hostname = preg_replace('/[\r\n]/', '', gethostname() ?: 'localhost');
        fwrite($socket, "EHLO {$hostname}\r\n");
        $this->assertSmtpResponse($socket, '250');

        if (strtolower($this->SMTPSecure) === 'tls') {
            fwrite($socket, "STARTTLS\r\n");
            $this->assertSmtpResponse($socket, '220');
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT)) {
                fclose($socket);
                throw new Exception('Failed to enable TLS on SMTP socket.');
            }
            fwrite($socket, "EHLO {$hostname}\r\n");
            $this->assertSmtpResponse($socket, '250');
        }

        if ($this->SMTPAuth && $this->Username !== '') {
            fwrite($socket, "AUTH LOGIN\r\n");
            $this->assertSmtpResponse($socket, '334');
            fwrite($socket, base64_encode($this->Username) . "\r\n");
            $this->assertSmtpResponse($socket, '334');
            fwrite($socket, base64_encode($this->Password) . "\r\n");
            $this->assertSmtpResponse($socket, '235');
        }

        fwrite($socket, "MAIL FROM:<{$fromEmail}>\r\n");
        $this->assertSmtpResponse($socket, '250');
        fwrite($socket, "RCPT TO:<{$toRecipient}>\r\n");
        $this->assertSmtpResponse($socket, '250');
        fwrite($socket, "DATA\r\n");
        $this->assertSmtpResponse($socket, '354');

        fwrite($socket, str_replace("\r\n.\r\n", "\r\n..\r\n", $payload) . "\r\n.\r\n");
        $this->assertSmtpResponse($socket, '250');
        fwrite($socket, "QUIT\r\n");
        fclose($socket);

        return true;
    }
}
