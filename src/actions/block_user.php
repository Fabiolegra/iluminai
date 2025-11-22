<?php
require_once __DIR__ . '/../../bootstrap.php';

// Proteção: Apenas administradores logados
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    $_SESSION['error_msg'] = "Acesso negado.";
    header("location: ../../public/index.php");
    exit;
}

// Validação do token CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error_msg'] = "Token inválido.";
    header("location: ../../public/index.php");
    exit;
}

require_once __DIR__ . '/../../config/database.php';

$user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
$reason = filter_input(INPUT_POST, 'reason', FILTER_SANITIZE_STRING);

if (!$user_id || !in_array($action, ['block', 'unblock'])) {
    $_SESSION['error_msg'] = "Dados inválidos.";
    header("location: ../../public/manage_users.php");
    exit;
}

// Evita que o admin bloqueie a si mesmo
if ($user_id === $_SESSION['id']) {
    $_SESSION['error_msg'] = "Você não pode bloquear sua própria conta.";
    header("location: ../../public/manage_users.php");
    exit;
}

// Atualiza o status do usuário
$new_status = $action === 'block' ? 'blocked' : 'active';

$stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
$stmt->bind_param("si", $new_status, $user_id);

if ($stmt->execute()) {
    $action_text = $action === 'block' ? 'bloqueado' : 'reativado';
    $_SESSION['success_msg'] = "Usuário $action_text com sucesso.";
} else {
    $_SESSION['error_msg'] = "Erro ao atualizar o usuário.";
}

$stmt->close();
$conn->close();

// Retorna para a página anterior ou para manage_users
$referer = $_SERVER['HTTP_REFERER'] ?? '../../public/manage_users.php';
header("location: $referer");
exit;
?>
