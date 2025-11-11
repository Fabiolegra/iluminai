<?php
require_once __DIR__ . '/../bootstrap.php';

// Protege a página: só usuários logados podem acessar
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';

// Busca os dados atuais do usuário
$user_id = $_SESSION['user_id'] ?? 0;
$sql = "SELECT nome, email, foto_perfil FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
// Define um avatar padrão se o usuário não tiver foto
$user_avatar = $user['foto_perfil'] ? htmlspecialchars($user['foto_perfil']) : 'https://ui-avatars.com/api/?name=' . urlencode($user['nome']) . '&background=1f2937&color=d1d5db&size=128';
$stmt->close();

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - IluminAI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-300">
    <!-- Navbar -->
    <?php require_once 'templates/header.php'; ?>

    <main class="py-10">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-gray-100 mb-8">Meu Perfil</h1>

            <?php
            if (isset($_SESSION['success_msg'])) {
                echo '<div class="bg-green-500/20 border border-green-500/30 text-green-400 px-4 py-3 rounded-lg relative mb-4" role="alert">' . htmlspecialchars($_SESSION['success_msg']) . '</div>';
                unset($_SESSION['success_msg']);
            }
            if (isset($_SESSION['error_msg'])) {
                echo '<div class="bg-red-500/20 border border-red-500/30 text-red-400 px-4 py-3 rounded-lg relative mb-4" role="alert">' . htmlspecialchars($_SESSION['error_msg']) . '</div>';
                unset($_SESSION['error_msg']);
            }
            ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Coluna da Foto de Perfil -->
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-gray-800 border border-gray-700 p-6 rounded-lg shadow-lg text-center">
                        <img class="w-32 h-32 rounded-full mx-auto mb-4 object-cover border-4 border-gray-700" src="<?php echo $user_avatar; ?>" alt="Foto de Perfil">
                        <h2 class="text-2xl font-bold text-gray-100"><?php echo htmlspecialchars($user['nome']); ?></h2>
                        <p class="text-sm text-gray-400"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                    <div class="bg-gray-800 border border-gray-700 p-6 rounded-lg shadow-lg">
                        <h3 class="text-lg font-bold text-gray-100 mb-4">Alterar Foto de Perfil</h3>
                        <form action="../src/actions/process_profile.php" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="update_photo">
                            <div>
                                <label for="foto_perfil" class="block text-gray-400 text-sm font-bold mb-2">Selecione uma imagem</label>
                                <input type="file" id="foto_perfil" name="foto_perfil" accept="image/png, image/jpeg" class="block w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700" required>
                                <p class="text-xs text-gray-500 mt-1">PNG ou JPG (Máx: 2MB).</p>
                            </div>
                            <button type="submit" class="mt-4 w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg">Salvar Foto</button>
                        </form>
                    </div>
                </div>

                <!-- Coluna dos Formulários -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Formulário de Alteração de Nome -->
                    <div class="bg-gray-800 border border-gray-700 p-6 rounded-lg shadow-lg">
                        <h3 class="text-xl font-bold text-gray-100 mb-4">Alterar Nome</h3>
                        <form action="../src/actions/process_profile.php" method="post" class="space-y-4">
                            <input type="hidden" name="action" value="update_name">
                            <div>
                                <label for="nome" class="block text-gray-400 text-sm font-bold mb-2">Nome</label>
                                <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($user['nome']); ?>" class="bg-gray-900 border border-gray-600 rounded w-full py-2 px-3 text-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            </div>
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg">Salvar Nome</button>
                        </form>
                    </div>

                    <!-- Formulário de Alteração de Senha -->
                    <div class="bg-gray-800 border border-gray-700 p-6 rounded-lg shadow-lg">
                        <h3 class="text-xl font-bold text-gray-100 mb-4">Alterar Senha</h3>
                        <form action="../src/actions/process_profile.php" method="post" class="space-y-4">
                            <input type="hidden" name="action" value="update_password">
                            <div>
                                <label for="senha_atual" class="block text-gray-400 text-sm font-bold mb-2">Senha Atual</label>
                                <input type="password" id="senha_atual" name="senha_atual" class="bg-gray-900 border border-gray-600 rounded w-full py-2 px-3 text-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            </div>
                            <div>
                                <label for="nova_senha" class="block text-gray-400 text-sm font-bold mb-2">Nova Senha</label>
                                <input type="password" id="nova_senha" name="nova_senha" class="bg-gray-900 border border-gray-600 rounded w-full py-2 px-3 text-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            </div>
                            <div>
                                <label for="confirmar_nova_senha" class="block text-gray-400 text-sm font-bold mb-2">Confirmar Nova Senha</label>
                                <input type="password" id="confirmar_nova_senha" name="confirmar_nova_senha" class="bg-gray-900 border border-gray-600 rounded w-full py-2 px-3 text-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            </div>
                            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg">Alterar Senha</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <?php
        $conn->close();
    ?>
</body>
</html>