<?php
require_once __DIR__ . '/../../bootstrap.php';

// Proteção: Apenas administradores logados
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

// Busca todos os usuários que não são admins
$sql = "SELECT id, nome, email FROM users WHERE tipo != 'admin' ORDER BY nome";
$stmt = $conn->prepare($sql);
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

header('Content-Type: application/json');
echo json_encode($users);
?>
