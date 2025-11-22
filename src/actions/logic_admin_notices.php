<?php
require_once __DIR__ . '/../../bootstrap.php';

// Proteção: Apenas administradores logados
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    $_SESSION['error_msg'] = "Acesso negado.";
    header("location: index.php");
    exit;
}

require_once __DIR__ . '/../../config/database.php';

$admin_id = $_SESSION['user_id'];

// Busca todos os avisos enviados pelo admin
$sql = "SELECT a.id, a.assunto, a.mensagem, a.lido, a.created_at, u.nome as user_nome, u.email as user_email
        FROM avisos a
        JOIN users u ON a.user_id = u.id
        WHERE a.admin_id = ?
        ORDER BY a.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$avisos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Estatísticas dos avisos
$sql_stats = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN lido = FALSE THEN 1 ELSE 0 END) as nao_lidos,
                SUM(CASE WHEN lido = TRUE THEN 1 ELSE 0 END) as lidos
              FROM avisos
              WHERE admin_id = ?";

$stmt_stats = $conn->prepare($sql_stats);
$stmt_stats->bind_param("i", $admin_id);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();
$stmt_stats->close();

// Token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>

