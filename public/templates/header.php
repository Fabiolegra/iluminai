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
            <div class="flex items-center gap-4">
                <?php // Mostra o botão 'Voltar para o Mapa' se não estivermos na página principal (index.php) ?>
                <?php if ($current_page !== 'index.php'): ?>
                    <a href="index.php" class="text-gray-400 hover:text-white flex items-center gap-2 text-sm" title="Voltar para o Mapa">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                        <span class="hidden sm:inline">Mapa</span>
                    </a>
                <?php endif; ?>
                <a href="index.php" class="text-2xl font-bold text-gray-100">IluminAI</a>
            </div>
            <div class="flex items-center gap-4">
                <?php if ($is_admin): ?>
                    <a href="admin.php" class="text-gray-300 hover:text-white font-semibold text-sm">Painel Admin</a>
                <?php endif; ?>

                <!-- Link Minhas Ocorrências com notificação -->
                <a href="dashboard.php" class="relative text-gray-300 hover:text-white font-semibold text-sm px-2 py-1" title="Ver minhas ocorrências e mensagens">
                    <span>Minhas Ocorrências</span>
                    <?php if ($unread_messages_count > 0): ?>
                        <!-- Indicador de Notificação -->
                        <span class="absolute top-0 -right-1 flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                        </span>
                    <?php endif; ?>
                </a>
                
                <a href="logout.php" class="bg-gray-700 hover:bg-gray-600 text-gray-200 font-semibold py-2 px-4 rounded-lg text-sm">Sair</a>
            </div>
        </div>
    </div>
</nav>
</header>