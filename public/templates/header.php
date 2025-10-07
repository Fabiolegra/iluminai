<?php
// Garante que a sessão seja iniciada em todas as páginas que incluem este header.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pega o nome do script atual para lógica condicional
$current_page = basename($_SERVER['SCRIPT_NAME']);
?>
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
                <?php if (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'admin'): ?>
                    <a href="admin.php" class="text-gray-300 hover:text-white font-semibold text-sm">Painel Admin</a>
                <?php endif; ?>
                <a href="dashboard.php" class="text-gray-300 hover:text-white font-semibold text-sm">Minhas Ocorrências</a>
                <a href="logout.php" class="bg-gray-700 hover:bg-gray-600 text-gray-200 font-semibold py-2 px-4 rounded-lg text-sm">Sair</a>
            </div>
        </div>
    </div>
</nav>