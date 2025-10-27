<?php
session_start();

// Protege a página: só usuários logados podem acessar
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Inclui o arquivo de configuração do banco de dados
require_once __DIR__ . '/../config/database.php';

// Busca as ocorrências do usuário logado
$user_id = $_SESSION['user_id'];
$ocorrencias = [];
$sql = "SELECT id, tipo, status, created_at FROM ocorrencias WHERE user_id = ? ORDER BY created_at DESC";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $ocorrencias = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
$conn->close();

// Mapeamento de status para cores do Tailwind CSS
$status_colors = [
    'pendente' => 'bg-yellow-100 text-yellow-800',
    'em andamento' => 'bg-orange-100 text-orange-800',
    'resolvido' => 'bg-green-100 text-green-800',
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Ocorrências - IluminAI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-300">
    <!-- Navbar -->
    <?php require_once 'templates/header.php'; ?>

    <!-- Conteúdo do Dashboard -->
    <main class="py-10">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-100">Minhas Ocorrências</h1>
                <a href="report.php" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg shadow">Nova Ocorrência</a>
            </div>

            <?php if (isset($_SESSION['success_msg'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <?php echo htmlspecialchars($_SESSION['success_msg']); unset($_SESSION['success_msg']); ?>
                </div>
            <?php endif; ?>

            <div class="bg-gray-800 border border-gray-700 shadow-lg rounded-lg overflow-hidden">
                <div class="divide-y divide-gray-700">
                    <?php if (empty($ocorrencias)): ?>
                        <p class="p-6 text-center text-gray-400">Você ainda não reportou nenhuma ocorrência.</p>
                    <?php else: ?>
                        <?php foreach ($ocorrencias as $ocorrencia): ?>
                            <div class="p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between">
                                <div class="flex-grow mb-4 sm:mb-0">
                                    <div class="flex items-center gap-3">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $status_colors[$ocorrencia['status']] ?? 'bg-gray-700 text-gray-200'; ?>">
                                            <?php echo htmlspecialchars(ucfirst($ocorrencia['status'])); ?>
                                        </span>
                                        <h3 class="text-lg font-semibold text-gray-100 capitalize"><?php echo htmlspecialchars($ocorrencia['tipo']); ?></h3>
                                    </div>
                                    <p class="text-sm text-gray-400 mt-1">Reportado em: <?php echo date('d/m/Y \à\s H:i', strtotime($ocorrencia['created_at'])); ?></p>
                                </div>
                                <div class="flex flex-col sm:flex-row items-center gap-2 flex-shrink-0 w-full sm:w-auto mt-4 sm:mt-0">
                                    <a href="details.php?id=<?php echo $ocorrencia['id']; ?>" class="w-full sm:w-auto text-center bg-gray-700 hover:bg-gray-600 text-gray-200 font-semibold py-2 px-4 rounded-lg text-sm">Ver detalhes</a>
                                    <?php if ($ocorrencia['status'] === 'pendente'): ?>
                                        <form action="../src/actions/delete_occurrence.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta ocorrência?');" class="w-full sm:w-auto">
                                            <input type="hidden" name="ocorrencia_id" value="<?php echo $ocorrencia['id']; ?>">
                                            <button type="submit" class="w-full sm:w-auto text-center bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg text-sm">Excluir</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
        </div>
    </main>
</body>
</html>