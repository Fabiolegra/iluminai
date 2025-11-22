<?php
/**
 * View para o Dashboard do Administrador.
 * Este arquivo é responsável apenas pela apresentação (HTML).
 * Toda a lógica de negócio e busca de dados é feita em `logic_reset_password.php`.
 */
require_once __DIR__ . '/../src/actions/logic_reset_password.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - IluminAI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-300 flex items-center justify-center min-h-screen">
    <div class="bg-gray-800 border border-gray-700 p-6 rounded-lg shadow-lg w-full max-w-sm">
        <h2 class="text-2xl font-bold mb-6 text-center text-gray-100">Crie uma Nova Senha</h2>
        
        <?php 
        if (isset($_SESSION['error_msg'])) {
            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">' . htmlspecialchars($_SESSION['error_msg']) . '</div>';
            unset($_SESSION['error_msg']);
        }
        ?>

        <form action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>" method="post" novalidate>
            <div class="mb-4">
                <label for="senha" class="block text-gray-400 text-sm font-bold mb-2">Nova Senha</label>
                <input type="password" id="senha" name="senha" class="bg-gray-900 border border-gray-600 rounded w-full py-2 px-3 text-gray-200 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div class="mb-6">
                <label for="confirmar_senha" class="block text-gray-400 text-sm font-bold mb-2">Confirmar Nova Senha</label>
                <input type="password" id="confirmar_senha" name="confirmar_senha" class="bg-gray-900 border border-gray-600 rounded w-full py-2 px-3 text-gray-200 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div>
                <input type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline cursor-pointer" value="Salvar Nova Senha">
            </div>
        </form>
    </div>
</body>
</html>