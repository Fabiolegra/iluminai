<?php
/**
 * View para o Dashboard do Administrador.
 * Este arquivo é responsável apenas pela apresentação (HTML).
 * Toda a lógica de negócio e busca de dados é feita em `logic_reset_password.php`.
 */
require_once __DIR__ . '/../src/actions/logic_manager_users.php';
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
            <div class="flex justify-between items-center mb-8"><h1 class="text-3xl font-bold text-gray-100">Gerenciar Usuários</h1><a href="create_operador.php" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg text-sm">Criar Novo Operador</a></div>
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

            <!-- Seção de Filtros -->
            <div class="bg-gray-800 border border-gray-700 rounded-lg p-4 mb-8">
                <form action="manage_users.php" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                    <div class="lg:col-span-1">
                        <label for="email" class="block text-sm font-medium text-gray-400">Buscar por E-mail</label>
                        <input type="text" name="email" id="email" value="<?php echo htmlspecialchars($filtro_email); ?>" placeholder="parte.do@email.com" class="mt-1 block w-full rounded-lg bg-gray-700 border-gray-600 text-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>
                    <div class="lg:col-span-1">
                        <label for="tipo" class="block text-sm font-medium text-gray-400">Filtrar por Tipo</label>
                        <select name="tipo" id="tipo" class="mt-1 block w-full rounded-lg bg-gray-700 border-gray-600 text-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">Todos</option>
                            <option value="admin" <?php echo ($filtro_tipo == 'admin') ? 'selected' : ''; ?>>Administradores</option>
                            <option value="operador" <?php echo ($filtro_tipo == 'operador') ? 'selected' : ''; ?>>Operadores</option>
                            <option value="usuario" <?php echo ($filtro_tipo == 'usuario') ? 'selected' : ''; ?>>Usuários</option>
                        </select>
                    </div>
                    <div class="lg:col-span-2 flex gap-2">
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg text-sm">Filtrar</button><a href="manage_users.php" class="w-full text-center bg-gray-600 hover:bg-gray-500 text-white font-semibold py-2 px-4 rounded-lg text-sm">Limpar</a>
                    </div>
                </form>
            </div>


            <!-- Lista de Usuários -->
            <div class="bg-gray-800 border border-gray-700 rounded-lg shadow-md overflow-hidden">
                <table class="min-w-full divide-y divide-gray-700">
                    <thead class="bg-gray-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Nome</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Tipo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Criado em</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <?php if (empty($users)): ?>
                            <tr><td colspan="5" class="px-6 py-4 text-center text-gray-400">Nenhum usuário encontrado com os filtros aplicados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <?php
                                    $type_colors = [
                                        'admin' => 'bg-blue-500/20 text-blue-400',
                                        'operador' => 'bg-yellow-500/20 text-yellow-400',
                                        'usuario' => 'bg-gray-500/20 text-gray-400'
                                    ];
                                ?>
                                <tr class="hover:bg-gray-700/50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-100"><?php echo htmlspecialchars($user['nome']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400"><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $type_colors[$user['tipo']] ?? 'bg-gray-600'; ?>">
                                            <?php echo htmlspecialchars(ucfirst($user['tipo'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400 space-x-2">
                                        <a href="details_profile.php?id=<?php echo $user['id']; ?>" class="text-blue-400 hover:text-blue-300">Perfil</a>
                                        <?php if ($user['tipo'] === 'operador'): ?>
                                            <form action="../src/actions/delete_user.php" method="POST" class="inline-block" onsubmit="return confirm('Tem certeza que deseja excluir este operador? As ocorrências atribuídas a ele serão desvinculadas.');">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="text-red-400 hover:text-red-300">Excluir</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <?php
        $conn->close();
    ?>
</body>
</html>