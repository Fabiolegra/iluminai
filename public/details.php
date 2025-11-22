<?php
/**
 * Este arquivo é responsável apenas pela apresentação (HTML).
 * Toda a lógica de negócio e busca de dados é feita em `logic_details.php`.
 */
require_once __DIR__ . '/../src/actions/logic_details.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da Ocorrência #<?php echo $ocorrencia['id']; ?> - IluminAI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet">
</head>
<body class="bg-gray-900 text-gray-300">
    <!-- Navbar -->
    <?php require_once 'templates/header.php'; ?>

    <!-- Conteúdo -->
    <main class="py-10">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-gray-100 mb-2">Detalhes da Ocorrência #<?php echo htmlspecialchars($ocorrencia['id']); ?></h1>
            <p class="text-gray-400 mb-6">Reportado por <?php echo htmlspecialchars($ocorrencia['user_nome']); ?> em <?php echo date('d/m/Y \à\s H:i', strtotime($ocorrencia['created_at'])); ?></p>

            <?php if ($success_msg): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-4" role="alert"><?php echo htmlspecialchars($success_msg); ?></div>
            <?php endif; ?>
            <?php if ($error_msg): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-4" role="alert"><?php echo htmlspecialchars($error_msg); ?></div>
            <?php endif; ?>
            
            <div class="bg-gray-800 border border-gray-700 shadow-lg rounded-lg overflow-hidden grid grid-cols-1 md:grid-cols-3 gap-6 p-6">
                <!-- Coluna de Informações -->
                <div class="space-y-4 md:col-span-2">
                    <div><h3 class="text-sm font-semibold text-gray-500">Tipo</h3><p class="text-lg text-gray-100 capitalize"><?php echo htmlspecialchars($ocorrencia['tipo']); ?></p></div>
                    <div><h3 class="text-sm font-semibold text-gray-500">Status</h3><span class="px-3 py-1 text-sm font-semibold rounded-full <?php echo $status_colors[$ocorrencia['status']] ?? 'bg-gray-700 text-gray-200'; ?>"><?php echo htmlspecialchars(ucfirst($ocorrencia['status'])); ?></span></div>
                    <?php if ($ocorrencia['operador_nome']): ?><div><h3 class="text-sm font-semibold text-gray-500">Operador Responsável</h3><p class="text-gray-200"><?php echo htmlspecialchars($ocorrencia['operador_nome']); ?></p></div><?php endif; ?>
                    <div><h3 class="text-sm font-semibold text-gray-500">Descrição</h3><p class="text-gray-300 whitespace-pre-wrap"><?php echo htmlspecialchars($ocorrencia['descricao']); ?></p></div>
                    
                    <?php
                        $fotos = array_filter([$ocorrencia['foto1'], $ocorrencia['foto2'], $ocorrencia['foto3']]);
                        if (!empty($fotos)):
                    ?>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-500 mb-2">Fotos</h3>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                                <?php foreach ($fotos as $foto): ?>
                                    <a href="<?php echo htmlspecialchars($foto); ?>" target="_blank" rel="noopener noreferrer">
                                        <img src="<?php echo htmlspecialchars($foto); ?>" alt="Foto da ocorrência" class="rounded-lg w-full h-32 object-cover border border-gray-700 hover:opacity-90 transition-opacity">
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Seção de Comentários -->
                    <div class="space-y-6 border-t border-gray-700 pt-6">
                        <?php include 'chat_section.php'; ?>
                    </div>
                </div>

                <!-- Coluna da Direita (Mapa, Ações, Histórico) -->
                <div class="md:col-span-1 space-y-6">
                    <!-- Mapa -->
                    <div><h3 class="text-sm font-semibold text-gray-500 mb-2">Localização</h3><div id="map" class="w-full h-64 rounded-lg border border-gray-700"></div></div>

                    <!-- Histórico -->
                    <div class="space-y-4 border-t border-gray-700 pt-6">
                    <h3 class="text-lg font-semibold text-gray-200">Histórico de Alterações</h3>
                    <?php if (empty($historico)): ?>
                        <p class="text-gray-400">Nenhum histórico de alterações para esta ocorrência.</p>
                    <?php else: ?>
                        <div class="flow-root">
                            <ul role="list" class="-mb-8">
                                <?php foreach ($historico as $index => $log): ?>
                                <li>
                                    <div class="relative pb-8">
                                        <?php if ($index !== count($historico) - 1): ?>
                                            <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-600" aria-hidden="true"></span>
                                        <?php endif; ?>
                                        <div class="relative flex space-x-3">
                                            <div><span class="h-8 w-8 rounded-full bg-gray-600 flex items-center justify-center ring-8 ring-gray-800"><svg class="h-5 w-5 text-gray-300" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd" /></svg></span></div>
                                            <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                <div><p class="text-sm text-gray-400"><?php echo $log['status_anterior'] ? 'Status alterado de <strong class="font-medium text-gray-200 capitalize">'.htmlspecialchars($log['status_anterior']).'</strong> para' : 'Ocorrência criada com status'; ?> <strong class="font-medium text-gray-200 capitalize"><?php echo htmlspecialchars($log['status_novo']); ?></strong> por <strong class="font-medium text-gray-200"><?php echo htmlspecialchars($log['alterado_por_nome']); ?></strong></p></div>
                                                <div class="text-right text-sm whitespace-nowrap text-gray-500"><time datetime="<?php echo $log['created_at']; ?>"><?php echo date('d/m/y H:i', strtotime($log['created_at'])); ?></time></div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Coluna do Mapa e Ações -->
                <div class="md:col-span-3 space-y-6">
                    <!-- Formulário de Ação para Admin -->
                    <?php if ($is_admin): ?>
                        <div class="bg-gray-900 p-4 rounded-lg border border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-200 mb-3">Ações do Administrador</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-1 gap-4">
                                <!-- Atribuir Operador -->
                                <form action="details.php?id=<?php echo $ocorrencia_id; ?>" method="POST">
                                    <label for="operador_id" class="block text-sm font-medium text-gray-400 mb-1">Atribuir Operador</label>
                                    <div class="flex items-center gap-2">
                                        <select id="operador_id" name="operador_id" class="block w-full rounded-lg border-gray-600 bg-gray-800 text-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" <?php echo empty($operadores) ? 'disabled' : ''; ?>>
                                            <option value="">Selecione um operador</option>
                                            <?php foreach ($operadores as $operador): ?>
                                                <option value="<?php echo $operador['id']; ?>" <?php echo ($ocorrencia['operador_id'] == $operador['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($operador['nome']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" name="assign_operator" class="px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">Atribuir</button>
                                    </div>
                                    <?php if (empty($operadores)): ?><p class="text-xs text-gray-500 mt-1">Nenhum operador ativo encontrado.</p><?php endif; ?>
                                </form>
                                <!-- Alterar Status Manualmente -->
                            </div>
                        </div>
                    <?php elseif ($is_assigned_operator && $ocorrencia['status'] === 'em andamento'): ?>
                        <!-- Ação para Operador -->
                        <div class="bg-gray-900 p-4 rounded-lg border border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-200 mb-3">Ações do Operador</h3>
                            <form action="details.php?id=<?php echo $ocorrencia_id; ?>" method="POST" onsubmit="return confirm('Tem certeza que deseja marcar esta ocorrência como resolvida?');">
                                <input type="hidden" name="status" value="resolvido">
                                <button type="submit" class="w-full text-center bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg text-sm transition-colors">Marcar como Resolvido</button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <!-- Formulário de Exclusão para Admin ou Dono -->
                    <?php
                        $is_pending = ($ocorrencia['status'] === 'pendente');
                        // Mostra o botão se for admin, ou se for o dono e o status for pendente
                        if ($is_admin || ($is_owner && $is_pending)):
                    ?>
                        <div class="mt-4 flex flex-col sm:flex-row gap-2">
                            <a href="edit_occurrence.php?id=<?php echo $ocorrencia_id; ?>" class="flex-1 text-center bg-yellow-600 hover:bg-yellow-700 text-white font-semibold py-2 px-4 rounded-lg text-sm transition-colors">Editar</a>
                            <form action="../src/actions/delete_occurrence.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta ocorrência? Esta ação não pode ser desfeita.');" class="flex-1">
                                <input type="hidden" name="ocorrencia_id" value="<?php echo $ocorrencia_id; ?>">
                                <button type="submit" class="w-full text-center bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg text-sm transition-colors">Excluir</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
    <script>
        mapboxgl.accessToken = '<?php echo $_ENV['MAPBOX_TOKEN']; ?>';
        const map = new mapboxgl.Map({
            container: 'map',
            style: 'mapbox://styles/mapbox/dark-v11',
            center: [<?php echo $ocorrencia['longitude']; ?>, <?php echo $ocorrencia['latitude']; ?>],
            zoom: 15
        });
        map.addControl(new mapboxgl.NavigationControl(), 'top-left');
        
        // Adiciona um marcador com uma cor de destaque para garantir a visibilidade
        new mapboxgl.Marker({ color: '#3B82F6' }) // Cor azul (blue-500)
            .setLngLat([<?php echo $ocorrencia['longitude']; ?>, <?php echo $ocorrencia['latitude']; ?>])
            .addTo(map);
    </script>
    <?php
        $conn->close();
    ?>
</body>
</html>
