<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../config/database.php';

$token = $_GET['token'] ?? '';

// 1. Valida o token
if (empty($token)) {
    $_SESSION['error_msg'] = "Token de redefinição inválido ou não fornecido.";
    header("location: login.php");
    exit;
}

$sql = "SELECT id, token_expires_at FROM users WHERE confirmation_token = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

// Validação do token e da expiração com tratamento de fuso horário
$user = $result->fetch_assoc();
$is_invalid = true; // Assume que é inválido por padrão
if ($user) {
    $expires_at = new DateTime($user['token_expires_at'], new DateTimeZone('America/Sao_Paulo'));
    $now = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
    if ($expires_at > $now) $is_invalid = false;
}
if ($is_invalid) {
    $_SESSION['error_msg'] = "Token inválido ou expirado. Por favor, solicite um novo link de redefinição.";
    header("location: forgot_password.php");
    exit;
}
$stmt->close();

// 2. Processa o formulário de nova senha
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];

    if (empty($senha) || $senha !== $confirmar_senha) {
        $_SESSION['error_msg'] = "As senhas não coincidem ou estão vazias.";
    } else {
        // Atualiza a senha e limpa o token
        $hashed_password = password_hash($senha, PASSWORD_DEFAULT);
        $sql_update = "UPDATE users SET senha = ?, confirmation_token = NULL, token_expires_at = NULL WHERE confirmation_token = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ss", $hashed_password, $token);
        
        if ($stmt_update->execute()) {
            $_SESSION['success_msg'] = "Sua senha foi redefinida com sucesso! Você já pode fazer login com a nova senha.";
            header("location: login.php");
            exit;
        } else {
            $_SESSION['error_msg'] = "Ocorreu um erro ao atualizar sua senha. Tente novamente.";
        }
        $stmt_update->close();
    }
    // Recarrega a página para mostrar o erro
    header("Location: reset_password.php?token=" . htmlspecialchars($token));
    exit;
}

$conn->close();
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