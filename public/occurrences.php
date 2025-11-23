<?php
// Define o cabeçalho como JSON
header('Content-Type: application/json');

// Inicia a sessão para verificar a autenticação
require_once __DIR__ . '/../bootstrap.php';

// Protege o endpoint: apenas usuários logados podem acessar
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Acesso não autorizado.']);
    exit;
}

// Inclui o arquivo de configuração do banco de dados
require_once __DIR__ . '/../config/database.php';

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['tipo'];
$filter = filter_input(INPUT_GET, 'filter', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? 'all';

// Se for operador
if ($user_type === 'operador') {
    // Se filtro for 'mine', mostra apenas suas ocorrências
    if ($filter === 'mine') {
        $sql = "SELECT o.id, o.user_id, o.operador_id, o.tipo, o.descricao, o.latitude, o.longitude, o.status
                FROM ocorrencias o
                WHERE o.operador_id = ?
                ORDER BY o.created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
    } else {
        // Se filtro for 'all', mostra todas
        $sql = "SELECT id, user_id, operador_id, tipo, descricao, latitude, longitude, status FROM ocorrencias";
        $stmt = null;
    }
} else {
    // Se for admin ou usuário, sempre mostra todas
    $sql = "SELECT id, user_id, operador_id, tipo, descricao, latitude, longitude, status FROM ocorrencias";
    $stmt = null;
}

// Executa a query
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $ocorrencias = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $result = $conn->query($sql);
    $ocorrencias = $result->fetch_all(MYSQLI_ASSOC);
}

echo json_encode($ocorrencias);

$conn->close();
?>