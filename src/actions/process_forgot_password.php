<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/send_email.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['email'])) {
    header("location: ../../public/forgot_password.php");
    exit;
}

$email = trim($_POST['email']);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error_msg'] = "Formato de e-mail inválido.";
    header("location: ../../public/forgot_password.php");
    exit;
}

$sql = "SELECT id FROM users WHERE email = ? AND status = 'active'";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $user_id = $user['id'];

        // Gera token e data de expiração (abordagem moderna com DateTime)
        $token = bin2hex(random_bytes(32));
        $expires_at_dt = new DateTime(); // Já usa o fuso de 'America/Sao_Paulo' definido no bootstrap
        $expires_at = $expires_at_dt->add(new DateInterval('PT1H'))->format('Y-m-d H:i:s');

        // Salva o token no banco de dados
        $sql_update = "UPDATE users SET confirmation_token = ?, token_expires_at = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ssi", $token, $expires_at, $user_id);
        $stmt_update->execute();

        // Envia o e-mail de redefinição
        $base_url = rtrim($_ENV['APP_URL'], '/'); // Garante que não haja barras duplicadas
        $reset_link = $base_url . "/public/reset_password.php?token=" . $token;
        $email_subject = 'Redefinição de Senha - IluminAI';
        $email_body = '
            <h2>Redefinição de Senha</h2>
            <p>Recebemos uma solicitação para redefinir sua senha. Clique no link abaixo para criar uma nova senha:</p>
            <p><a href="' . htmlspecialchars($reset_link) . '" style="padding: 10px 15px; background-color: #2563EB; color: white; text-decoration: none; border-radius: 5px;">Redefinir Senha</a></p>
            <p>Se você não consegue clicar no botão, copie e cole o seguinte link no seu navegador:</p>
            <p>' . htmlspecialchars($reset_link) . '</p>
            <p>Se você não solicitou isso, pode ignorar este e-mail.</p>
            <p>Este link expira em 1 hora.</p>
        ';

        send_email($email, $email_subject, $email_body);
    }
    $stmt->close();
}

// Por segurança, sempre mostramos a mesma mensagem, existindo o e-mail ou não.
// Isso evita que alguém descubra quais e-mails estão cadastrados.
$_SESSION['success_msg'] = "Se um e-mail correspondente for encontrado em nosso sistema, um link de recuperação de senha será enviado.";
header("location: ../../public/forgot_password.php");
exit;

$conn->close();
?>