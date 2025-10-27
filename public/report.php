<?php
// Carrega o bootstrap da aplicação (autoloader, .env, sessão)
require_once __DIR__ . '/../bootstrap.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportar Ocorrência - IluminAI</title>
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-300">
    <!-- Navbar -->
    <?php require_once 'templates/header.php'; ?>

    <!-- Formulário -->
    <main class="py-10">
        <div class="max-w-xl mx-auto bg-gray-800 border border-gray-700 p-8 rounded-lg shadow-lg">
            <h2 class="text-2xl font-bold mb-6 text-center text-gray-100">Reportar uma Ocorrência</h2>

            <?php
            if (isset($_SESSION['error_msg'])) {
                echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">' . htmlspecialchars($_SESSION['error_msg']) . '</div>';
                unset($_SESSION['error_msg']);
            }
            ?>

            <form action="../src/actions/process_report.php" method="post" enctype="multipart/form-data" class="space-y-6" novalidate>
                <!-- Tipo do Problema -->
                <div>
                    <label for="tipo" class="block text-gray-400 text-sm font-bold mb-2">Tipo do Problema:</label>
                    <select id="tipo" name="tipo" class="bg-gray-900 border border-gray-600 rounded w-full py-2 px-3 text-gray-200 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="">Selecione um tipo</option>
                        <option value="falta de energia">Falta de energia</option>
                        <option value="poste tombado">Poste tombado</option>
                        <option value="iluminacao apagada">Iluminação apagada</option>
                        <option value="fio solto">Fio solto</option>
                    </select>
                </div>

                <!-- Descrição -->
                <div>
                    <label for="descricao" class="block text-gray-400 text-sm font-bold mb-2">Descrição:</label>
                    <textarea id="descricao" name="descricao" rows="4" class="bg-gray-900 border border-gray-600 rounded w-full py-2 px-3 text-gray-200 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" required></textarea>
                </div>

                <!-- Localização -->
                <div class="p-4 bg-gray-900 rounded-lg border border-gray-700 space-y-3">
                    <label class="block text-gray-400 text-sm font-bold mb-2">Localização da Ocorrência</label>
                    
                    <div id="map" class="w-full h-64 rounded-lg border border-gray-600"></div>
                    <p class="text-xs text-center text-gray-500 !mt-2">Clique no mapa para definir a localização ou use o botão para obter sua localização atual.</p>

                    <button type="button" id="getLocationBtn" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg text-sm transition-colors">Usar minha localização atual</button>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                        <div>
                            <label for="latitude" class="block text-xs font-medium text-gray-500">Latitude</label>
                            <input type="text" id="latitude" name="latitude" class="bg-gray-900 border border-gray-600 rounded w-full py-2 px-3 text-gray-200 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="-2.4400" required>
                        </div>
                        <div>
                            <label for="longitude" class="block text-xs font-medium text-gray-500">Longitude</label>
                            <input type="text" id="longitude" name="longitude" class="bg-gray-900 border border-gray-600 rounded w-full py-2 px-3 text-gray-200 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="-54.7100" required>
                        </div>
                    </div>
                    <div>
                        <label for="endereco" class="block text-xs font-medium text-gray-500">Endereço Aproximado</label>
                        <input type="text" id="endereco" name="endereco" class="bg-gray-800 border border-gray-600 rounded w-full py-2 px-3 text-gray-400 leading-tight" placeholder="O endereço aparecerá aqui..." readonly>
                    </div>

                    <p id="locationStatus" class="text-sm text-gray-400 mt-2 text-center h-4"></p>
                </div>

                <!-- Botão de Envio -->
                <div>
                    <input type="submit" id="submitBtn" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-lg shadow-md focus:outline-none focus:shadow-outline cursor-pointer disabled:bg-gray-400 disabled:cursor-not-allowed" value="Enviar Ocorrência" disabled>
                </div>
            </form>
            <p class="text-xs text-gray-400 text-center mt-4">Por favor, preencha a localização para habilitar o envio.</p>
        </div>
    </main>

    <script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
    <script>
        const getLocationBtn = document.getElementById('getLocationBtn');
        const locationStatus = document.getElementById('locationStatus');
        const latInput = document.getElementById('latitude');
        const lonInput = document.getElementById('longitude');
        const submitBtn = document.getElementById('submitBtn');

        mapboxgl.accessToken = '<?php echo $_ENV['MAPBOX_TOKEN']; ?>';

        const map = new mapboxgl.Map({
            container: 'map',
            style: 'mapbox://styles/mapbox/dark-v11',
            center: [-54.71, -2.44], // Centro de Santarém
            zoom: 13,
            maxBounds: [[-54.80, -2.55], [-54.60, -2.33]]
        });

        map.addControl(new mapboxgl.NavigationControl(), 'top-left');

        let marker = null;

        // Função para atualizar os campos e o marcador
        async function updateLocation(lng, lat) {
            latInput.value = lat.toFixed(7);
            lonInput.value = lng.toFixed(7);

            // Remove o marcador anterior, se existir
            if (marker) {
                marker.remove();
            }
            // Adiciona um novo marcador na posição clicada
            marker = new mapboxgl.Marker().setLngLat([lng, lat]).addTo(map);
            
            await reverseGeocode(lng, lat); // Busca o endereço
            checkLocationFields(); // Habilita o botão de envio
        }

        // Função para converter coordenadas em endereço (Reverse Geocoding)
        async function reverseGeocode(lng, lat) {
            locationStatus.textContent = 'Buscando endereço...';
            const url = `https://api.mapbox.com/geocoding/v5/mapbox.places/${lng},${lat}.json?access_token=${mapboxgl.accessToken}&language=pt-BR`;
            
            try {
                const response = await fetch(url);
                const data = await response.json();
                if (data.features && data.features.length > 0) {
                    const address = data.features[0].place_name;
                    document.getElementById('endereco').value = address;
                    locationStatus.textContent = 'Endereço encontrado!';
                } else {
                    document.getElementById('endereco').value = 'Endereço não encontrado.';
                    locationStatus.textContent = 'Não foi possível encontrar um endereço para esta localização.';
                }
            } catch (error) {
                console.error('Erro no Reverse Geocoding:', error);
                locationStatus.textContent = 'Erro ao buscar endereço.';
            }
        }

        // Evento de clique no mapa
        map.on('click', async (e) => {
            const { lng, lat } = e.lngLat;
            await updateLocation(lng, lat);
        });

        function checkLocationFields() {
            // Habilita o botão de envio se ambos os campos de localização tiverem algum valor
            submitBtn.disabled = !(latInput.value.trim() !== '' && lonInput.value.trim() !== '');
        }

        // Lógica para o botão "Usar minha localização atual"
        getLocationBtn.addEventListener('click', (e) => {
            e.preventDefault(); // Previne o envio do formulário
            if (!navigator.geolocation) {
                locationStatus.textContent = 'Geolocalização não é suportada pelo seu navegador.';
                return;
            }

            locationStatus.textContent = 'Obtendo localização...';
            getLocationBtn.disabled = true;
            getLocationBtn.textContent = 'Obtendo...';

            navigator.geolocation.getCurrentPosition(async (position) => {
                const { latitude, longitude } = position.coords;
                await updateLocation(longitude, latitude);
                map.flyTo({ center: [longitude, latitude], zoom: 16 }); // Centraliza o mapa na localização
                getLocationBtn.disabled = false;
                getLocationBtn.textContent = 'Usar minha localização atual';
            }, () => {
                locationStatus.textContent = 'Não foi possível obter a localização. Verifique as permissões.';
                getLocationBtn.disabled = false;
                getLocationBtn.textContent = 'Usar minha localização atual';
            });
        });

        // Adiciona listeners aos campos de input para verificar em tempo real
        latInput.addEventListener('input', checkLocationFields);
        lonInput.addEventListener('input', checkLocationFields);
    </script>
</body>
</html>