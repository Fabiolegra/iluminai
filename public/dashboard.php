<?php
// Carrega o bootstrap da aplicação (autoloader, .env, sessão)
require_once __DIR__ . '/../bootstrap.php';

// Protege a página: só usuários logados podem acessar
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Inclui o arquivo de configuração do banco de dados
require_once __DIR__ . '/../config/database.php';

$user_id = $_SESSION['user_id'];
$is_admin = ($_SESSION['tipo'] === 'admin');
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
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Ocorrências - IluminAI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-300">
    <!-- Navbar -->
    <?php require_once 'templates/header.php'; ?>

    <!-- Conteúdo do Dashboard -->
    <main class="py-10">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-100"><?php echo $is_admin ? 'Conversas Ativas' : 'Minhas Ocorrências'; ?></h1>
                <a href="report.php" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg shadow">Nova Ocorrência</a>
            </div>

            <?php if (isset($_SESSION['success_msg'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <?php echo htmlspecialchars($_SESSION['success_msg']); unset($_SESSION['success_msg']); ?>
                </div>
            <?php endif; ?>

            <div class="bg-gray-800 border border-gray-700 shadow-lg rounded-lg overflow-hidden">
                <div class="divide-y divide-gray-700">
                    <?php if (empty($ocorrencias)): ?>
                        <p class="p-6 text-center text-gray-400"><?php echo $is_admin ? 'Nenhuma conversa ativa no momento.' : 'Você ainda não reportou nenhuma ocorrência.'; ?></p>
                    <?php else: ?>
                        <?php foreach ($ocorrencias as $ocorrencia): ?>
                            <div class="p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between hover:bg-gray-700/50 transition-colors">
                                <div class="flex-grow mb-4 sm:mb-0">
                                    <div class="flex items-center gap-3">
                                        <span class="px-3 py-1 text-xs font-semibold rounded-full <?php echo $status_colors[$ocorrencia['status']] ?? 'bg-gray-700 text-gray-200'; ?>">
                                            <?php echo htmlspecialchars(ucfirst($ocorrencia['status'])); ?>
                                        </span>
                                        <a href="details.php?id=<?php echo $ocorrencia['id']; ?>" class="text-lg font-semibold text-gray-100 capitalize hover:underline">
                                            <?php echo htmlspecialchars($ocorrencia['tipo']); ?> (Ocorrência #<?php echo $ocorrencia['id']; ?>)
                                        </a>
                                    </div>
                                    <p class="text-sm text-gray-400 mt-1 ml-1">
                                        <?php if ($is_admin): ?>
                                            Reportado por <strong><?php echo htmlspecialchars($ocorrencia['user_nome']); ?></strong> em <?php echo date('d/m/Y', strtotime($ocorrencia['created_at'])); ?>
                                        <?php else: ?>
                                            Reportado em: <?php echo date('d/m/Y \à\s H:i', strtotime($ocorrencia['created_at'])); ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 flex-shrink-0 w-full sm:w-auto mt-4 sm:mt-0">
                                    <?php if (!empty($ocorrencia['unread_count']) && $ocorrencia['unread_count'] > 0): ?>
                                        <a href="details.php?id=<?php echo $ocorrencia['id']; ?>" class="flex items-center gap-2 w-full sm:w-auto text-center bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg text-sm">
                                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zM9 11a1 1 0 100-2 1 1 0 000 2zm-3 0a1 1 0 100-2 1 1 0 000 2zm6 0a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" /></svg>
                                            <span><?php echo $ocorrencia['unread_count']; ?> Nova(s)</span>
                                        </a>
                                    <?php else: ?>
                                        <a href="details.php?id=<?php echo $ocorrencia['id']; ?>" class="w-full sm:w-auto text-center bg-gray-700 hover:bg-gray-600 text-gray-200 font-semibold py-2 px-4 rounded-lg text-sm">Detalhes</a>
                                    <?php endif; ?>
                                    <?php if (!$is_admin && $ocorrencia['status'] === 'pendente'): ?>
                                        <form action="../src/actions/delete_occurrence.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta ocorrência?');" class="w-full sm:w-auto">
                                            <input type="hidden" name="ocorrencia_id" value="<?php echo $ocorrencia['id']; ?>">
                                            <button type="submit" class="w-full sm:w-auto text-center bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg text-sm min-w-[100px]">Excluir</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
<?php
    $conn->close();
?>
</html>
        </div>
    </main>
</html>