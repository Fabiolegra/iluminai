<?php
// Carrega o bootstrap da aplicação (autoloader, .env, sessão)
require_once __DIR__ . '/../../bootstrap.php';

// Protege a página: só usuários logados podem acessar
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Inclui o arquivo de configuração do banco de dados
require_once __DIR__ . '/../../config/database.php';

$user_id = $_SESSION['user_id'];
$is_admin = ($_SESSION['tipo'] === 'admin');
$is_operator = ($_SESSION['tipo'] === 'operador');
$ocorrencias = [];

if ($is_admin) {
    // Para Admins: Busca todas as ocorrências com comentários, ordenadas pela atividade mais recente.
    $sql = "SELECT o.id, o.tipo, o.status, o.created_at, u.nome as user_nome,
            (SELECT COUNT(c.id) FROM comentarios c LEFT JOIN comentarios_visualizacao cv ON c.ocorrencia_id = cv.ocorrencia_id AND cv.user_id = ? WHERE c.ocorrencia_id = o.id AND c.user_id != ? AND (cv.last_seen_at IS NULL OR c.created_at > cv.last_seen_at)) as unread_count,
            (SELECT MAX(c.created_at) FROM comentarios c WHERE c.ocorrencia_id = o.id) as last_comment_at
            FROM ocorrencias o
            JOIN users u ON o.user_id = u.id
            WHERE EXISTS (SELECT 1 FROM comentarios c WHERE c.ocorrencia_id = o.id)
            ORDER BY unread_count DESC, last_comment_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $user_id);
} elseif ($is_operator) {
    // Para Operadores: Busca as ocorrências atribuídas a ele.
    $sql = "SELECT o.id, o.tipo, o.status, o.created_at, u.nome as user_nome,
            (SELECT COUNT(c.id) FROM comentarios c LEFT JOIN comentarios_visualizacao cv ON c.ocorrencia_id = cv.ocorrencia_id AND cv.user_id = ? WHERE c.ocorrencia_id = o.id AND c.user_id != ?) as unread_count
            FROM ocorrencias o
            JOIN users u ON o.user_id = u.id
            WHERE o.operador_id = ?
            ORDER BY unread_count DESC, o.updated_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $user_id, $user_id, $user_id);
} else {
    // Para Usuários Comuns: Busca suas próprias ocorrências, com contagem de mensagens não lidas.
    $sql = "SELECT o.id, o.tipo, o.status, o.created_at,
            (SELECT COUNT(c.id) FROM comentarios c LEFT JOIN comentarios_visualizacao cv ON c.ocorrencia_id = cv.ocorrencia_id AND cv.user_id = ? WHERE c.ocorrencia_id = o.id AND c.user_id != ?) as unread_count
            FROM ocorrencias o
            WHERE o.user_id = ?
            ORDER BY unread_count DESC, o.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $user_id, $user_id, $user_id);
}

if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $ocorrencias = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Mapeamento de status para cores do Tailwind CSS
$status_colors = [
    'pendente' => 'bg-yellow-500/20 text-yellow-400',
    'em andamento' => 'bg-orange-500/20 text-orange-400',
    'resolvido' => 'bg-green-500/20 text-green-400',
];
?>