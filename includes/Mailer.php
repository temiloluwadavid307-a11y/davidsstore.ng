<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer {
    public function sendMail(string $from, string $fromName, string $to, string $subject, string $html, string $text = ''): bool
    {
        $to = filter_var($to, FILTER_VALIDATE_EMAIL);
        if (!$to) {
            error_log("Mailer: Invalid recipient email: $to");
            return false;
        }

        if (empty(MAIL_HOST) || empty(MAIL_USERNAME) || empty(MAIL_PASSWORD)) {
            error_log('Mailer: SMTP configuration is incomplete.');
            return false;
        }

        $mailer = new PHPMailer();
        $mailer->isSMTP();
        $mailer->Host = MAIL_HOST;
        $mailer->SMTPAuth = true;
        $mailer->Username = MAIL_USERNAME;
        $mailer->Password = MAIL_PASSWORD;
        $mailer->SMTPSecure = MAIL_ENCRYPTION;
        $mailer->Port = (int) MAIL_PORT;
        $mailer->CharSet = 'UTF-8';
        $mailer->setFrom($from, $fromName);
        $mailer->addAddress($to);
        $mailer->addReplyTo($from, $fromName);
        $mailer->Subject = $subject;
        $mailer->Body = $html;
        $mailer->AltBody = $text;

        try {
            return $mailer->send();
        } catch (Exception $ex) {
            error_log('Mailer: send failed: ' . $ex->getMessage());
            return false;
        }
    }
}
