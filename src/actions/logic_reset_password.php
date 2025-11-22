<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../config/database.php';

$token = $_GET['token'] ?? '';

// 1. Valida o token
if (empty($token)) {
    $_SESSION['error_msg'] = "Token de redefinição inválido ou não fornecido.";
    header("location: login.php");
    exit;
}

$sql = "SELECT id, token_expires_at FROM users WHERE confirmation_token = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

// Validação do token e da expiração com tratamento de fuso horário
$user = $result->fetch_assoc();
$is_invalid = true; // Assume que é inválido por padrão
if ($user) {
    $expires_at = new DateTime($user['token_expires_at'], new DateTimeZone('America/Sao_Paulo'));
    $now = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
    if ($expires_at > $now) $is_invalid = false;
}
if ($is_invalid) {
    $_SESSION['error_msg'] = "Token inválido ou expirado. Por favor, solicite um novo link de redefinição.";
    header("location: forgot_password.php");
    exit;
}
$stmt->close();

// 2. Processa o formulário de nova senha
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];

    if (empty($senha) || $senha !== $confirmar_senha) {
        $_SESSION['error_msg'] = "As senhas não coincidem ou estão vazias.";
    } else {
        // Atualiza a senha e limpa o token
        $hashed_password = password_hash($senha, PASSWORD_DEFAULT);
        $sql_update = "UPDATE users SET senha = ?, confirmation_token = NULL, token_expires_at = NULL WHERE confirmation_token = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ss", $hashed_password, $token);
        
        if ($stmt_update->execute()) {
            $_SESSION['success_msg'] = "Sua senha foi redefinida com sucesso! Você já pode fazer login com a nova senha.";
            header("location: login.php");
            exit;
        } else {
            $_SESSION['error_msg'] = "Ocorreu um erro ao atualizar sua senha. Tente novamente.";
        }
        $stmt_update->close();
    }
    // Recarrega a página para mostrar o erro
    header("Location: reset_password.php?token=" . htmlspecialchars($token));
    exit;
}

$conn->close();
?>