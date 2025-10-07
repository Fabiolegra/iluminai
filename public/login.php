<?php
// Inicia a sessão
session_start();

// Se o usuário já estiver logado, redireciona para a página principal
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: index.php"); // ou index.html, se for o caso
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-300 flex items-center justify-center min-h-screen">
    <div class="bg-gray-800 border border-gray-700 p-6 rounded-lg shadow-lg w-full max-w-sm">
        <h2 class="text-2xl font-bold mb-6 text-center text-gray-100">Entrar no IluminAI</h2>
        
        <?php 
        if (isset($_SESSION['error_msg'])) {
            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">' . htmlspecialchars($_SESSION['error_msg']) . '</div>';
            unset($_SESSION['error_msg']);
        }
        ?>

        <form action="../src/actions/process_login.php" method="post" novalidate>
            <div class="mb-4">
                <label for="email" class="block text-gray-400 text-sm font-bold mb-2">E-mail</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_SESSION['input_email'] ?? ''); ?>" class="bg-gray-900 border border-gray-600 rounded w-full py-2 px-3 text-gray-200 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div class="mb-6">
                <label for="senha" class="block text-gray-400 text-sm font-bold mb-2">Senha</label>
                <input type="password" id="senha" name="senha" class="bg-gray-900 border border-gray-600 rounded w-full py-2 px-3 text-gray-200 mb-3 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div>
                <input type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline cursor-pointer" value="Entrar">
            </div>
            <p class="text-center text-sm text-gray-400 mt-6">
                Não tem uma conta?
                <a href="register.php" class="font-medium text-blue-600 hover:text-blue-500">
                    Criar conta
                </a>
            </p>
        </form>
    </div>
    <?php 
        unset($_SESSION['input_email']);
    ?>
</body>
</html>