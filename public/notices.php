<?php
/**
 * Página de Avisos e Notificações
 * Exibe todos os avisos enviados pelo admin para o usuário
 */
require_once __DIR__ . '/../src/actions/logic_notices.php';

// Redireciona admins para a página de avisos do admin
if (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'admin') {
    header("location: admin_notices.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Avisos - IluminAI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-300">
    <?php require_once 'templates/header.php'; ?>

    <main class="py-10">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Cabeçalho -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-100 mb-2">Meus Avisos</h1>
                <p class="text-gray-400">
                    <?php echo $avisos_nao_lidos > 0 ? "Você tem <strong>$avisos_nao_lidos</strong> aviso(s) não lido(s)." : "Todos os seus avisos foram lidos."; ?>
                </p>
            </div>

            <!-- Lista de Avisos -->
            <div class="space-y-4">
                <?php if (empty($avisos)): ?>
                    <div class="bg-gray-800 border border-gray-700 p-8 rounded-lg text-center">
                        <p class="text-gray-400 text-lg">Você ainda não tem avisos.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($avisos as $aviso): ?>
                        <div class="bg-gray-800 border <?php echo !$aviso['lido'] ? 'border-yellow-600' : 'border-gray-700'; ?> p-6 rounded-lg hover:bg-gray-700/50 transition-colors">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-grow">
                                    <div class="flex items-center gap-3">
                                        <?php if (!$aviso['lido']): ?>
                                            <span class="inline-block w-3 h-3 bg-yellow-500 rounded-full"></span>
                                        <?php endif; ?>
                                        <h3 class="text-xl font-semibold text-gray-100">
                                            <?php echo htmlspecialchars($aviso['assunto']); ?>
                                        </h3>
                                    </div>
                                    <p class="text-sm text-gray-500 mt-1">
                                        Enviado por <strong><?php echo htmlspecialchars($aviso['admin_nome']); ?></strong> em <?php echo date('d/m/Y \à\s H:i', strtotime($aviso['created_at'])); ?>
                                    </p>
                                </div>
                                <?php if (!$aviso['lido']): ?>
                                    <a href="?mark_read=<?php echo $aviso['id']; ?>" class="text-sm bg-yellow-600 hover:bg-yellow-500 text-white px-3 py-1 rounded transition">
                                        Marcar como lido
                                    </a>
                                <?php else: ?>
                                    <span class="text-xs text-gray-500 px-3 py-1">Lido</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="bg-gray-900/50 p-4 rounded border border-gray-700">
                                <p class="text-gray-300 whitespace-pre-wrap">
                                    <?php echo htmlspecialchars($aviso['mensagem']); ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php $conn->close(); ?>
</body>
</html>
