<?php
/**
 * Página de Avisos do Admin
 * Exibe todos os avisos enviados pelo admin aos usuários
 */
require_once __DIR__ . '/../src/actions/logic_admin_notices.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avisos Enviados - IluminAI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-300">
    <?php require_once 'templates/header.php'; ?>

    <main class="py-10">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Cabeçalho -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-100 mb-2">Avisos Enviados</h1>
                <p class="text-gray-400">Gerencie todos os avisos que você enviou aos usuários</p>
            </div>

            <!-- Botões de Ação -->
            <div class="mb-8 flex flex-wrap gap-3">
                <button onclick="loadUsersAndShowModal('singleNoticeModal')" class="bg-indigo-600 hover:bg-indigo-500 text-white font-semibold px-4 py-2 rounded transition">
                    Enviar para Pessoa Específica
                </button>
                <button onclick="document.getElementById('groupNoticeModal').classList.remove('hidden')" class="bg-blue-600 hover:bg-blue-500 text-white font-semibold px-4 py-2 rounded transition">
                    Enviar para Grupo/Tipo
                </button>
                <button onclick="document.getElementById('allNoticeModal').classList.remove('hidden')" class="bg-purple-600 hover:bg-purple-500 text-white font-semibold px-4 py-2 rounded transition">
                    Enviar para Todos
                </button>
            </div>

            <!-- Estatísticas -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                <div class="bg-gray-800 border border-gray-700 p-4 rounded-lg">
                    <p class="text-sm text-gray-400">Total de Avisos</p>
                    <p class="text-3xl font-bold text-blue-400 mt-2"><?php echo number_format($stats['total']); ?></p>
                </div>
                <div class="bg-gray-800 border border-gray-700 p-4 rounded-lg">
                    <p class="text-sm text-gray-400">Não Lidos</p>
                    <p class="text-3xl font-bold text-yellow-400 mt-2"><?php echo number_format($stats['nao_lidos']); ?></p>
                </div>
                <div class="bg-gray-800 border border-gray-700 p-4 rounded-lg">
                    <p class="text-sm text-gray-400">Lidos</p>
                    <p class="text-3xl font-bold text-green-400 mt-2"><?php echo number_format($stats['lidos']); ?></p>
                </div>
            </div>

            <!-- Lista de Avisos -->
            <div class="space-y-4">
                <?php if (empty($avisos)): ?>
                    <div class="bg-gray-800 border border-gray-700 p-8 rounded-lg text-center">
                        <p class="text-gray-400 text-lg">Você ainda não enviou avisos.</p>
                        <a href="manage_users.php" class="inline-block mt-4 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold px-4 py-2 rounded">
                            Ir para Gerenciar Usuários
                        </a>
                    </div>
                <?php else: ?>
                    <div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-700 border-b border-gray-600">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Usuário</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Assunto</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Data</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-700">
                                    <?php foreach ($avisos as $aviso): ?>
                                        <tr class="hover:bg-gray-700/50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center gap-2">
                                                    <?php if (!$aviso['lido']): ?>
                                                        <span class="w-2 h-2 bg-yellow-500 rounded-full"></span>
                                                        <span class="text-xs text-yellow-400">Não Lido</span>
                                                    <?php else: ?>
                                                        <span class="text-xs text-green-400">Lido</span>
                                                    <?php endif; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm">
                                                    <p class="text-gray-100 font-medium"><?php echo htmlspecialchars($aviso['user_nome']); ?></p>
                                                    <p class="text-gray-400"><?php echo htmlspecialchars($aviso['user_email']); ?></p>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <p class="text-sm text-gray-100"><?php echo htmlspecialchars($aviso['assunto']); ?></p>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <p class="text-sm text-gray-400"><?php echo date('d/m/Y H:i', strtotime($aviso['created_at'])); ?></p>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <button onclick="showNoticeDetails('<?php echo htmlspecialchars(json_encode($aviso), ENT_QUOTES); ?>')" 
                                                        class="text-indigo-400 hover:text-indigo-300 text-sm font-medium">
                                                    Ver Detalhes
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal: Detalhes do Aviso -->
    <div id="noticeModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-gray-800 border border-gray-700 rounded-lg w-full max-w-2xl">
            <div class="flex items-center justify-between p-6 border-b border-gray-700">
                <h3 class="text-lg font-semibold text-gray-100" id="modalTitle"></h3>
                <button onclick="document.getElementById('noticeModal').classList.add('hidden')" class="text-gray-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <p class="text-sm text-gray-400" id="modalRecipient"></p>
                    <p class="text-sm text-gray-500" id="modalDate"></p>
                </div>
                
                <div class="bg-gray-900/50 p-4 rounded border border-gray-700">
                    <p class="text-gray-300 whitespace-pre-wrap" id="modalMessage"></p>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="document.getElementById('noticeModal').classList.add('hidden')" class="bg-gray-700 hover:bg-gray-600 text-gray-200 px-4 py-2 rounded flex-1">
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php $conn->close(); ?>

    <!-- Modal: Enviar para Pessoa Específica -->
    <div id="singleNoticeModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-gray-800 border border-gray-700 rounded-lg w-full max-w-2xl">
            <div class="flex items-center justify-between p-6 border-b border-gray-700">
                <h3 class="text-lg font-semibold text-gray-100">Enviar Aviso para Pessoa Específica</h3>
                <button onclick="document.getElementById('singleNoticeModal').classList.add('hidden')" class="text-gray-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form method="post" action="../src/actions/send_notice.php" class="p-6 space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="type" value="single">
                
                <div>
                    <label class="block text-gray-400 text-sm font-bold mb-2">Selecione o Usuário</label>
                    <select name="user_id" required class="bg-gray-900 border border-gray-600 text-gray-200 rounded px-3 py-2 w-full">
                        <option value="">— Carregando usuários... —</option>
                    </select>
                </div>

                <div>
                    <label class="block text-gray-400 text-sm font-bold mb-2">Assunto</label>
                    <input type="text" name="subject" required class="bg-gray-900 border border-gray-600 text-gray-200 rounded px-3 py-2 w-full" placeholder="Assunto do aviso...">
                </div>

                <div>
                    <label class="block text-gray-400 text-sm font-bold mb-2">Mensagem</label>
                    <textarea name="message" required class="bg-gray-900 border border-gray-600 text-gray-200 rounded px-3 py-2 w-full text-sm" rows="5" placeholder="Digite a mensagem..."></textarea>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded flex-1">Enviar</button>
                    <button type="button" onclick="document.getElementById('singleNoticeModal').classList.add('hidden')" class="bg-gray-700 hover:bg-gray-600 text-gray-200 px-4 py-2 rounded flex-1">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Enviar para Grupo/Tipo -->
    <div id="groupNoticeModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-gray-800 border border-gray-700 rounded-lg w-full max-w-2xl">
            <div class="flex items-center justify-between p-6 border-b border-gray-700">
                <h3 class="text-lg font-semibold text-gray-100">Enviar Aviso para Grupo/Tipo</h3>
                <button onclick="document.getElementById('groupNoticeModal').classList.add('hidden')" class="text-gray-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form method="post" action="../src/actions/send_notice.php" class="p-6 space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="type" value="group">
                
                <div>
                    <label class="block text-gray-400 text-sm font-bold mb-2">Selecione o Tipo de Usuário</label>
                    <select name="user_type" required class="bg-gray-900 border border-gray-600 text-gray-200 rounded px-3 py-2 w-full">
                        <option value="">— Escolher tipo —</option>
                        <option value="usuario">Usuários</option>
                        <option value="operador">Operadores</option>
                        <option value="admin">Admins</option>
                    </select>
                </div>

                <div>
                    <label class="block text-gray-400 text-sm font-bold mb-2">Assunto</label>
                    <input type="text" name="subject" required class="bg-gray-900 border border-gray-600 text-gray-200 rounded px-3 py-2 w-full" placeholder="Assunto do aviso...">
                </div>

                <div>
                    <label class="block text-gray-400 text-sm font-bold mb-2">Mensagem</label>
                    <textarea name="message" required class="bg-gray-900 border border-gray-600 text-gray-200 rounded px-3 py-2 w-full text-sm" rows="5" placeholder="Digite a mensagem..."></textarea>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded flex-1">Enviar</button>
                    <button type="button" onclick="document.getElementById('groupNoticeModal').classList.add('hidden')" class="bg-gray-700 hover:bg-gray-600 text-gray-200 px-4 py-2 rounded flex-1">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Enviar para Todos -->
    <div id="allNoticeModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-gray-800 border border-gray-700 rounded-lg w-full max-w-2xl">
            <div class="flex items-center justify-between p-6 border-b border-gray-700">
                <h3 class="text-lg font-semibold text-gray-100">Enviar Aviso para Todos</h3>
                <button onclick="document.getElementById('allNoticeModal').classList.add('hidden')" class="text-gray-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="p-6">
                <p class="text-gray-400 text-sm mb-6">Este aviso será enviado para <strong>todos os usuários do sistema</strong>.</p>
                <form method="post" action="../src/actions/send_notice.php" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="type" value="all">
                    
                    <div>
                        <label class="block text-gray-400 text-sm font-bold mb-2">Assunto</label>
                        <input type="text" name="subject" required class="bg-gray-900 border border-gray-600 text-gray-200 rounded px-3 py-2 w-full" placeholder="Assunto do aviso...">
                    </div>

                    <div>
                        <label class="block text-gray-400 text-sm font-bold mb-2">Mensagem</label>
                        <textarea name="message" required class="bg-gray-900 border border-gray-600 text-gray-200 rounded px-3 py-2 w-full text-sm" rows="5" placeholder="Digite a mensagem..."></textarea>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button type="submit" class="bg-purple-600 hover:bg-purple-500 text-white px-4 py-2 rounded flex-1">Enviar</button>
                        <button type="button" onclick="document.getElementById('allNoticeModal').classList.add('hidden')" class="bg-gray-700 hover:bg-gray-600 text-gray-200 px-4 py-2 rounded flex-1">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showNoticeDetails(noticeJson) {
            try {
                const notice = JSON.parse(noticeJson.replace(/&quot;/g, '"'));
                document.getElementById('modalTitle').textContent = notice.assunto;
                document.getElementById('modalRecipient').textContent = 'Para: ' + notice.user_nome + ' (' + notice.user_email + ')';
                document.getElementById('modalDate').textContent = 'Enviado em: ' + new Date(notice.created_at).toLocaleString('pt-BR');
                document.getElementById('modalMessage').textContent = notice.mensagem;
                document.getElementById('noticeModal').classList.remove('hidden');
            } catch (e) {
                console.error('Erro ao processar aviso:', e);
            }
        }

        function loadUsersAndShowModal(modalId) {
            const select = document.querySelector('#singleNoticeModal select[name="user_id"]');
            
            // Se já foram carregados, apenas mostra o modal
            if (select.options.length > 1) {
                document.getElementById(modalId).classList.remove('hidden');
                return;
            }

            // Carrega os usuários via AJAX
            fetch('../src/actions/get_users.php')
                .then(response => response.json())
                .then(data => {
                    // Limpa as opções anteriores
                    select.innerHTML = '<option value="">— Escolher usuário —</option>';
                    
                    // Adiciona os usuários
                    data.forEach(user => {
                        const option = document.createElement('option');
                        option.value = user.id;
                        option.textContent = user.nome + ' (' + user.email + ')';
                        select.appendChild(option);
                    });
                    
                    // Mostra o modal
                    document.getElementById(modalId).classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Erro ao carregar usuários:', error);
                    alert('Erro ao carregar lista de usuários');
                });
        }
    </script>
</body>
</html>
