<?php
// Carrega o bootstrap da aplicação (autoloader, .env, sessão)
require_once __DIR__ . '/../bootstrap.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Valida o ID da ocorrência na URL
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    header("location: index.php");
    exit;
}
$ocorrencia_id = intval($_GET['id']);

require_once __DIR__ . '/../config/database.php';

// Busca os dados da ocorrência
$sql = "SELECT * FROM ocorrencias WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $ocorrencia_id);
$stmt->execute();
$result = $stmt->get_result();
$ocorrencia = $result->fetch_assoc();
$stmt->close();

// Verificação de permissão
$is_owner = ($ocorrencia['user_id'] === $_SESSION['user_id']);
$is_admin = ($_SESSION['tipo'] === 'admin');
$is_pending = ($ocorrencia['status'] === 'pendente');

if (!$ocorrencia || !($is_admin || ($is_owner && $is_pending))) {
    $_SESSION['error_msg'] = "Você não tem permissão para editar esta ocorrência ou ela não está mais pendente.";
    header("location: dashboard.php");
    exit;
}

$fotos = array_filter([$ocorrencia['foto1'], $ocorrencia['foto2'], $ocorrencia['foto3']]);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Ocorrência - IluminAI</title>
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet">
    <script src="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v5.0.0/mapbox-gl-geocoder.min.js"></script>
    <link rel="stylesheet" href="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v5.0.0/mapbox-gl-geocoder.css" type="text/css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .mapboxgl-ctrl-geocoder { min-width: 100%; }
    </style>
</head>
<body class="bg-gray-900 text-gray-300">
    <!-- Navbar -->
    <?php require_once 'templates/header.php'; ?>

    <!-- Formulário -->
    <main class="py-10">
        <div class="max-w-xl mx-auto bg-gray-800 border border-gray-700 p-8 rounded-lg shadow-lg">
            <h2 class="text-2xl font-bold mb-6 text-center text-gray-100">Editar Ocorrência #<?php echo $ocorrencia_id; ?></h2>

            <?php
            if (isset($_SESSION['error_msg'])) {
                echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">' . htmlspecialchars($_SESSION['error_msg']) . '</div>';
                unset($_SESSION['error_msg']);
            }
            ?>

            <form action="../src/actions/process_edit_occurrence.php" method="post" enctype="multipart/form-data" class="space-y-6" novalidate>
                <input type="hidden" name="ocorrencia_id" value="<?php echo $ocorrencia_id; ?>">
                <!-- Tipo do Problema -->
                <div>
                    <label for="tipo" class="block text-gray-400 text-sm font-bold mb-2">Tipo do Problema:</label>
                    <select id="tipo" name="tipo" class="bg-gray-900 border border-gray-600 rounded w-full py-2 px-3 text-gray-200 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="falta de energia" <?php echo ($ocorrencia['tipo'] == 'falta de energia') ? 'selected' : ''; ?>>Falta de energia</option>
                        <option value="poste tombado" <?php echo ($ocorrencia['tipo'] == 'poste tombado') ? 'selected' : ''; ?>>Poste tombado</option>
                        <option value="iluminacao apagada" <?php echo ($ocorrencia['tipo'] == 'iluminacao apagada') ? 'selected' : ''; ?>>Iluminação apagada</option>
                        <option value="fio solto" <?php echo ($ocorrencia['tipo'] == 'fio solto') ? 'selected' : ''; ?>>Fio solto</option>
                    </select>
                </div>

                <!-- Descrição -->
                <div>
                    <label for="descricao" class="block text-gray-400 text-sm font-bold mb-2">Descrição:</label>
                    <textarea id="descricao" name="descricao" rows="4" class="bg-gray-900 border border-gray-600 rounded w-full py-2 px-3 text-gray-200 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" required><?php echo htmlspecialchars($ocorrencia['descricao']); ?></textarea>
                </div>

                <!-- Fotos Atuais -->
                <?php if (!empty($fotos)): ?>
                <div>
                    <label class="block text-gray-400 text-sm font-bold mb-2">Fotos Atuais (marque para remover):</label>
                    <div class="grid grid-cols-3 gap-4">
                        <?php foreach ($fotos as $foto_url): ?>
                        <div class="relative">
                            <img src="<?php echo htmlspecialchars($foto_url); ?>" class="w-full h-24 object-cover rounded-lg">
                            <label class="absolute top-1 right-1 flex items-center bg-gray-900/70 p-1 rounded-full">
                                <input type="checkbox" name="remover_fotos[]" value="<?php echo htmlspecialchars($foto_url); ?>" class="h-4 w-4 text-red-600 bg-gray-700 border-gray-600 rounded focus:ring-red-500">
                                <span class="text-xs text-white ml-1">Remover</span>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Upload de Novas Fotos -->
                <div>
                    <label for="fotos" class="block text-gray-400 text-sm font-bold mb-2">Adicionar Novas Fotos (opcional, até 3):</label>
                    <input type="file" id="fotos" name="fotos[]" multiple accept="image/png, image/jpeg" 
                           class="block w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700">
                </div>

                <!-- Localização -->
                <div class="p-4 bg-gray-900 rounded-lg border border-gray-700 space-y-3">
                    <label class="block text-gray-400 text-sm font-bold mb-2">Localização da Ocorrência</label>
                    <div id="map" class="relative w-full h-64 rounded-lg border border-gray-600"></div>
                    <p class="text-xs text-center text-gray-500 !mt-2">Clique no mapa para alterar a localização.</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                        <div>
                            <label for="latitude" class="block text-xs font-medium text-gray-500">Latitude</label>
                            <input type="text" id="latitude" name="latitude" value="<?php echo htmlspecialchars($ocorrencia['latitude']); ?>" class="bg-gray-900 border border-gray-600 rounded w-full py-2 px-3 text-gray-200" required>
                        </div>
                        <div>
                            <label for="longitude" class="block text-xs font-medium text-gray-500">Longitude</label>
                            <input type="text" id="longitude" name="longitude" value="<?php echo htmlspecialchars($ocorrencia['longitude']); ?>" class="bg-gray-900 border border-gray-600 rounded w-full py-2 px-3 text-gray-200" required>
                        </div>
                    </div>
                </div>

                <!-- Botão de Envio -->
                <div>
                    <input type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-lg shadow-md cursor-pointer" value="Salvar Alterações">
                </div>
            </form>
        </div>
    </main>

    <script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
    <script>
        const latInput = document.getElementById('latitude');
        const lonInput = document.getElementById('longitude');

        mapboxgl.accessToken = '<?php echo $_ENV['MAPBOX_TOKEN']; ?>';

        const map = new mapboxgl.Map({
            container: 'map',
            style: 'mapbox://styles/mapbox/dark-v11',
            center: [lonInput.value, latInput.value],
            zoom: 15,
            maxBounds: [[-54.80, -2.55], [-54.60, -2.33]]
        });

        map.addControl(new mapboxgl.NavigationControl(), 'top-left');

        const geocoder = new MapboxGeocoder({
            accessToken: mapboxgl.accessToken,
            mapboxgl: mapboxgl,
            marker: false,
            placeholder: 'Buscar endereço para centralizar...',
            bbox: [-54.80, -2.55, -54.60, -2.33],
            proximity: {
                longitude: -54.71,
                latitude: -2.44
            }
        });
        map.addControl(geocoder, 'top-right');

        let marker = new mapboxgl.Marker()
            .setLngLat([lonInput.value, latInput.value])
            .addTo(map);

        function updateLocation(lng, lat) {
            latInput.value = lat.toFixed(7);
            lonInput.value = lng.toFixed(7);

            if (marker) {
                marker.setLngLat([lng, lat]);
            } else {
                marker = new mapboxgl.Marker().setLngLat([lng, lat]).addTo(map);
            }
        }

        map.on('click', (e) => {
            const { lng, lat } = e.lngLat;
            updateLocation(lng, lat);
        });
    </script>
    <?php
        $conn->close();
    ?>
</body>
</html>