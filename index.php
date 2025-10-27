<?php
// Carrega o bootstrap da aplicação (autoloader, .env, sessão)
require_once __DIR__ . '/bootstrap.php';

// Se o usuário já estiver logado, redireciona para o dashboard principal
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: public/index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bem-vindo ao IluminAI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-300">

    <div class="min-h-screen flex flex-col items-center justify-center p-4 text-center">
        
        <header class="mb-8">
            <h1 class="text-5xl font-bold text-gray-100 mb-2">
                IluminAI
            </h1>
            <p class="text-lg text-gray-400">
                Reportando problemas de iluminação pública de forma inteligente.
            </p>
        </header>

        <main class="max-w-2xl">
            <p class="mb-8 text-gray-400 leading-relaxed">
                O IluminAI é uma plataforma colaborativa onde você pode reportar problemas como postes com lâmpadas queimadas, falta de energia em uma área ou danos na infraestrutura elétrica. Ao marcar a localização no mapa, você ajuda a prefeitura a identificar e resolver os problemas com mais agilidade, tornando nossa cidade mais segura e bem iluminada.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="public/login.php" class="w-full sm:w-auto bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-lg shadow-md transition-transform transform hover:scale-105">
                    Entrar
                </a>
                <a href="public/register.php" class="w-full sm:w-auto bg-gray-700 hover:bg-gray-600 text-gray-200 font-bold py-3 px-8 rounded-lg shadow-md transition-transform transform hover:scale-105">
                    Criar Conta
                </a>
            </div>
        </main>
        <div class="mt-12 border-t border-gray-700 pt-8 max-w-xs w-full">
            <h3 class="text-sm font-semibold text-gray-500 mb-3">Ações de Desenvolvimento</h3>
            <a href="src/actions/setup_admin.php" target="_blank" class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg shadow-md transition-colors text-sm">
                Setup: Criar Administrador
            </a>
            <p class="text-xs text-gray-500 mt-2">Clique para executar o script que cria o usuário 'admin@iluminai.com'.</p>
        </div>
    </div>
    <footer class="text-center mt-12 text-sm text-gray-500">
            <p>&copy; <?php echo date('Y'); ?> IluminAI. Todos os direitos reservados.</p>
        </footer>
</body>
</html>