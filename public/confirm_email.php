<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../config/database.php';

$token = $_GET['token'] ?? '';
$message = '';
$is_success = false;

if (empty($token)) {
    $message = "Token de confirmação não fornecido.";
} else {
    $sql = "SELECT id, token_expires_at FROM users WHERE confirmation_token = ? AND status = 'pending'";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (strtotime($user['token_expires_at']) > time()) {
                // Token válido e não expirado, ativa o usuário
                $sql_update = "UPDATE users SET status = 'active', confirmation_token = NULL, token_expires_at = NULL WHERE id = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("i", $user['id']);
                $stmt_update->execute();
                
                $_SESSION['success_msg'] = "Sua conta foi ativada com sucesso! Você já pode fazer login.";
                header("location: login.php");
                exit;
            } else {
                $message = "Este link de confirmação expirou. Por favor, solicite um novo.";
            }
        } else {
            $message = "Token de confirmação inválido ou a conta já foi ativada.";
        }
        $stmt->close();
    }
}

// Se chegou até aqui, algo deu errado. Exibe a mensagem.
if (!empty($message)) {
    $_SESSION['error_msg'] = $message;
    header("location: login.php");
    exit;
}

$conn->close();
?>