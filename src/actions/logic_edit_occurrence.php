<?php
// Carrega o bootstrap da aplicação (autoloader, .env, sessão)
require_once __DIR__ . '/../../bootstrap.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Valida o ID da ocorrência na URL
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    header("location: index.php");
    exit;
}
$ocorrencia_id = intval($_GET['id']);

require_once __DIR__ . '/../../config/database.php';

// Busca os dados da ocorrência
$sql = "SELECT * FROM ocorrencias WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $ocorrencia_id);
$stmt->execute();
$result = $stmt->get_result();
$ocorrencia = $result->fetch_assoc();
$stmt->close();

// Verificação de permissão
$is_owner = ($ocorrencia['user_id'] === $_SESSION['user_id']);
$is_admin = ($_SESSION['tipo'] === 'admin');
$is_pending = ($ocorrencia['status'] === 'pendente');

if (!$ocorrencia || !($is_admin || ($is_owner && $is_pending))) {
    $_SESSION['error_msg'] = "Você não tem permissão para editar esta ocorrência ou ela não está mais pendente.";
    header("location: my_occurrence.php");
    exit;
}

$fotos = array_filter([$ocorrencia['foto1'], $ocorrencia['foto2'], $ocorrencia['foto3']]);

?>