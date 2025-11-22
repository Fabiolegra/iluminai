<?php
require_once __DIR__ . '/../../bootstrap.php';

// 1. Proteção da página: Apenas administradores logados
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    $_SESSION['error_msg'] = "Acesso negado.";
    header("location: index.php");
    exit;
}

$user_id_to_view = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$user_id_to_view) {
    $_SESSION['error_msg'] = "Usuário não encontrado.";
    header("location: manage_users.php");
    exit;
}

require_once __DIR__ . '/../../config/database.php';

// 2. Busca os dados do usuário a ser visualizado
$stmt_user = $conn->prepare("SELECT id, nome, email, foto_perfil, tipo, status, created_at FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id_to_view);
$stmt_user->execute();
$result_user = $stmt_user->get_result();

if ($result_user->num_rows === 0) {
    $_SESSION['error_msg'] = "Usuário não encontrado.";
    header("location: manage_users.php");
    exit;
}
$user = $result_user->fetch_assoc();
$stmt_user->close();

$user_avatar = $user['foto_perfil'] ? htmlspecialchars($user['foto_perfil']) : 'https://ui-avatars.com/api/?name=' . urlencode($user['nome']) . '&background=1f2937&color=d1d5db&size=128';

// 3. Busca as ocorrências relacionadas ao usuário
$ocorrencias = [];
if ($user['tipo'] === 'operador') {
    // Se for operador, busca as ocorrências atribuídas a ele
    $sql_ocorrencias = "SELECT o.id, o.tipo, o.status, o.created_at, u.nome as user_nome
                        FROM ocorrencias o
                        JOIN users u ON o.user_id = u.id
                        WHERE o.operador_id = ?
                        ORDER BY o.created_at DESC";
} else {
    // Se for admin ou outro tipo, busca as ocorrências criadas por ele
    $sql_ocorrencias = "SELECT id, tipo, status, created_at
                        FROM ocorrencias
                        WHERE user_id = ?
                        ORDER BY created_at DESC";
}

$stmt_ocorrencias = $conn->prepare($sql_ocorrencias);
$stmt_ocorrencias->bind_param("i", $user_id_to_view);
$stmt_ocorrencias->execute();
$ocorrencias = $stmt_ocorrencias->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_ocorrencias->close();

$status_colors = [
    'pendente' => 'bg-yellow-500/20 text-yellow-400',
    'em andamento' => 'bg-orange-500/20 text-orange-400',
    'resolvido' => 'bg-green-500/20 text-green-400',
];

// 4. Calcula estatísticas do usuário
$stats = [
    'total' => 0,
    'abertas' => 0,
    'resolvidas' => 0,
    'tempo_medio_resposta_sec' => null,
    'tempo_medio_resolucao_sec' => null
];

if ($user['tipo'] === 'operador') {
    // Estatísticas para operadores (ocorrências atribuídas)
    $sql_stats = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status IN ('pendente', 'em andamento') THEN 1 ELSE 0 END) as abertas,
                    SUM(CASE WHEN status = 'resolvido' THEN 1 ELSE 0 END) as resolvidas
                  FROM ocorrencias
                  WHERE operador_id = ?";
} else {
    // Estatísticas para usuários comuns (ocorrências criadas)
    $sql_stats = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status IN ('pendente', 'em andamento') THEN 1 ELSE 0 END) as abertas,
                    SUM(CASE WHEN status = 'resolvido' THEN 1 ELSE 0 END) as resolvidas
                  FROM ocorrencias
                  WHERE user_id = ?";
}

$stmt_stats = $conn->prepare($sql_stats);
$stmt_stats->bind_param("i", $user_id_to_view);
$stmt_stats->execute();
$result_stats = $stmt_stats->get_result();
$stats_raw = $result_stats->fetch_assoc();
$stmt_stats->close();

if ($stats_raw) {
    $stats['total'] = (int)$stats_raw['total'];
    $stats['abertas'] = (int)($stats_raw['abertas'] ?? 0);
    $stats['resolvidas'] = (int)($stats_raw['resolvidas'] ?? 0);
}

// 5. Dados para o gráfico de tendência (últimos 30 dias)
$spark_labels = [];
$spark_data = [];

for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $spark_labels[] = date('d/m', strtotime($date));
}

if ($user['tipo'] === 'operador') {
    $sql_spark = "SELECT DATE(created_at) as data, COUNT(*) as quantidade
                  FROM ocorrencias
                  WHERE operador_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                  GROUP BY DATE(created_at)
                  ORDER BY data";
} else {
    $sql_spark = "SELECT DATE(created_at) as data, COUNT(*) as quantidade
                  FROM ocorrencias
                  WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                  GROUP BY DATE(created_at)
                  ORDER BY data";
}

$stmt_spark = $conn->prepare($sql_spark);
$stmt_spark->bind_param("i", $user_id_to_view);
$stmt_spark->execute();
$result_spark = $stmt_spark->get_result();

$spark_data_map = [];
while ($row = $result_spark->fetch_assoc()) {
    $spark_data_map[$row['data']] = (int)$row['quantidade'];
}
$stmt_spark->close();

// Preenche os dados do gráfico
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $spark_data[] = $spark_data_map[$date] ?? 0;
}

// 6. Busca operadores (caso seja necessário)
$operators = [];
$stmt_operators = $conn->prepare("SELECT id, nome FROM users WHERE tipo = 'operador' AND id != ? AND status = 'active'");
$stmt_operators->bind_param("i", $user_id_to_view);
$stmt_operators->execute();
$operators = $stmt_operators->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_operators->close();

// 7. Token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

?>