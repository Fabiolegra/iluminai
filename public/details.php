<?php
// Carrega o bootstrap da aplicação (autoloader, .env, sessão)
require_once __DIR__ . '/../bootstrap.php';

// 1. Proteção da página
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Valida o ID da ocorrência na URL
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    // Se não houver ID ou não for um número, redireciona para a página principal.
    header("location: index.php");
    exit;
}
$ocorrencia_id = intval($_GET['id']);

// Inclui o arquivo de configuração do banco de dados
require_once __DIR__ . '/../config/database.php';

// 2. Processamento do formulário de atualização de status (APENAS PARA ADMINS)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['status']) && $_SESSION['tipo'] === 'admin') {
        $novo_status = $_POST['status'];
        $status_permitidos = ['pendente', 'em andamento', 'resolvido'];

        // Pega o status atual ANTES de atualizar
        $sql_get_status = "SELECT status FROM ocorrencias WHERE id = ?";
        $stmt_get = $conn->prepare($sql_get_status);
        $stmt_get->bind_param("i", $ocorrencia_id);
        $stmt_get->execute();
        $result_status = $stmt_get->get_result();
        $status_anterior = $result_status->fetch_assoc()['status'];
        $stmt_get->close();

        // Só executa se o status for diferente e válido
        if (in_array($novo_status, $status_permitidos) && $novo_status !== $status_anterior) {
            $sql_update = "UPDATE ocorrencias SET status = ? WHERE id = ?";
            if ($stmt = $conn->prepare($sql_update)) {
                $stmt->bind_param("si", $novo_status, $ocorrencia_id);
                if ($stmt->execute()) {
                    $_SESSION['success_msg'] = "Status da ocorrência #{$ocorrencia_id} atualizado com sucesso!";

                    // Adiciona a mudança ao log
                    $sql_log = "INSERT INTO ocorrencias_log (ocorrencia_id, status_anterior, status_novo, alterado_por) VALUES (?, ?, ?, ?)";
                    $stmt_log = $conn->prepare($sql_log);
                    $stmt_log->bind_param("issi", $ocorrencia_id, $status_anterior, $novo_status, $_SESSION['user_id']);
                    $stmt_log->execute();
                    $stmt_log->close();
                } else {
                    $_SESSION['error_msg'] = "Erro ao atualizar o status.";
                }
                $stmt->close();
            }
        } else {
            $_SESSION['error_msg'] = "Status inválido ou idêntico ao atual.";
        }
        // Redireciona para a própria página para ver o resultado
        header("location: details.php?id=" . $ocorrencia_id);
        exit;
    }

    // 2.1 Processamento do formulário de novo comentário
    if (isset($_POST['comentario'])) {
        $comentario_texto = trim($_POST['comentario']);
        if (!empty($comentario_texto)) {
            $sql_insert_comment = "INSERT INTO comentarios (ocorrencia_id, user_id, comentario) VALUES (?, ?, ?)";
            if ($stmt_comment = $conn->prepare($sql_insert_comment)) {
                $stmt_comment->bind_param("iis", $ocorrencia_id, $_SESSION['user_id'], $comentario_texto);
                $stmt_comment->execute();
                $stmt_comment->close();
            }
        }
        header("location: details.php?id=" . $ocorrencia_id);
    }
    exit;
}

// 3. Busca os detalhes da ocorrência
$sql_select = "SELECT o.*, u.nome as user_nome 
               FROM ocorrencias o 
               JOIN users u ON o.user_id = u.id 
               WHERE o.id = ?";
$ocorrencia = null;
if ($stmt = $conn->prepare($sql_select)) {
    $stmt->bind_param("i", $ocorrencia_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $ocorrencia = $result->fetch_assoc();
    }
    $stmt->close();
}

// 3.1 Busca o histórico de status da ocorrência
$historico = [];
$sql_log = "SELECT l.status_anterior, l.status_novo, l.created_at, u.nome as alterado_por_nome
            FROM ocorrencias_log l
            JOIN users u ON l.alterado_por = u.id
            WHERE l.ocorrencia_id = ?
            ORDER BY l.created_at ASC";
if ($stmt = $conn->prepare($sql_log)) {
    $stmt->bind_param("i", $ocorrencia_id);
    $stmt->execute();
    $historico = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// 3.2 Busca os comentários da ocorrência
$comentarios = [];
$sql_comments = "SELECT c.*, u.nome as user_nome, u.tipo as user_tipo
                 FROM comentarios c
                 JOIN users u ON c.user_id = u.id
                 WHERE c.ocorrencia_id = ?
                 ORDER BY c.created_at ASC";
if ($stmt = $conn->prepare($sql_comments)) {
    $stmt->bind_param("i", $ocorrencia_id);
    $stmt->execute();
    $comentarios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// 4. Atualiza o timestamp de visualização dos comentários para o usuário atual
// Isso "marca como lido" os comentários desta ocorrência
$sql_update_seen = "INSERT INTO comentarios_visualizacao (user_id, ocorrencia_id, last_seen_at) 
                    VALUES (?, ?, NOW()) 
                    ON DUPLICATE KEY UPDATE last_seen_at = NOW()";
if ($stmt_seen = $conn->prepare($sql_update_seen)) {
    $stmt_seen->bind_param("ii", $_SESSION['user_id'], $ocorrencia_id);
    $stmt_seen->execute();
    $stmt_seen->close();
}


// 5. Verificação de segurança: Ocorrência existe? O usuário tem permissão?
if ($ocorrencia === null) {
    // Ocorrência não encontrada, redireciona com erro.
    $_SESSION['error_msg'] = "Ocorrência não encontrada.";
    header("location: index.php");
    exit;
}

if ($_SESSION['tipo'] !== 'admin' && $_SESSION['user_id'] !== $ocorrencia['user_id']) {
    // Se não for admin E não for o dono da ocorrência, nega o acesso.
    $_SESSION['error_msg'] = "Acesso negado. Você não tem permissão para ver esta ocorrência.";
    header("location: index.php");
    $conn->close();
    exit;
}

// Mapeamento de status para cores
$status_colors = [
    'pendente' => 'bg-yellow-500/20 text-yellow-400',
    'em andamento' => 'bg-orange-500/20 text-orange-400',
    'resolvido' => 'bg-green-500/20 text-green-400',
];
$status_options = ['pendente', 'em andamento', 'resolvido'];

// Prepara as variáveis de mensagem para a view e limpa a sessão
$success_msg = $_SESSION['success_msg'] ?? null;
$error_msg = $_SESSION['error_msg'] ?? null;
unset($_SESSION['success_msg'], $_SESSION['error_msg']);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da Ocorrência #<?php echo $ocorrencia['id']; ?> - IluminAI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet">
</head>
<body class="bg-gray-900 text-gray-300">
    <!-- Navbar -->
    <?php require_once 'templates/header.php'; ?>

    <!-- Conteúdo -->
    <main class="py-10">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-gray-100 mb-2">Detalhes da Ocorrência #<?php echo htmlspecialchars($ocorrencia['id']); ?></h1>
            <p class="text-gray-400 mb-6">Reportado por <?php echo htmlspecialchars($ocorrencia['user_nome']); ?> em <?php echo date('d/m/Y \à\s H:i', strtotime($ocorrencia['created_at'])); ?></p>

            <?php if ($success_msg): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-4" role="alert"><?php echo htmlspecialchars($success_msg); ?></div>
            <?php endif; ?>
            <?php if ($error_msg): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-4" role="alert"><?php echo htmlspecialchars($error_msg); ?></div>
            <?php endif; ?>
            
            <div class="bg-gray-800 border border-gray-700 shadow-lg rounded-lg overflow-hidden grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
                <!-- Coluna de Informações -->
                <div class="space-y-4">
                    <div><h3 class="text-sm font-semibold text-gray-500">Tipo</h3><p class="text-lg text-gray-100 capitalize"><?php echo htmlspecialchars($ocorrencia['tipo']); ?></p></div>
                    <div><h3 class="text-sm font-semibold text-gray-500">Status</h3><span class="px-3 py-1 text-sm font-semibold rounded-full <?php echo $status_colors[$ocorrencia['status']] ?? 'bg-gray-700 text-gray-200'; ?>"><?php echo htmlspecialchars(ucfirst($ocorrencia['status'])); ?></span></div>
                    <div><h3 class="text-sm font-semibold text-gray-500">Descrição</h3><p class="text-gray-300 whitespace-pre-wrap"><?php echo htmlspecialchars($ocorrencia['descricao']); ?></p></div>
                    
                    <?php if ($ocorrencia['foto']): ?>
                        <div><h3 class="text-sm font-semibold text-gray-500 mb-2">Foto</h3><img src="<?php echo htmlspecialchars($ocorrencia['foto']); ?>" alt="Foto da ocorrência" class="rounded-lg max-w-full h-auto border border-gray-700"></div>
                    <?php endif; ?>
                </div>

                <!-- Coluna de Histórico -->
                <div class="md:col-span-1 space-y-4 border-t md:border-t-0 md:border-l border-gray-700 pt-6 md:pt-0 md:pl-6">
                    <h3 class="text-lg font-semibold text-gray-200">Histórico de Alterações</h3>
                    <?php if (empty($historico)): ?>
                        <p class="text-gray-400">Nenhum histórico de alterações para esta ocorrência.</p>
                    <?php else: ?>
                        <div class="flow-root">
                            <ul role="list" class="-mb-8">
                                <?php foreach ($historico as $index => $log): ?>
                                <li>
                                    <div class="relative pb-8">
                                        <?php if ($index !== count($historico) - 1): ?>
                                            <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-600" aria-hidden="true"></span>
                                        <?php endif; ?>
                                        <div class="relative flex space-x-3">
                                            <div><span class="h-8 w-8 rounded-full bg-gray-600 flex items-center justify-center ring-8 ring-gray-800"><svg class="h-5 w-5 text-gray-300" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd" /></svg></span></div>
                                            <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                <div><p class="text-sm text-gray-400"><?php echo $log['status_anterior'] ? 'Status alterado de <strong class="font-medium text-gray-200 capitalize">'.htmlspecialchars($log['status_anterior']).'</strong> para' : 'Ocorrência criada com status'; ?> <strong class="font-medium text-gray-200 capitalize"><?php echo htmlspecialchars($log['status_novo']); ?></strong> por <strong class="font-medium text-gray-200"><?php echo htmlspecialchars($log['alterado_por_nome']); ?></strong></p></div>
                                                <div class="text-right text-sm whitespace-nowrap text-gray-500"><time datetime="<?php echo $log['created_at']; ?>"><?php echo date('d/m/y H:i', strtotime($log['created_at'])); ?></time></div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Seção de Comentários -->
                <div class="md:col-span-2 space-y-6 border-t border-gray-700 pt-6">
                    <h3 class="text-lg font-semibold text-gray-200">Bate-papo da Ocorrência</h3>
                    <div class="space-y-4">
                        <?php if (empty($comentarios)): ?>
                            <p class="text-gray-400">Nenhum comentário ainda. Seja o primeiro a enviar uma mensagem!</p>
                        <?php else: ?>
                            <?php foreach ($comentarios as $comentario): ?>
                                <?php
                                    $is_admin_comment = $comentario['user_tipo'] === 'admin';
                                    $is_current_user_comment = $comentario['user_id'] === $_SESSION['user_id'];
                                    $comment_bg = $is_admin_comment ? 'bg-blue-900/50 border-blue-700/50' : 'bg-gray-900/70';
                                    $comment_align = $is_current_user_comment ? 'ml-auto' : 'mr-auto';
                                ?>
                                <div class="w-full max-w-lg <?php echo $comment_align; ?>">
                                    <div class="p-3 rounded-lg border <?php echo $comment_bg; ?>">
                                        <div class="flex items-center justify-between mb-1">
                                            <p class="text-sm font-bold <?php echo $is_admin_comment ? 'text-blue-400' : 'text-gray-200'; ?>">
                                                <?php echo htmlspecialchars($comentario['user_nome']); ?>
                                                <?php if ($is_admin_comment): ?>
                                                    <span class="text-xs font-medium bg-blue-600 text-white px-2 py-0.5 rounded-full ml-2">Admin</span>
                                                <?php endif; ?>
                                            </p>
                                            <time class="text-xs text-gray-500"><?php echo date('d/m H:i', strtotime($comentario['created_at'])); ?></time>
                                        </div>
                                        <p class="text-gray-300 whitespace-pre-wrap"><?php echo htmlspecialchars($comentario['comentario']); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Formulário para novo comentário -->
                    <div class="pt-4">
                        <form action="details.php?id=<?php echo $ocorrencia_id; ?>" method="POST">
                            <label for="comentario" class="block text-sm font-semibold text-gray-400 mb-2">Adicionar um comentário</label>
                            <textarea name="comentario" id="comentario" rows="3" class="block w-full rounded-lg border-gray-600 bg-gray-900 text-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Digite sua mensagem aqui..." required></textarea>
                            <div class="mt-3 text-right">
                                <button type="submit" class="px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">Enviar Mensagem</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Coluna do Mapa e Ações -->
                <div class="space-y-6">
                    <div><h3 class="text-sm font-semibold text-gray-500 mb-2">Localização</h3><div id="map" class="w-full h-64 rounded-lg border border-gray-700"></div></div>

                    <!-- Formulário de Ação para Admin -->
                    <?php if (($_SESSION['tipo'] ?? 'usuario') === 'admin'): ?>
                        <div class="bg-gray-900 p-4 rounded-lg border border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-200 mb-3">Alterar Status</h3>
                            <form action="details.php?id=<?php echo $ocorrencia_id; ?>" method="POST" class="flex items-center gap-2">
                                <select name="status" class="block w-full rounded-lg border-gray-600 bg-gray-800 text-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    <?php foreach ($status_options as $option): ?>
                                        <option value="<?php echo $option; ?>" <?php echo ($ocorrencia['status'] == $option) ? 'selected' : ''; ?>><?php echo ucfirst($option); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">Salvar</button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <!-- Formulário de Exclusão para Admin ou Dono -->
                    <?php
                        $is_owner = ($_SESSION['user_id'] === $ocorrencia['user_id']);
                        $is_admin = ($_SESSION['tipo'] === 'admin');
                        $is_pending = ($ocorrencia['status'] === 'pendente');
                        // Mostra o botão se for admin, ou se for o dono e o status for pendente
                        if ($is_admin || ($is_owner && $is_pending)):
                    ?>
                        <form action="../src/actions/delete_occurrence.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta ocorrência? Esta ação não pode ser desfeita.');" class="mt-4">
                            <input type="hidden" name="ocorrencia_id" value="<?php echo $ocorrencia_id; ?>">
                            <button type="submit" class="w-full text-center bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg text-sm transition-colors">Excluir Ocorrência</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
    <script>
        mapboxgl.accessToken = '<?php echo $_ENV['MAPBOX_TOKEN']; ?>';
        const map = new mapboxgl.Map({
            container: 'map',
            style: 'mapbox://styles/mapbox/dark-v11',
            center: [<?php echo $ocorrencia['longitude']; ?>, <?php echo $ocorrencia['latitude']; ?>],
            zoom: 15
        });
        map.addControl(new mapboxgl.NavigationControl(), 'top-left');
        
        // Adiciona um marcador com uma cor de destaque para garantir a visibilidade
        new mapboxgl.Marker({ color: '#3B82F6' }) // Cor azul (blue-500)
            .setLngLat([<?php echo $ocorrencia['longitude']; ?>, <?php echo $ocorrencia['latitude']; ?>])
            .addTo(map);
    </script>
    <?php
        $conn->close();
    ?>
</body>
</html>
