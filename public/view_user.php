<?php
require_once __DIR__ . '/../bootstrap.php';

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

require_once __DIR__ . '/../config/database.php';

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
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de <?php echo htmlspecialchars($user['nome']); ?> - IluminAI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-300">
    <?php require_once 'templates/header.php'; ?>

    <main class="py-10">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Card de Perfil -->
            <div class="bg-gray-800 border border-gray-700 p-6 rounded-lg shadow-lg text-center mb-8">
                <img class="w-32 h-32 rounded-full mx-auto mb-4 object-cover border-4 border-gray-700" src="<?php echo $user_avatar; ?>" alt="Foto de Perfil">
                <h1 class="text-3xl font-bold text-gray-100"><?php echo htmlspecialchars($user['nome']); ?></h1>
                <p class="text-sm text-gray-400 mb-2"><?php echo htmlspecialchars($user['email']); ?></p>
                <span class="px-3 py-1 text-xs font-semibold rounded-full <?php echo $user['tipo'] === 'admin' ? 'bg-blue-500/20 text-blue-400' : 'bg-yellow-500/20 text-yellow-400'; ?>">
                    <?php echo htmlspecialchars(ucfirst($user['tipo'])); ?>
                </span>
            </div>

            <!-- Lista de Ocorrências -->
            <h2 class="text-2xl font-bold text-gray-100 mb-6">
                <?php echo $user['tipo'] === 'operador' ? 'Ocorrências Atribuídas' : 'Ocorrências Criadas'; ?>
            </h2>

            <div class="bg-gray-800 border border-gray-700 shadow-lg rounded-lg overflow-hidden">
                <div class="divide-y divide-gray-700">
                    <?php if (empty($ocorrencias)): ?>
                        <p class="p-6 text-center text-gray-400">Nenhuma ocorrência encontrada para este usuário.</p>
                    <?php else: ?>
                        <?php foreach ($ocorrencias as $ocorrencia): ?>
                            <div class="p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between hover:bg-gray-700/50 transition-colors">
                                <div class="flex-grow mb-4 sm:mb-0">
                                    <div class="flex items-center gap-3">
                                        <span class="px-3 py-1 text-xs font-semibold rounded-full <?php echo $status_colors[$ocorrencia['status']] ?? 'bg-gray-700 text-gray-200'; ?>">
                                            <?php echo htmlspecialchars(ucfirst($ocorrencia['status'])); ?>
                                        </span>
                                        <a href="details.php?id=<?php echo $ocorrencia['id']; ?>" class="text-lg font-semibold text-gray-100 capitalize hover:underline">
                                            <?php echo htmlspecialchars($ocorrencia['tipo']); ?> (#<?php echo $ocorrencia['id']; ?>)
                                        </a>
                                    </div>
                                    <p class="text-sm text-gray-400 mt-1 ml-1">
                                        <?php if ($user['tipo'] === 'operador'): ?>
                                            Reportado por <strong><?php echo htmlspecialchars($ocorrencia['user_nome']); ?></strong> em <?php echo date('d/m/Y', strtotime($ocorrencia['created_at'])); ?>
                                        <?php else: ?>
                                            Reportado em: <?php echo date('d/m/Y \à\s H:i', strtotime($ocorrencia['created_at'])); ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="flex-shrink-0">
                                    <a href="details.php?id=<?php echo $ocorrencia['id']; ?>" class="w-full sm:w-auto text-center bg-gray-700 hover:bg-gray-600 text-gray-200 font-semibold py-2 px-4 rounded-lg text-sm">Ver Detalhes</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    <?php $conn->close(); ?>
</body>
</html>