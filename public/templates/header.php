<?php
// Este arquivo é incluído em páginas que já iniciaram a sessão
// e carregaram o bootstrap.php, então $_SESSION está disponível.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está logado antes de prosseguir
$user_id = $_SESSION['user_id'] ?? null;
$is_admin = (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'admin');
$current_page = basename($_SERVER['SCRIPT_NAME']);
$unread_messages_count = 0;

if ($user_id) {
    // Inclui a conexão com o banco de dados se ainda não estiver disponível
    // (Isso é um fallback, idealmente a página que inclui o header já tem a conexão)
    if (!isset($conn) || !$conn) {
        require_once __DIR__ . '/../../config/database.php';
    }

    // Lógica para contar mensagens não lidas
    if ($is_admin) {
        // Admin: conta novas mensagens em TODAS as ocorrências que não foram enviadas por ele mesmo
        $sql_unread = "SELECT COUNT(c.id) as total
                       FROM comentarios c
                       LEFT JOIN comentarios_visualizacao cv ON c.ocorrencia_id = cv.ocorrencia_id AND cv.user_id = ?
                       WHERE c.user_id != ? AND (cv.last_seen_at IS NULL OR c.created_at > cv.last_seen_at)";
        $stmt_unread = $conn->prepare($sql_unread);
        $stmt_unread->bind_param("ii", $user_id, $user_id);
    } else {
        // Usuário comum: conta novas mensagens apenas nas SUAS ocorrências que não foram enviadas por ele mesmo
        $sql_unread = "SELECT COUNT(c.id) as total
                       FROM comentarios c
                       JOIN ocorrencias o ON c.ocorrencia_id = o.id
                       LEFT JOIN comentarios_visualizacao cv ON c.ocorrencia_id = cv.ocorrencia_id AND cv.user_id = ?
                       WHERE o.user_id = ? AND c.user_id != ? AND (cv.last_seen_at IS NULL OR c.created_at > cv.last_seen_at)";
        $stmt_unread = $conn->prepare($sql_unread);
        $stmt_unread->bind_param("iii", $user_id, $user_id, $user_id);
    }
    $stmt_unread->execute();
    $unread_messages_count = $stmt_unread->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt_unread->close();
}
?>
<header>
<nav class="bg-gray-800 border-b border-gray-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <div class="flex items-center">
                <div class="flex-shrink-0 flex items-center gap-4">
                    <?php if ($current_page !== 'index.php'): ?>
                        <a href="index.php" class="text-gray-400 hover:text-white flex items-center gap-2 text-sm" title="Voltar para o Mapa">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                            <span class="hidden sm:inline">Mapa</span>
                        </a>
                    <?php endif; ?>
                    <a href="index.php" class="text-2xl font-bold text-gray-100">IluminAI</a>
                </div>
            </div>
            <div class="hidden md:block">
                <div class="ml-10 flex items-baseline space-x-4">
                    <?php if ($is_admin): ?>
                        <a href="admin.php" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Painel Admin</a>
                    <?php endif; ?>
                    <a href="dashboard.php" class="relative text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium" title="Ver minhas ocorrências e mensagens">
                        <span>Minhas Ocorrências</span>
                        <?php if ($unread_messages_count > 0): ?>
                            <span class="absolute top-1 right-0 flex h-3 w-3"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span><span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span></span>
                        <?php endif; ?>
                    </a>
                    <a href="logout.php" class="bg-gray-700 hover:bg-gray-600 text-gray-200 font-semibold py-2 px-4 rounded-lg text-sm">Sair</a>
                </div>
            </div>
            <div class="-mr-2 flex md:hidden">
                <!-- Botão do menu mobile -->
                <button type="button" id="mobile-menu-button" class="bg-gray-800 inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-800 focus:ring-white" aria-controls="mobile-menu" aria-expanded="false">
                    <span class="sr-only">Abrir menu principal</span>
                    <svg class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    <svg class="hidden h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Menu Mobile, mostra/esconde com base no estado do menu. -->
    <div class="hidden md:hidden" id="mobile-menu">
        <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
            <?php if ($is_admin): ?>
                <a href="admin.php" class="text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Painel Admin</a>
            <?php endif; ?>
            <a href="dashboard.php" class="relative text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">
                <span>Minhas Ocorrências</span>
                <?php if ($unread_messages_count > 0): ?>
                    <span class="absolute top-1/2 -translate-y-1/2 right-3 flex h-3 w-3"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span><span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span></span>
                <?php endif; ?>
            </a>
            <a href="logout.php" class="text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Sair</a>
        </div>
    </div>
</nav>
</header>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const btn = document.getElementById('mobile-menu-button');
        const menu = document.getElementById('mobile-menu');
        const icons = btn.getElementsByTagName('svg');

        btn.addEventListener('click', () => {
            menu.classList.toggle('hidden');
            icons[0].classList.toggle('hidden'); // Ícone de hambúrguer
            icons[1].classList.toggle('hidden'); // Ícone de 'X'
        });
    });
</script>