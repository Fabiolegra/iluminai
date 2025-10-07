<?php
// Inicia a sessão para poder usar variáveis de sessão (para mensagens de erro/sucesso)
session_start();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Usuário</title>
    <!-- Adiciona a CDN do Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-300 flex items-center justify-center min-h-screen">
    <!-- Card de Cadastro -->
    <div class="bg-gray-800 border border-gray-700 p-8 rounded-lg shadow-md w-full max-w-sm">
        <h2 class="text-2xl font-bold mb-2 text-center text-gray-100">Criar Conta</h2>
        <p class="text-gray-400 mb-6 text-center">Preencha os campos para se cadastrar.</p>
        
        <?php 
        if (isset($_SESSION['error_msg'])) {
            // Alerta de erro estilizado com Tailwind
            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">' . htmlspecialchars($_SESSION['error_msg']) . '</div>';
            unset($_SESSION['error_msg']); // Limpa a mensagem para não exibir novamente
        }
        ?>

        <form action="../src/actions/process_register.php" method="post" novalidate>
            <div class="mb-4">
                <label for="nome" class="block text-gray-400 text-sm font-bold mb-2">Nome</label>
                <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($_SESSION['input_nome'] ?? ''); ?>" class="bg-gray-900 border border-gray-600 rounded w-full py-2 px-3 text-gray-200 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div class="mb-4">
                <label for="email" class="block text-gray-400 text-sm font-bold mb-2">E-mail</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_SESSION['input_email'] ?? ''); ?>" class="bg-gray-900 border border-gray-600 rounded w-full py-2 px-3 text-gray-200 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div class="mb-4">
                <label for="senha" class="block text-gray-400 text-sm font-bold mb-2">Senha</label>
                <input type="password" id="senha" name="senha" class="bg-gray-900 border border-gray-600 rounded w-full py-2 px-3 text-gray-200 mb-3 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div class="mb-6">
                <label for="confirmar_senha" class="block text-gray-400 text-sm font-bold mb-2">Confirmar Senha</label>
                <input type="password" id="confirmar_senha" name="confirmar_senha" class="bg-gray-900 border border-gray-600 rounded w-full py-2 px-3 text-gray-200 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div>
                <!-- Cor do botão: bg-blue-600 (#2563EB) com hover mais escuro -->
                <input type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline cursor-pointer" value="Criar conta">
            </div>
            <p class="text-center text-sm text-gray-400 mt-6">
                Já tem uma conta?
                <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500">
                    Faça login aqui
                </a>
            </p>
        </form>
    </div>
    <?php 
        // Limpa os valores de input da sessão após usá-los
        unset($_SESSION['input_nome']);
        unset($_SESSION['input_email']);
    ?>
</body>
</html>