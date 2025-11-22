<?php
require_once __DIR__ . '/../../bootstrap.php';

// 1. Proteção: Apenas administradores logados podem executar esta ação
if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['tipo'] !== 'admin') {
    $_SESSION['error_msg'] = "Acesso não autorizado.";
    header("location: ../../public/index.php");
    exit;
}

require_once __DIR__ . '/../../config/database.php';

$user_id_to_delete = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

if (!$user_id_to_delete) {
    $_SESSION['error_msg'] = "ID de usuário inválido.";
    header("location: ../../public/manage_users.php");
    exit;
}

if ($user_id_to_delete === $_SESSION['user_id']) {
    $_SESSION['error_msg'] = "Você não pode excluir sua própria conta.";
    header("location: ../../public/manage_users.php");
    exit;
}

// Inicia uma transação para garantir a integridade dos dados
$conn->begin_transaction();

try {
    // 2. Desvincula as ocorrências do operador que será excluído
    $sql_unassign = "UPDATE ocorrencias SET operador_id = NULL WHERE operador_id = ?";
    $stmt_unassign = $conn->prepare($sql_unassign);
    $stmt_unassign->bind_param("i", $user_id_to_delete);
    $stmt_unassign->execute();
    $stmt_unassign->close();

    // 3. Exclui o usuário (apenas se for um operador)
    $sql_delete = "DELETE FROM users WHERE id = ? AND tipo = 'operador'";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $user_id_to_delete);
    $stmt_delete->execute();

    $_SESSION['success_msg'] = "Operador excluído com sucesso.";
    $conn->commit();
} catch (mysqli_sql_exception $exception) {
    $conn->rollback();
    $_SESSION['error_msg'] = "Erro ao excluir o operador: " . $exception->getMessage();
}

$conn->close();
header("location: ../../public/manage_users.php");
exit;