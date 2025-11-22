<?php
require_once __DIR__ . '/../../bootstrap.php';

// Proteção: Apenas usuários logados
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    $_SESSION['error_msg'] = "Acesso negado.";
    header("location: login.php");
    exit;
}

require_once __DIR__ . '/../../config/database.php';

$user_id = $_SESSION['user_id'];

// Busca todos os avisos do usuário
$sql = "SELECT a.id, a.assunto, a.mensagem, a.lido, a.created_at, u.nome as admin_nome
        FROM avisos a
        JOIN users u ON a.admin_id = u.id
        WHERE a.user_id = ?
        ORDER BY a.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$avisos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Busca o número de avisos não lidos
$sql_unread = "SELECT COUNT(*) as total FROM avisos WHERE user_id = ? AND lido = FALSE";
$stmt_unread = $conn->prepare($sql_unread);
$stmt_unread->bind_param("i", $user_id);
$stmt_unread->execute();
$unread = $stmt_unread->get_result()->fetch_assoc();
$stmt_unread->close();

$avisos_nao_lidos = $unread['total'];

// Se houver um aviso para marcar como lido
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $aviso_id = filter_input(INPUT_GET, 'mark_read', FILTER_VALIDATE_INT);
    
    $stmt_update = $conn->prepare("UPDATE avisos SET lido = TRUE WHERE id = ? AND user_id = ?");
    $stmt_update->bind_param("ii", $aviso_id, $user_id);
    $stmt_update->execute();
    $stmt_update->close();
    
    header("location: notices.php");
    exit;
}
?>
