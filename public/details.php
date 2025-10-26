<?php
session_start();

// 1. Proteção da página: Apenas usuários logados podem acessar.
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
define('MAPBOX_TOKEN', 'pk.eyJ1Ijoic2dodXMiLCJhIjoiY21nYTV2c3A2MGYwdDJucHg4ZWt3ZGl4NiJ9.6n3z1p6riEzHiu7TfbM4mQ');

// 2. Processamento do formulário de atualização de status (APENAS PARA ADMINS)
if ($_SERVER["REQUEST_METHOD"] == "POST" && $_SESSION['tipo'] === 'admin') {
    if (isset($_POST['status'])) {
        $novo_status = $_POST['status'];
        $status_permitidos = ['pendente', 'em andamento', 'resolvido'];

        if (in_array($novo_status, $status_permitidos)) {
            $sql_update = "UPDATE ocorrencias SET status = ? WHERE id = ?";
            if ($stmt = $conn->prepare($sql_update)) {
                $stmt->bind_param("si", $novo_status, $ocorrencia_id);
                if ($stmt->execute()) {
                    $_SESSION['success_msg'] = "Status da ocorrência #{$ocorrencia_id} atualizado com sucesso!";
                } else {
                    $_SESSION['error_msg'] = "Erro ao atualizar o status.";
                }
                $stmt->close();
            }
        } else {
            $_SESSION['error_msg'] = "Status inválido selecionado.";
        }
    }
    // Redireciona para a página principal para ver o resultado no mapa
    header("location: index.php");
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
$conn->close();

// 4. Verificação de segurança: Ocorrência existe? O usuário tem permissão?
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
        mapboxgl.accessToken = '<?php echo MAPBOX_TOKEN; ?>';
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
</body>
</html>
