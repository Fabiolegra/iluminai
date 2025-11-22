<?php
/**
 * View para o Dashboard do Usuário.
 * Este arquivo é responsável apenas pela apresentação (HTML).
 * Toda a lógica de negócio e busca de dados é feita em `logic_my_occurence.php`.
 */ 
require_once __DIR__ . '/../src/actions/logic_my_occurence.php';
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
                <h1 class="text-3xl font-bold text-gray-100"><?php echo $is_admin ? 'Conversas Ativas' : ($is_operator ? 'Ocorrências Atribuídas' : 'Minhas Ocorrências'); ?></h1>
                <?php if (!$is_operator): ?>
                    <a href="report_occurrence.php" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg shadow">Nova Ocorrência</a>
                <?php endif; ?>
            </div>

            <?php if (isset($_SESSION['success_msg'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <?php echo htmlspecialchars($_SESSION['success_msg']); unset($_SESSION['success_msg']); ?>
                </div>
            <?php endif; ?>

            <div class="bg-gray-800 border border-gray-700 shadow-lg rounded-lg overflow-hidden">
                <div class="divide-y divide-gray-700">
                    <?php if (empty($ocorrencias)): ?>
                        <p class="p-6 text-center text-gray-400"><?php echo $is_admin ? 'Nenhuma conversa ativa no momento.' : ($is_operator ? 'Nenhuma ocorrência atribuída a você no momento.' : 'Você ainda não reportou nenhuma ocorrência.'); ?></p>
                    <?php else: ?>
                        <?php foreach ($ocorrencias as $ocorrencia): ?>
                            <div class="p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between hover:bg-gray-700/50 transition-colors">
                                <div class="flex-grow mb-4 sm:mb-0">
                                    <div class="flex items-center gap-3">
                                        <span class="px-3 py-1 text-xs font-semibold rounded-full <?php echo $status_colors[$ocorrencia['status']] ?? 'bg-gray-700 text-gray-200'; ?>">
                                            <?php echo htmlspecialchars(ucfirst($ocorrencia['status'])); ?>
                                        </span>
                                        <a href="details.php?id=<?php echo $ocorrencia['id']; ?>" class="text-lg font-semibold text-gray-100 capitalize hover:underline">
                                            <?php echo htmlspecialchars($ocorrencia['tipo']); ?> (Ocorrência #<?php echo $ocorrencia['id']; ?>)
                                        </a>
                                    </div>
                                    <p class="text-sm text-gray-400 mt-1 ml-1">
                                        <?php if ($is_admin || $is_operator): ?>
                                            Reportado por <strong><?php echo htmlspecialchars($ocorrencia['user_nome']); ?></strong> em <?php echo date('d/m/Y', strtotime($ocorrencia['created_at'])); ?>
                                        <?php else: ?>
                                            Reportado em: <?php echo date('d/m/Y \à\s H:i', strtotime($ocorrencia['created_at'])); ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 flex-shrink-0 w-full sm:w-auto mt-4 sm:mt-0">
                                    <?php if (!empty($ocorrencia['unread_count']) && $ocorrencia['unread_count'] > 0): ?>
                                        <a href="details.php?id=<?php echo $ocorrencia['id']; ?>" class="flex items-center gap-2 w-full sm:w-auto text-center bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg text-sm">
                                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zM9 11a1 1 0 100-2 1 1 0 000 2zm-3 0a1 1 0 100-2 1 1 0 000 2zm6 0a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" /></svg>
                                            <span><?php echo $ocorrencia['unread_count']; ?> Nova(s)</span>
                                        </a>
                                    <?php else: ?>
                                        <a href="details.php?id=<?php echo $ocorrencia['id']; ?>" class="w-full sm:w-auto text-center bg-gray-700 hover:bg-gray-600 text-gray-200 font-semibold py-2 px-4 rounded-lg text-sm">Detalhes</a>
                                    <?php endif; ?>
                                    <?php if (!$is_admin && $ocorrencia['status'] === 'pendente'): ?>
                                        <form action="../src/actions/delete_occurrence.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta ocorrência?');" class="w-full sm:w-auto">
                                            <input type="hidden" name="ocorrencia_id" value="<?php echo $ocorrencia['id']; ?>">
                                            <button type="submit" class="w-full sm:w-auto text-center bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg text-sm min-w-[100px]">Excluir</button>
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
<?php
    $conn->close();
?>
</html>
        </div>
    </main>
</html>