<?php

namespace App\Services;

use CodeIgniter\Email\Email;

class EmailService
{
    protected Email $email;

    public function __construct()
    {
        $this->email = \Config\Services::email();

        $this->email->initialize([
            'protocol'  => EMAIL_PROTOCOL,
            'SMTPHost'  => EMAIL_SMTP_HOST,
            'SMTPPort'  => EMAIL_SMTP_PORT,
            'SMTPUser'  => EMAIL_SMTP_USER,
            'SMTPPass'  => EMAIL_SMTP_PASS,
            'SMTPCrypto' => EMAIL_SMTP_CRYPTO,
            'mailType'  => 'html',
            'charset'   => 'utf-8',
            'newline'   => "\r\n",
        ]);
    }

    public function send_reset_password(string $to, string $nome, string $link): bool
    {
        $this->email->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);
        $this->email->setTo($to);
        $this->email->setSubject('Recuperação de senha');
        $this->email->setMessage($this->_template_reset_password($nome, $link));

        return $this->email->send();
    }

    private function _template_reset_password(string $nome, string $link): string
    {
        return "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2>Recuperação de senha</h2>
                <p>Olá, <strong>{$nome}</strong>!</p>
                <p>Recebemos uma solicitação para redefinir a senha da sua conta.</p>
                <p>Clique no botão abaixo para criar uma nova senha. O link expira em <strong>1 hora</strong>.</p>
                <a href='{$link}' 
                   style='display: inline-block; padding: 12px 24px; background-color: #2d6cf5;
                          color: #fff; text-decoration: none; border-radius: 4px; margin: 16px 0;'>
                    Redefinir senha
                </a>
                <p>Se não foi você quem solicitou, ignore este email. Sua senha permanece a mesma.</p>
                <hr>
                <small style='color: #999;'>Este link é válido por 1 hora e pode ser usado apenas uma vez.</small>
            </div>
        ";
    }



    // Send email candidate info
    public function send_mail_info(string $to, string $nome, string $subject): bool
    {
        $this->email->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);
        $this->email->setTo($to);
        $this->email->setSubject($subject);
        $this->email->setMessage($this->_template_candidatura_info($nome, $subject));

        return $this->email->send();
    }

    private function _template_candidatura_info(string $nome, string $body)
    {
        return "
        <div>
                <h2>Informações</h2>
                <p>Olá, <strong>{$nome}</strong>!</p>
                <p>Recebemos seu comprovante de pagamento e teve o seguinte parecer: 
                <strong>{$body}</strong>.</p>
        </div>
        ";
    }
}
