<?php
// Este arquivo é incluído dentro de 'details.php', então as variáveis
// $comentarios, $ocorrencia_id e $_SESSION estão disponíveis.

// Impede o acesso direto ao arquivo.
if (basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) {
    die('Acesso direto não permitido.');
}
?>
<h3 class="text-lg font-semibold text-gray-200">Bate-papo da Ocorrência</h3>
<div class="space-y-4 flex flex-col">
    <?php if (empty($comentarios)): ?>
        <p class="text-gray-400">Nenhum comentário ainda. Seja o primeiro a enviar uma mensagem!</p>
    <?php else: ?>
        <?php foreach ($comentarios as $comentario): ?>
            <?php
                $is_admin_comment = $comentario['user_tipo'] === 'admin';
                $is_current_user_comment = $comentario['user_id'] === $_SESSION['user_id'];
                $comment_bg = $is_admin_comment ? 'bg-blue-900/50 border-blue-700/50' : 'bg-gray-900/70';
                $comment_align = $is_current_user_comment ? 'ml-auto' : 'mr-auto';
            ?>
            <div class="max-w-md w-fit <?php echo $comment_align; ?>">
                <div class="p-3 rounded-lg border <?php echo $comment_bg; ?>">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-sm font-bold <?php echo $is_admin_comment ? 'text-blue-400' : 'text-gray-200'; ?>">
                            <?php echo htmlspecialchars($comentario['user_nome']); ?>
                            <?php if ($is_admin_comment): ?>
                                <span class="text-xs font-medium bg-blue-600 text-white px-2 py-0.5 rounded-full ml-2">Admin</span>
                            <?php endif; ?>
                        </p>
                        <time class="text-xs text-gray-500"><?php echo date('d/m H:i', strtotime($comentario['created_at'])); ?></time>
                    </div>
                    <p class="text-gray-300 whitespace-pre-wrap"><?php echo htmlspecialchars($comentario['comentario']); ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Formulário para novo comentário -->
<div class="pt-4">
    <form action="details.php?id=<?php echo $ocorrencia_id; ?>" method="POST">
        <label for="comentario" class="block text-sm font-semibold text-gray-400 mb-2">Adicionar um comentário</label>
        <textarea name="comentario" id="comentario" rows="3" class="block w-full rounded-lg border-gray-600 bg-gray-900 text-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Digite sua mensagem aqui..." required></textarea>
        <div class="mt-3 text-right">
            <button type="submit" class="px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">Enviar Mensagem</button>
        </div>
    </form>
</div>