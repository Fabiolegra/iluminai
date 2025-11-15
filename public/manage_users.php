<?php
// Carrega o bootstrap da aplicação (autoloader, .env, sessão)
require_once __DIR__ . '/../bootstrap.php';

// 1. Proteção da página: Apenas administradores logados
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    $_SESSION['error_msg'] = "Acesso negado. Você não tem permissão para acessar esta página.";
    header("location: index.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';

// Busca todos os usuários que são administradores ou operadores
$sql_users = "SELECT id, nome, email, tipo, created_at FROM users WHERE tipo IN ('admin', 'operador') ORDER BY tipo, nome";
$users = $conn->query($sql_users)->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - IluminAI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-300">
    <!-- Navbar -->
    <?php require_once 'templates/header.php'; ?>

    <main class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-gray-100 mb-8">Gerenciar Usuários</h1>

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
                <!-- Coluna da Lista de Usuários -->
                <div class="lg:col-span-2">
                    <div class="bg-gray-800 border border-gray-700 rounded-lg shadow-md overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-700">
                            <thead class="bg-gray-800">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Nome</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Tipo</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Criado em</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700">
                                <?php if (empty($users)): ?>
                                    <tr><td colspan="4" class="px-6 py-4 text-center text-gray-400">Nenhum operador ou administrador encontrado.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr class="hover:bg-gray-700/50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-100"><?php echo htmlspecialchars($user['nome']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400"><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $user['tipo'] === 'admin' ? 'bg-blue-500/20 text-blue-400' : 'bg-yellow-500/20 text-yellow-400'; ?>">
                                                    <?php echo htmlspecialchars(ucfirst($user['tipo'])); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Coluna do Formulário -->
                <div class="lg:col-span-1">
                    <div class="bg-gray-800 border border-gray-700 p-6 rounded-lg shadow-lg">
                        <h3 class="text-xl font-bold text-gray-100 mb-4">Criar Novo Operador</h3>
                        <form action="../src/actions/process_create_user.php" method="post" class="space-y-4">
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
                            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg">Criar Operador</button>
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