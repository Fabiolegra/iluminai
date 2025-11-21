<?php
/**
 * View para o Dashboard do Administrador.
 * Este arquivo é responsável apenas pela apresentação (HTML).
 * Toda a lógica de negócio e busca de dados é feita em `admin_dashboard_logic.php`.
 */
require_once __DIR__ . '/../src/actions/logic_admin_dashboard.php';
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

            <!-- Seção de Filtros -->
            <div class="bg-gray-800 border border-gray-700 rounded-lg p-4 mb-8">
                <form action="dashboard_admin.php" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                    <div>
                        <label for="data_inicio" class="block text-sm font-medium text-gray-400">Data Início</label>
                        <input type="date" name="data_inicio" id="data_inicio" value="<?php echo htmlspecialchars($filtro_data_inicio); ?>" class="mt-1 block w-full rounded-lg bg-gray-700 border-gray-600 text-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label for="data_fim" class="block text-sm font-medium text-gray-400">Data Fim</label>
                        <input type="date" name="data_fim" id="data_fim" value="<?php echo htmlspecialchars($filtro_data_fim); ?>" class="mt-1 block w-full rounded-lg bg-gray-700 border-gray-600 text-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label for="tipo" class="block text-sm font-medium text-gray-400">Tipo</label>
                        <select name="tipo" id="tipo" class="mt-1 block w-full rounded-lg bg-gray-700 border-gray-600 text-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">Todos</option>
                            <?php foreach ($tipos_distintos as $tipo_opt): ?>
                                <option value="<?php echo htmlspecialchars($tipo_opt['tipo']); ?>" <?php echo ($filtro_tipo == $tipo_opt['tipo']) ? 'selected' : ''; ?>><?php echo htmlspecialchars(ucfirst($tipo_opt['tipo'])); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-400">Status</label>
                        <select name="status" id="status" class="mt-1 block w-full rounded-lg bg-gray-700 border-gray-600 text-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">Todos</option>
                            <?php foreach ($status_options as $status_opt): ?>
                                <option value="<?php echo htmlspecialchars($status_opt); ?>" <?php echo ($filtro_status == $status_opt) ? 'selected' : ''; ?>><?php echo htmlspecialchars(ucfirst($status_opt)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex gap-2"><button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg text-sm">Filtrar</button><a href="dashboard_admin.php" class="w-full text-center bg-gray-600 hover:bg-gray-500 text-white font-semibold py-2 px-4 rounded-lg text-sm">Limpar</a></div>
                </form>
            </div>

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
                                        <form action="dashboard_admin.php" method="POST" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2" onchange="this.querySelector('button[type=submit]').disabled = false;">
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