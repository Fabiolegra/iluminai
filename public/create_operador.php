<?php
// Carrega o bootstrap da aplicação (autoloader, .env, sessão)
require_once __DIR__ . '/../bootstrap.php';

// 1. Proteção da página: Apenas administradores logados
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    $_SESSION['error_msg'] = "Acesso negado. Você não tem permissão para acessar esta página.";
    header("location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Novo Operador - IluminAI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-300">
    <!-- Navbar -->
    <?php require_once 'templates/header.php'; ?>

    <main class="py-10">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="bg-gray-800 border border-gray-700 p-6 rounded-lg shadow-lg">
                <h1 class="text-2xl font-bold text-gray-100 mb-6">Criar Novo Operador</h1>

                <form action="../src/actions/process_create_operador.php" method="post" class="space-y-4">
                    <div>
                        <label for="nome" class="block text-gray-400 text-sm font-bold mb-2">Nome Completo</label>
                        <input type="text" id="nome" name="nome" class="bg-gray-900 border border-gray-600 rounded w-full py-2 px-3 text-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div>
                        <label for="email" class="block text-gray-400 text-sm font-bold mb-2">E-mail</label>
                        <input type="email" id="email" name="email" class="bg-gray-900 border border-gray-600 rounded w-full py-2 px-3 text-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div>
                        <label for="senha" class="block text-gray-400 text-sm font-bold mb-2">Senha Provisória</label>
                        <input type="password" id="senha" name="senha" class="bg-gray-900 border border-gray-600 rounded w-full py-2 px-3 text-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div class="flex items-center gap-4 pt-4">
                        <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg">Criar Operador</button>
                        <a href="manage_users.php" class="w-full text-center bg-gray-600 hover:bg-gray-500 text-white font-semibold py-2 px-4 rounded-lg text-sm">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>
</html>