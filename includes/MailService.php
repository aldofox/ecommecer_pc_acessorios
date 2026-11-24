<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailException;

class MailService
{
    /**
     * Envia um e-mail HTML.
     *
     * @param string $toEmail  E-mail do destinatário
     * @param string $toName   Nome do destinatário
     * @param string $subject  Assunto
     * @param string $htmlBody Corpo HTML
     * @param string $textBody Versão texto puro (fallback)
     * @return bool
     * @throws \RuntimeException
     */
    public static function send(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $textBody = ''
    ): bool {
        $cfg = require __DIR__ . '/../config/mail.php';

        $mail = new PHPMailer(true);

        try {
            // Servidor SMTP
            $mail->isSMTP();
            $mail->Host        = $cfg['host'];
            $mail->SMTPAuth    = true;
            $mail->Username    = $cfg['username'];
            $mail->Password    = $cfg['password'];
            $mail->SMTPSecure  = $cfg['encryption'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port        = $cfg['port'];
            $mail->SMTPDebug   = $cfg['debug'];
            $mail->CharSet     = PHPMailer::CHARSET_UTF8;

            // Remetente
            $mail->setFrom($cfg['from_email'], $cfg['from_name']);
            $mail->addReplyTo($cfg['from_email'], $cfg['from_name']);

            // Destinatário
            $mail->addAddress($toEmail, $toName);

            // Conteúdo
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = $textBody ?: strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

            $mail->send();
            return true;

        } catch (MailException $e) {
            throw new \RuntimeException('Falha no envio de e-mail: ' . $mail->ErrorInfo);
        }
    }
}
