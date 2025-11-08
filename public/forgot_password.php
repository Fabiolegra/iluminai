<?php
require_once __DIR__ . '/../bootstrap.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - IluminAI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-300 flex items-center justify-center min-h-screen">
    <div class="bg-gray-800 border border-gray-700 p-6 rounded-lg shadow-lg w-full max-w-sm">
        <h2 class="text-2xl font-bold mb-2 text-center text-gray-100">Recuperar Senha</h2>
        <p class="text-gray-400 mb-6 text-center">Digite seu e-mail para receber as instruções.</p>
        
        <?php 
        if (isset($_SESSION['success_msg'])) {
            echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">' . htmlspecialchars($_SESSION['success_msg']) . '</div>';
            unset($_SESSION['success_msg']);
        }
        if (isset($_SESSION['error_msg'])) {
            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">' . htmlspecialchars($_SESSION['error_msg']) . '</div>';
            unset($_SESSION['error_msg']);
        }
        ?>

        <form action="../src/actions/process_forgot_password.php" method="post" novalidate>
            <div class="mb-4">
                <label for="email" class="block text-gray-400 text-sm font-bold mb-2">E-mail</label>
                <input type="email" id="email" name="email" class="bg-gray-900 border border-gray-600 rounded w-full py-2 px-3 text-gray-200 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div class="mb-6">
                <input type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline cursor-pointer" value="Enviar Link de Recuperação">
            </div>
            <p class="text-center text-sm text-gray-400"><a href="login.php" class="font-medium text-blue-600 hover:text-blue-500">Voltar para o Login</a></p>
        </form>
    </div>
</body>
</html>