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

// Busca todas as ocorrências
$result = $conn->query("SELECT id, user_id, tipo, descricao, latitude, longitude, status FROM ocorrencias");
$ocorrencias = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($ocorrencias);

$conn->close();
?>