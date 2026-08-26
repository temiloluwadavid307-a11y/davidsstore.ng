<?php
namespace PHPMailer\PHPMailer;

use PHPMailer\PHPMailer\Exception;

class PHPMailer
{
    public bool $isSMTP = false;
    public string $Host = '';
    public bool $SMTPAuth = true;
    public string $Username = '';
    public string $Password = '';
    public string $SMTPSecure = 'tls';
    public int $Port = 587;
    public string $CharSet = 'UTF-8';
    public bool $SMTPAutoTLS = true;
    public array $SMTPOptions = [];
    public string $From = '';
    public string $FromName = '';
    public string $Sender = '';
    public string $Subject = '';
    public string $Body = '';
    public string $AltBody = '';
    public bool $SMTPDebug = false;
    private array $to = [];
    private array $replyTo = [];

    public function isSMTP(): void
    {
        $this->isSMTP = true;
    }

    public function setFrom(string $address, string $name = ''): void
    {
        $this->From = $address;
        $this->FromName = $name;
    }

    public function addAddress(string $address, string $name = ''): void
    {
        $this->to[] = ['address' => $address, 'name' => $name];
    }

    public function addReplyTo(string $address, string $name = ''): void
    {
        $this->replyTo[] = ['address' => $address, 'name' => $name];
    }

    public function isHTML(bool $isHtml = true): void
    {
        // Body is already HTML when calling send().
    }

    public function send(): bool
    {
        $to = $this->to[0]['address'] ?? '';
        if (!$to) {
            throw new Exception('No recipient specified');
        }
        $from = $this->From;
        $fromName = $this->FromName;
        $subject = $this->Subject;
        $html = $this->Body;
        $text = $this->AltBody;

        $mailer = new Mailer();
        return $mailer->sendMail($from, $fromName, $to, $subject, $html, $text);
    }
}
