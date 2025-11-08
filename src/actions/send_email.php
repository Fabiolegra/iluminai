<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Este arquivo não deve ser acessado diretamente, mas incluído por outros scripts.
if (basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) {
    die('Acesso direto não permitido.');
}

function send_email($to, $subject, $body) {
    // Carrega as dependências do Composer
    require_once __DIR__ . '/../../vendor/autoload.php';

    $mail = new PHPMailer(true);

    try {
        // Configurações do servidor (puxadas do .env)
        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USER'];
        $mail->Password   = $_ENV['SMTP_PASS'];
        $mail->SMTPSecure = $_ENV['SMTP_SECURE'];
        $mail->Port       = $_ENV['SMTP_PORT'];
        $mail->CharSet    = 'UTF-8';

        // Remetente e Destinatário
        $mail->setFrom($_ENV['SMTP_FROM_EMAIL'], $_ENV['SMTP_FROM_NAME']);
        $mail->addAddress($to);

        // Conteúdo
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        return $mail->send();
    } catch (Exception $e) {
        // Linha de depuração temporária: armazena o erro detalhado na sessão
        $_SESSION['debug_email_error'] = "Mailer Error: {$mail->ErrorInfo}";
        
        // Em um ambiente de produção, o ideal é logar o erro em um arquivo
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}