<?php
// Carrega o bootstrap da aplicação (autoloader, .env, sessão)
require_once __DIR__ . '/../bootstrap.php';

// 1. Proteção da página
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    // Se não for admin, redireciona para a página principal com uma mensagem de erro (opcional).
    $_SESSION['error_msg'] = "Acesso negado. Você não tem permissão para acessar esta página.";
    header("location: index.php");
    exit;
}

// Inclui o arquivo de configuração do banco de dados
require_once __DIR__ . '/../config/database.php';

// 2. Processamento do formulário de atualização de status
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['ocorrencia_id'], $_POST['status'])) {
        $ocorrencia_id = intval($_POST['ocorrencia_id']);
        $novo_status = $_POST['status'];

        // Validação simples do status
        $status_permitidos = ['pendente', 'em andamento', 'resolvido'];
        if (in_array($novo_status, $status_permitidos)) {
            $sql_update = "UPDATE ocorrencias SET status = ? WHERE id = ?";
            if ($stmt = $conn->prepare($sql_update)) {
                $stmt->bind_param("si", $novo_status, $ocorrencia_id);
                if ($stmt->execute()) {
                    $_SESSION['success_msg'] = "Status da ocorrência #{$ocorrencia_id} atualizado com sucesso!";
                } else {
                    $_SESSION['error_msg'] = "Erro ao atualizar o status.";
                }
                $stmt->close();
            }
        } else {
            $_SESSION['error_msg'] = "Status inválido selecionado.";
        }
    }
    // Redireciona para a página principal para ver o resultado no mapa
    header("location: index.php");
    exit;
}

// 3. Busca de todas as ocorrências com os dados do usuário
$ocorrencias = [];
$sql_select = "SELECT o.id, o.tipo, o.status, o.created_at, u.nome as user_nome 
               FROM ocorrencias o 
               JOIN users u ON o.user_id = u.id 
               ORDER BY o.created_at DESC";

$result = $conn->query($sql_select);
if ($result) {
    $ocorrencias = $result->fetch_all(MYSQLI_ASSOC);
}
$conn->close();

$status_options = ['pendente', 'em andamento', 'resolvido'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Administração - IluminAI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Esconde a thead em telas pequenas */
        @media (max-width: 767px) {
            .responsive-table thead {
                display: none;
            }
            .responsive-table tbody, .responsive-table tr {
                display: block;
            }
        }
    </style>
</head>
<body class="bg-gray-900 text-gray-300">
    <!-- Navbar -->
    <?php require_once 'templates/header.php'; ?>

    <!-- Conteúdo do Painel -->
    <main class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-gray-100 mb-6">Painel Administrativo</h1>

            <?php if (isset($_SESSION['success_msg'])): ?>
                <div class="bg-green-500/20 border border-green-500/30 text-green-400 px-4 py-3 rounded-lg relative mb-4" role="alert">
                    <?php echo htmlspecialchars($_SESSION['success_msg']); unset($_SESSION['success_msg']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_msg'])): ?>
                <div class="bg-red-500/20 border border-red-500/30 text-red-400 px-4 py-3 rounded-lg relative mb-4" role="alert">
                    <?php echo htmlspecialchars($_SESSION['error_msg']); unset($_SESSION['error_msg']); ?>
                </div>
            <?php endif; ?>

            <div class="overflow-x-auto bg-gray-800 border border-gray-700 rounded-lg shadow-md">
                <table class="min-w-full responsive-table ">
                    <thead class="bg-gray-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Usuário</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Tipo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Data</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status / Ação</th>
                        </tr>
                    </thead>
                    <tbody class="space-y-4 md:space-y-0 md:divide-y md:divide-gray-700">
                        <?php if (empty($ocorrencias)): ?>
                            <tr class="block md:table-row"><td colspan="5" class="block md:table-cell px-6 py-4 text-center text-gray-400">Nenhuma ocorrência encontrada.</td></tr>
                        <?php else: ?>
                            <?php foreach ($ocorrencias as $ocorrencia): ?>
                                <tr class="block md:table-row bg-gray-800 p-4 rounded-lg shadow md:bg-transparent md:p-0 md:shadow-none border-b border-gray-700 md:border-none">
                                    <td class="block md:table-cell md:px-6 md:py-4 whitespace-nowrap text-sm font-medium text-gray-100"><a href="details.php?id=<?php echo $ocorrencia['id']; ?>" class="text-blue-400 hover:underline">#<?php echo $ocorrencia['id']; ?></a></td>
                                    <td class="block md:table-cell md:px-6 md:py-4 whitespace-nowrap text-sm text-gray-400"><span class="font-bold text-gray-300 md:hidden">Usuário: </span><?php echo htmlspecialchars($ocorrencia['user_nome']); ?></td>
                                    <td class="block md:table-cell md:px-6 md:py-4 whitespace-nowrap text-sm text-gray-400 capitalize"><span class="font-bold text-gray-300 md:hidden">Tipo: </span><?php echo htmlspecialchars($ocorrencia['tipo']); ?></td>
                                    <td class="block md:table-cell md:px-6 md:py-4 whitespace-nowrap text-sm text-gray-400"><span class="font-bold text-gray-300 md:hidden">Data: </span><?php echo date('d/m/Y H:i', strtotime($ocorrencia['created_at'])); ?></td>
                                    <td class="block md:table-cell md:px-6 md:py-4 whitespace-nowrap text-sm font-medium">
                                        <form action="admin.php" method="POST" class="flex items-center gap-2" onchange="this.querySelector('button').disabled = false;">
                                            <input type="hidden" name="ocorrencia_id" value="<?php echo $ocorrencia['id']; ?>">
                                            <select name="status" class="block w-full rounded-lg bg-gray-700 border-gray-600 text-gray-200 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 text-sm">
                                                <?php foreach ($status_options as $option): ?>
                                                    <option value="<?php echo $option; ?>" <?php echo ($ocorrencia['status'] == $option) ? 'selected' : ''; ?>><?php echo ucfirst($option); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:bg-gray-500 disabled:cursor-not-allowed" disabled>Salvar</button>
                                            <a href="details.php?id=<?php echo $ocorrencia['id']; ?>" class="px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-gray-600 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 text-center">Detalhes</a>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>