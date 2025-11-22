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
$stmt_user = $conn->prepare("SELECT id, nome, email, foto_perfil, tipo FROM users WHERE id = ?");
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

?>