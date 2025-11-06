<?php
// Carrega o bootstrap da aplicação (autoloader, .env, sessão)
require_once __DIR__ . '/../bootstrap.php';

// 1. Proteção da página
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    // Se não for admin, redireciona para a página principal com uma mensagem de erro (opcional).
    $_SESSION['error_msg'] = "Acesso negado. Você não tem permissão para acessar esta página.";
    header("location: index.php");
    exit;
}

// Inclui o arquivo de configuração do banco de dados
require_once __DIR__ . '/../config/database.php';

// 2. Processamento do formulário de atualização de status
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['ocorrencia_id'], $_POST['status'])) {
        $ocorrencia_id = intval($_POST['ocorrencia_id']);
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
    }
    // Redireciona de volta para a própria página de admin
    header("location: admin.php");
    exit;
}

// 3. Busca de todas as ocorrências com os dados do usuário
$ocorrencias = [];
$sql_select = "SELECT o.id, o.tipo, o.status, o.created_at, u.nome as user_nome 
               FROM ocorrencias o 
               JOIN users u ON o.user_id = u.id 
               ORDER BY o.created_at DESC";

$result = $conn->query($sql_select);
if ($result) {
    $ocorrencias = $result->fetch_all(MYSQLI_ASSOC);
}

// 4. Busca de estatísticas para o dashboard
$stats = [
    'pendente' => 0,
    'em_andamento' => 0,
    'resolvido' => 0,
    'total_users' => 0
];

// Contagem de ocorrências por status
$result_status = $conn->query("SELECT status, COUNT(id) as total FROM ocorrencias GROUP BY status");
if ($result_status) {
    while ($row = $result_status->fetch_assoc()) {
        $key = str_replace(' ', '_', $row['status']); // 'em andamento' -> 'em_andamento'
        if (array_key_exists($key, $stats)) {
            $stats[$key] = $row['total'];
        }
    }
}

// Contagem de usuários
$result_users = $conn->query("SELECT COUNT(id) as total FROM users WHERE tipo = 'usuario'");
if ($result_users) {
    $stats['total_users'] = $result_users->fetch_assoc()['total'];
}

// Ocorrências por tipo (para o gráfico)
$ocorrencias_por_tipo = [];
$result_tipos = $conn->query("SELECT tipo, COUNT(id) as total FROM ocorrencias GROUP BY tipo");
if ($result_tipos) {
    $ocorrencias_por_tipo = $result_tipos->fetch_all(MYSQLI_ASSOC);
}

// Histórico de alterações recentes
$historico_recente = $conn->query("SELECT l.*, o.tipo as ocorrencia_tipo, u.nome as alterado_por_nome FROM ocorrencias_log l JOIN ocorrencias o ON l.ocorrencia_id = o.id JOIN users u ON l.alterado_por = u.id ORDER BY l.created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);


$status_options = ['pendente', 'em andamento', 'resolvido'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Administração - IluminAI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-900 text-gray-300">
    <!-- Navbar -->
    <?php require_once 'templates/header.php'; ?>

    <!-- Conteúdo do Painel -->
    <main class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-gray-100 mb-8">Dashboard Administrativo</h1>

            <!-- Seção de Estatísticas -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-gray-800 border border-gray-700 rounded-lg p-5">
                    <h3 class="text-sm font-medium text-yellow-400">Pendentes</h3>
                    <p class="mt-2 text-3xl font-semibold text-gray-100"><?php echo $stats['pendente']; ?></p>
                </div>
                <div class="bg-gray-800 border border-gray-700 rounded-lg p-5">
                    <h3 class="text-sm font-medium text-orange-400">Em Andamento</h3>
                    <p class="mt-2 text-3xl font-semibold text-gray-100"><?php echo $stats['em_andamento']; ?></p>
                </div>
                <div class="bg-gray-800 border border-gray-700 rounded-lg p-5">
                    <h3 class="text-sm font-medium text-green-400">Resolvidas</h3>
                    <p class="mt-2 text-3xl font-semibold text-gray-100"><?php echo $stats['resolvido']; ?></p>
                </div>
                <div class="bg-gray-800 border border-gray-700 rounded-lg p-5">
                    <h3 class="text-sm font-medium text-blue-400">Usuários</h3>
                    <p class="mt-2 text-3xl font-semibold text-gray-100"><?php echo $stats['total_users']; ?></p>
                </div>
            </div>

            <!-- Seção de Gráficos e Atividades Recentes -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
                <div class="lg:col-span-2 bg-gray-800 border border-gray-700 rounded-lg p-5">
                    <h3 class="text-lg font-semibold text-gray-200 mb-4">Ocorrências por Tipo</h3>
                    <div class="h-80"><canvas id="ocorrenciasPorTipoChart"></canvas></div>
                </div>
                <div class="bg-gray-800 border border-gray-700 rounded-lg p-5">
                    <h3 class="text-lg font-semibold text-gray-200 mb-4">Atividade Recente</h3>
                    <ul class="space-y-4">
                        <?php if (empty($historico_recente)): ?>
                            <li class="text-gray-400 text-sm">Nenhuma atividade recente.</li>
                        <?php else: ?>
                            <?php foreach ($historico_recente as $log): ?>
                                <li class="text-sm">
                                    <p class="text-gray-300">
                                        <strong class="font-medium text-white"><?php echo htmlspecialchars($log['alterado_por_nome']); ?></strong> alterou a ocorrência
                                        <a href="details.php?id=<?php echo $log['ocorrencia_id']; ?>" class="text-blue-400 hover:underline">#<?php echo $log['ocorrencia_id']; ?></a>
                                        para <strong class="capitalize"><?php echo htmlspecialchars($log['status_novo']); ?></strong>.
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1"><?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?></p>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <?php if (isset($_SESSION['success_msg'])): ?>
                <div class="bg-green-500/20 border border-green-500/30 text-green-400 px-4 py-3 rounded-lg relative mb-4" role="alert">
                    <?php echo htmlspecialchars($_SESSION['success_msg']); unset($_SESSION['success_msg']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_msg'])): ?>
                <div class="bg-red-500/20 border border-red-500/30 text-red-400 px-4 py-3 rounded-lg relative mb-4" role="alert">
                    <?php echo htmlspecialchars($_SESSION['error_msg']); unset($_SESSION['error_msg']); ?>
                </div>
            <?php endif; ?>

            <h2 class="text-2xl font-bold text-gray-100 mb-6 mt-12">Gerenciar Ocorrências</h2>
            <div class="overflow-x-auto bg-gray-800 border border-gray-700 rounded-lg shadow-md">
                <table class="min-w-full">
                    <thead class="hidden md:table-header-group bg-gray-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Usuário</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Tipo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Data</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Ação</th>
                        </tr>
                    </thead>
                    <tbody class="block md:table-row-group space-y-4 md:space-y-0 p-4 md:p-0 md:divide-y md:divide-gray-700">
                        <?php if (empty($ocorrencias)): ?>
                            <tr class="block md:table-row"><td colspan="5" class="block md:table-cell px-6 py-4 text-center text-gray-400">Nenhuma ocorrência encontrada.</td></tr>
                        <?php else: ?>
                            <?php foreach ($ocorrencias as $ocorrencia): ?>
                                <tr class="block md:table-row bg-gray-900/50 p-4 rounded-lg shadow-md md:bg-transparent md:p-0 md:shadow-none md:border-b md:border-gray-700">
                                    <td class="block md:table-cell md:px-6 md:py-4 whitespace-nowrap text-sm font-medium text-gray-100"><a href="details.php?id=<?php echo $ocorrencia['id']; ?>" class="text-blue-400 hover:underline">#<?php echo $ocorrencia['id']; ?></a></td>
                                    <td class="block md:table-cell md:px-6 md:py-4 whitespace-nowrap text-sm text-gray-400"><span class="font-bold text-gray-300 md:hidden">Usuário: </span><?php echo htmlspecialchars($ocorrencia['user_nome']); ?></td>
                                    <td class="block md:table-cell md:px-6 md:py-4 whitespace-nowrap text-sm text-gray-400 capitalize"><span class="font-bold text-gray-300 md:hidden">Tipo: </span><?php echo htmlspecialchars($ocorrencia['tipo']); ?></td>
                                    <td class="block md:table-cell md:px-6 md:py-4 whitespace-nowrap text-sm text-gray-400"><span class="font-bold text-gray-300 md:hidden">Data: </span><?php echo date('d/m/Y H:i', strtotime($ocorrencia['created_at'])); ?></td>
                                    <td class="block md:table-cell md:px-6 md:py-4 whitespace-nowrap text-sm font-medium">
                                        <form action="admin.php" method="POST" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2" onchange="this.querySelector('button[type=submit]').disabled = false;">
                                            <input type="hidden" name="ocorrencia_id" value="<?php echo $ocorrencia['id']; ?>">
                                            <select name="status" class="block w-full rounded-lg bg-gray-700 border-gray-600 text-gray-200 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 text-sm">
                                                <?php foreach ($status_options as $option): ?>
                                                    <option value="<?php echo $option; ?>" <?php echo ($ocorrencia['status'] == $option) ? 'selected' : ''; ?>><?php echo ucfirst($option); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:bg-gray-500 disabled:cursor-not-allowed">Salvar</button>
                                            <a href="details.php?id=<?php echo $ocorrencia['id']; ?>" class="px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-gray-600 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 text-center">Ver</a>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <?php
        $conn->close();
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('ocorrenciasPorTipoChart').getContext('2d');
            
            const data = <?php echo json_encode($ocorrencias_por_tipo); ?>;
            const labels = data.map(item => item.tipo.charAt(0).toUpperCase() + item.tipo.slice(1));
            const values = data.map(item => item.total);

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Ocorrências',
                        data: values,
                        backgroundColor: ['#FBBF24', '#F97316', '#22C55E', '#3B82F6', '#8B5CF6'],
                        borderColor: '#1f2937',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'top', labels: { color: '#d1d5db' } } }
                }
            });
        });
    </script>
</body>
</html>