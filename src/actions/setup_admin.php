<?php

// ==================================================================
// SCRIPT DE CONFIGURAÇÃO DO USUÁRIO ADMINISTRADOR
// Execute este script uma vez para criar o usuário admin.
// ==================================================================

// --- DADOS DO ADMINISTRADOR ---
$admin_nome = 'Admin Padrão';
$admin_email = 'admin@iluminai.com';
$admin_senha_texto_plano = 'admin123'; // A senha que será criptografada
// ------------------------------

echo "<!DOCTYPE html><html lang='pt-br'><head><meta charset='UTF-8'><title>Setup Admin</title><script src='https://cdn.tailwindcss.com'></script></head><body class='bg-gray-900 text-gray-300 flex items-center justify-center min-h-screen'><div class='bg-gray-800 border border-gray-700 p-8 rounded-lg shadow-lg w-full max-w-md'>";
echo "<h1 class='text-2xl font-bold text-gray-100 mb-4'>Configuração do Administrador</h1>";

// Carrega o bootstrap para ter acesso ao .env e à conexão com o banco
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../config/database.php';

// 1. Verifica se o usuário administrador já existe
$sql_check = "SELECT id FROM users WHERE email = ?";
if ($stmt_check = $conn->prepare($sql_check)) {
    $stmt_check->bind_param("s", $admin_email);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        // Usuário já existe
        echo "<div class='bg-yellow-500/20 border border-yellow-500/30 text-yellow-400 px-4 py-3 rounded' role='alert'><strong>Aviso:</strong> O usuário administrador com o e-mail '<strong>" . htmlspecialchars($admin_email) . "</strong>' já existe no banco de dados. Nenhuma ação foi tomada.</div>";
    } else {
        // 2. Usuário não existe, então vamos criá-lo
        $sql_insert = "INSERT INTO users (nome, email, senha, tipo) VALUES (?, ?, ?, 'admin')";
        if ($stmt_insert = $conn->prepare($sql_insert)) {
            // Criptografa a senha
            $hashed_password = password_hash($admin_senha_texto_plano, PASSWORD_DEFAULT);

            $stmt_insert->bind_param("sss", $admin_nome, $admin_email, $hashed_password);

            if ($stmt_insert->execute()) {
                echo "<div class='bg-green-500/20 border border-green-500/30 text-green-400 px-4 py-3 rounded' role='alert'><strong>Sucesso!</strong> Usuário administrador criado.<br><strong>E-mail:</strong> " . htmlspecialchars($admin_email) . "<br><strong>Senha:</strong> " . htmlspecialchars($admin_senha_texto_plano) . "</div>";
            } else {
                echo "<div class='bg-red-500/20 border border-red-500/30 text-red-400 px-4 py-3 rounded' role='alert'><strong>Erro:</strong> Não foi possível inserir o usuário no banco de dados.</div>";
            }
            $stmt_insert->close();
        }
    }
    $stmt_check->close();
} else {
    echo "<div class='bg-red-500/20 border border-red-500/30 text-red-400 px-4 py-3 rounded' role='alert'><strong>Erro:</strong> Falha ao preparar a consulta de verificação.</div>";
}

echo "<div class='mt-6 text-center'><a href='../../public/login.php' class='bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg'>Ir para a página de Login</a></div>";
echo "<p class='text-xs text-gray-500 text-center mt-4'><strong>Aviso de segurança:</strong> Após usar este script, é recomendado removê-lo do servidor.</p>";
echo "</div></body></html>";

// Fecha a conexão
$conn->close();
?>
