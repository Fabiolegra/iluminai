<?php
/**
 * Este arquivo é responsável apenas pela apresentação (HTML).
 * Toda a lógica de negócio e busca de dados é feita em `logic_details_profile.php`.
 */
require_once __DIR__ . '/../src/actions/logic_details_profile.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de <?php echo htmlspecialchars($user['nome']); ?> - IluminAI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-900 text-gray-300">
    <?php require_once 'templates/header.php'; ?>

    <main class="py-10">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Card de Perfil -->
            <div class="bg-gray-800 border border-gray-700 p-6 rounded-lg shadow-lg mb-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Foto e Informações Básicas -->
                    <div class="col-span-1 text-center md:text-left md:border-r md:border-gray-700 md:pr-6">
                        <img class="w-32 h-32 rounded-full mx-auto md:mx-0 mb-4 object-cover border-4 border-gray-700" src="<?php echo $user_avatar; ?>" alt="Foto de Perfil">
                        <h1 class="text-2xl font-bold text-gray-100"><?php echo htmlspecialchars($user['nome']); ?></h1>
                        <p class="text-sm text-gray-400 mb-3"><?php echo htmlspecialchars($user['email']); ?></p>
                        <div class="flex flex-col gap-2">
                            <span class="px-3 py-1 text-xs font-semibold rounded-full <?php echo $user['tipo'] === 'admin' ? 'bg-blue-500/20 text-blue-400' : 'bg-yellow-500/20 text-yellow-400'; ?>">
                                <?php echo htmlspecialchars(ucfirst($user['tipo'])); ?>
                            </span>
                            <span class="px-3 py-1 text-xs font-semibold rounded-full <?php echo $user['status'] === 'active' ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400'; ?>">
                                <?php echo htmlspecialchars(ucfirst($user['status'])); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Informações Adicionais -->
                    <div class="col-span-1 md:border-r md:border-gray-700 md:pr-6">
                        <h3 class="text-sm font-semibold text-gray-300 uppercase tracking-wide mb-4">Informações</h3>
                        <dl class="space-y-3 text-sm">
                            <div>
                                <dt class="text-gray-500">ID do Usuário</dt>
                                <dd class="text-gray-100 font-mono">#<?php echo $user['id']; ?></dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Data de Criação</dt>
                                <dd class="text-gray-100"><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Tipo de Usuário</dt>
                                <dd class="text-gray-100 capitalize"><?php echo htmlspecialchars($user['tipo']); ?></dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Status</dt>
                                <dd class="text-gray-100 capitalize"><?php echo htmlspecialchars($user['status']); ?></dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Estatísticas Rápidas -->
                    <div class="col-span-1">
                        <h3 class="text-sm font-semibold text-gray-300 uppercase tracking-wide mb-4">Resumo</h3>
                        <dl class="space-y-3 text-sm">
                            <div>
                                <dt class="text-gray-500">Total de Ocorrências</dt>
                                <dd class="text-2xl font-bold text-gray-100"><?php echo number_format($stats['total']); ?></dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Abertas</dt>
                                <dd class="text-lg font-semibold text-red-400"><?php echo number_format($stats['abertas']); ?></dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Resolvidas</dt>
                                <dd class="text-lg font-semibold text-green-400"><?php echo number_format($stats['resolvidas']); ?></dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Estatísticas Detalhadas -->
            <section class="mb-8">
                <h2 class="text-2xl font-bold text-gray-100 mb-4">Estatísticas Detalhadas</h2>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-gradient-to-br from-blue-900/30 to-blue-800/30 border border-blue-700/50 p-4 rounded-lg">
                        <p class="text-xs text-gray-400 uppercase tracking-wide">Total de Ocorrências</p>
                        <p class="text-3xl font-bold text-blue-400 mt-2"><?php echo number_format($stats['total']); ?></p>
                    </div>
                    <div class="bg-gradient-to-br from-red-900/30 to-red-800/30 border border-red-700/50 p-4 rounded-lg">
                        <p class="text-xs text-gray-400 uppercase tracking-wide">Abertas</p>
                        <p class="text-3xl font-bold text-red-400 mt-2"><?php echo number_format($stats['abertas']); ?></p>
                    </div>
                    <div class="bg-gradient-to-br from-green-900/30 to-green-800/30 border border-green-700/50 p-4 rounded-lg">
                        <p class="text-xs text-gray-400 uppercase tracking-wide">Resolvidas</p>
                        <p class="text-3xl font-bold text-green-400 mt-2"><?php echo number_format($stats['resolvidas']); ?></p>
                    </div>
                    <div class="bg-gradient-to-br from-yellow-900/30 to-yellow-800/30 border border-yellow-700/50 p-4 rounded-lg">
                        <p class="text-xs text-gray-400 uppercase tracking-wide">Taxa de Resolução</p>
                        <p class="text-3xl font-bold text-yellow-400 mt-2">
                            <?php 
                                $resolution_rate = $stats['total'] > 0 ? round(($stats['resolvidas'] / $stats['total']) * 100) : 0;
                                echo $resolution_rate . '%';
                            ?>
                        </p>
                    </div>
                </div>

                <!-- Métricas de Tempo -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div class="bg-gray-800 border border-gray-700 p-4 rounded-lg">
                        <p class="text-sm text-gray-400 mb-3">Tempo Médio de Resposta</p>
                        <p class="text-2xl font-semibold text-gray-100">
                            <?php
                                if ($stats['tempo_medio_resposta_sec'] !== null) {
                                    $h = floor($stats['tempo_medio_resposta_sec'] / 3600);
                                    $m = floor(($stats['tempo_medio_resposta_sec'] % 3600) / 60);
                                    echo sprintf('%dh %dm', $h, $m);
                                } else {
                                    echo '—';
                                }
                            ?>
                        </p>
                        <p class="text-xs text-gray-500 mt-1">Criação até atendimento</p>
                    </div>
                    <div class="bg-gray-800 border border-gray-700 p-4 rounded-lg">
                        <p class="text-sm text-gray-400 mb-3">Tempo Médio de Resolução</p>
                        <p class="text-2xl font-semibold text-gray-100">
                            <?php
                                if ($stats['tempo_medio_resolucao_sec'] !== null) {
                                    $h = floor($stats['tempo_medio_resolucao_sec'] / 3600);
                                    $m = floor(($stats['tempo_medio_resolucao_sec'] % 3600) / 60);
                                    echo sprintf('%dh %dm', $h, $m);
                                } else {
                                    echo '—';
                                }
                            ?>
                        </p>
                        <p class="text-xs text-gray-500 mt-1">Criação até resolução</p>
                    </div>
                </div>

                <!-- Gráfico de Tendência -->
                <div class="bg-gray-800 border border-gray-700 p-4 rounded-lg">
                    <p class="text-sm text-gray-400 mb-3">Ocorrências (Últimos 30 Dias)</p>
                    <canvas id="sparklineChart" height="80"></canvas>
                </div>
            </section>

            <!-- Ações Administrativas -->
            <div class="mb-8 flex flex-wrap gap-3">
                <button onclick="document.getElementById('blockModal').classList.remove('hidden')" class="bg-red-600 hover:bg-red-500 text-white font-semibold px-4 py-2 rounded transition">
                    <?php echo $user['status'] === 'active' ? 'Bloquear Usuário' : 'Reativar Usuário'; ?>
                </button>
                <button onclick="document.getElementById('noticeModal').classList.remove('hidden')" class="bg-yellow-600 hover:bg-yellow-500 text-white font-semibold px-4 py-2 rounded transition">
                    Enviar Aviso
                </button>
            </div>
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

    <!-- Modal: Bloquear/Reativar Usuário -->
    <div id="blockModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-gray-800 border border-gray-700 p-6 rounded-lg w-full max-w-md">
            <h3 class="text-lg font-semibold text-gray-100 mb-4">
                <?php echo $user['status'] === 'active' ? 'Bloquear Usuário' : 'Reativar Usuário'; ?>
            </h3>
            <p class="text-gray-400 mb-4">
                <?php echo $user['status'] === 'active' ? 'Usuário não poderá fazer login.' : 'Usuário poderá fazer login novamente.'; ?>
            </p>
            <form method="post" action="../src/actions/block_user.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                <input type="hidden" name="action" value="<?php echo $user['status'] === 'active' ? 'block' : 'unblock'; ?>">
                
                <div class="mb-4">
                    <label class="block text-gray-400 text-sm font-bold mb-2">Motivo</label>
                    <textarea name="reason" required class="bg-gray-900 border border-gray-600 text-gray-200 rounded px-3 py-2 w-full text-sm" rows="3" placeholder="Motivo do bloqueio..."></textarea>
                </div>

                <div class="flex gap-3">
                    <button type="submit" class="<?php echo $user['status'] === 'active' ? 'bg-red-600 hover:bg-red-500' : 'bg-green-600 hover:bg-green-500'; ?> text-white px-4 py-2 rounded flex-1">
                        <?php echo $user['status'] === 'active' ? 'Bloquear' : 'Reativar'; ?>
                    </button>
                    <button type="button" onclick="document.getElementById('blockModal').classList.add('hidden')" class="bg-gray-700 hover:bg-gray-600 text-gray-200 px-4 py-2 rounded flex-1">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Enviar Aviso -->
    <div id="noticeModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-gray-800 border border-gray-700 p-6 rounded-lg w-full max-w-md">
            <h3 class="text-lg font-semibold text-gray-100 mb-4">Enviar Aviso</h3>
            <form method="post" action="../src/actions/send_notice.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                
                <div class="mb-4">
                    <label class="block text-gray-400 text-sm font-bold mb-2">Assunto</label>
                    <input type="text" name="subject" required class="bg-gray-900 border border-gray-600 text-gray-200 rounded px-3 py-2 w-full" placeholder="Assunto do aviso...">
                </div>

                <div class="mb-4">
                    <label class="block text-gray-400 text-sm font-bold mb-2">Mensagem</label>
                    <textarea name="message" required class="bg-gray-900 border border-gray-600 text-gray-200 rounded px-3 py-2 w-full text-sm" rows="4" placeholder="Digite a mensagem..."></textarea>
                </div>

                <div class="mb-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="send_email" value="1" checked class="w-4 h-4">
                        <span class="text-gray-400 text-sm">Enviar por e-mail também</span>
                    </label>
                </div>

                <div class="flex gap-3">
                    <button type="submit" class="bg-yellow-600 hover:bg-yellow-500 text-white px-4 py-2 rounded flex-1">Enviar</button>
                    <button type="button" onclick="document.getElementById('noticeModal').classList.add('hidden')" class="bg-gray-700 hover:bg-gray-600 text-gray-200 px-4 py-2 rounded flex-1">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Sparkline Chart
        const ctx = document.getElementById('sparklineChart').getContext('2d');
        const labels = <?php echo json_encode($spark_labels); ?>;
        const data = <?php echo json_encode($spark_data); ?>;
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Ocorrências',
                    data: data,
                    borderColor: '#3B82F6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#3B82F6',
                    pointBorderColor: '#1E40AF'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { color: '#9CA3AF' },
                        grid: { color: 'rgba(156, 163, 175, 0.1)' }
                    },
                    x: {
                        ticks: { color: '#9CA3AF' },
                        grid: { display: false }
                    }
                }
            }
        });
    </script>
</body>
</html>