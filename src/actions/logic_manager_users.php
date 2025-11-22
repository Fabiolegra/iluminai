<?php
// Carrega o bootstrap da aplicação (autoloader, .env, sessão)
require_once __DIR__ . '/../../bootstrap.php';

// 1. Proteção da página: Apenas administradores logados
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    $_SESSION['error_msg'] = "Acesso negado. Você não tem permissão para acessar esta página.";
    header("location: index.php");
    exit;
}

require_once __DIR__ . '/../../config/database.php';

// Captura e validação dos filtros
$filtro_tipo = $_GET['tipo'] ?? '';
$filtro_email = trim($_GET['email'] ?? '');
$allowed_types = ['admin', 'operador', 'usuario'];

$where_clauses = [];
$params = [];
$types = '';

if (!empty($filtro_tipo) && in_array($filtro_tipo, $allowed_types)) {
    $where_clauses[] = "tipo = ?";
    $params[] = $filtro_tipo;
    $types .= 's';
}

if (!empty($filtro_email)) {
    $where_clauses[] = "email LIKE ?";
    $params[] = "%" . $filtro_email . "%";
    $types .= 's';
}

$where_sql = !empty($where_clauses) ? " WHERE " . implode(" AND ", $where_clauses) : '';

$sql_users = "SELECT id, nome, email, tipo, created_at FROM users" . $where_sql . " ORDER BY FIELD(tipo, 'admin', 'operador', 'usuario'), nome";
$stmt = $conn->prepare($sql_users);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

?>