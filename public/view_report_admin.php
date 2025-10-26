<?php
// Inicia a sessão e protege a página
session_start();

// Simula a verificação de um usuário admin
// Em um cenário real, você teria uma lógica mais robusta aqui (ex: $_SESSION['role'] === 'admin')
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Define a chave da API do Mapbox
define('MAPBOX_TOKEN', 'pk.eyJ1Ijoic2dodXMiLCJhIjoiY21nYTV2c3A2MGYwdDJucHg4ZWt3ZGl4NiJ9.6n3z1p6riEzHiu7TfbM4mQ');

// --- DADOS SIMULADOS DA OCORRÊNCIA ---
// Em um cenário real, você buscaria esses dados do banco de dados usando um ID
// Ex: $report = getReportById($_GET['id']);
$report_lat = -2.445833; // Latitude do problema (ex: vindo do DB)
$report_lon = -54.718401; // Longitude do problema (ex: vindo do DB)
$report_address = "Av. Mendonça Furtado, 2946, Santarém, Pará"; // Endereço (vindo do DB)
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Ocorrência - Admin</title>
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-300">
    <!-- Navbar Admin -->
    <?php require_once 'templates/header.php'; // Reutilizando o header principal ?>

    <main class="py-10">
        <div class="max-w-4xl mx-auto bg-gray-800 border border-gray-700 p-8 rounded-lg shadow-lg">
            <h2 class="text-2xl font-bold mb-4 text-gray-100">Detalhes da Ocorrência</h2>
            <p class="mb-6 text-gray-400">Localização: <?php echo htmlspecialchars($report_address); ?></p>

            <!-- Controles e Mapa -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Painel de Controle da Rota -->
                <div class="md:col-span-1 bg-gray-900 p-4 rounded-lg border border-gray-700">
                    <h3 class="font-bold text-lg mb-4">Traçar Rota</h3>
                    <div class="space-y-3">
                        <button id="getCarRouteBtn" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg text-sm transition-colors">
                            Rota de Carro
                        </button>
                        <button id="getMotorcycleRouteBtn" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2 px-4 rounded-lg text-sm transition-colors">
                            Rota de Moto
                        </button>
                    </div>
                    <div id="route-instructions" class="mt-4 text-sm text-gray-400 space-y-2">
                        <p>Clique em um dos botões para traçar a rota a partir da sua localização atual.</p>
                    </div>
                </div>

                <!-- Mapa -->
                <div class="md:col-span-2 w-full h-96 rounded-lg border border-gray-600" id="map"></div>
            </div>
        </div>
    </main>

    <script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
    <script>
        mapboxgl.accessToken = '<?php echo MAPBOX_TOKEN; ?>';

        // Coordenadas do destino (local da ocorrência)
        const destinationCoords = [<?php echo $report_lon; ?>, <?php echo $report_lat; ?>];

        const map = new mapboxgl.Map({
            container: 'map',
            style: 'mapbox://styles/mapbox/dark-v11',
            center: destinationCoords,
            zoom: 15
        });

        // Adiciona o marcador do problema no mapa
        new mapboxgl.Marker({ color: '#FF4136' }) // Cor vermelha para destacar
            .setLngLat(destinationCoords)
            .setPopup(new mapboxgl.Popup().setText('Local da Ocorrência'))
            .addTo(map);

        const instructionsDiv = document.getElementById('route-instructions');

        // Função para buscar e desenhar a rota
        async function getRoute(profile, startCoords) {
            const url = `https://api.mapbox.com/directions/v5/mapbox/${profile}/${startCoords[0]},${startCoords[1]};${destinationCoords[0]},${destinationCoords[1]}?steps=true&geometries=geojson&access_token=${mapboxgl.accessToken}&language=pt-BR`;

            try {
                const response = await fetch(url);
                const data = await response.json();
                const route = data.routes[0];
                const geojson = {
                    type: 'Feature',
                    properties: {},
                    geometry: route.geometry
                };

                // Se a rota já existir no mapa, atualiza os dados
                if (map.getSource('route')) {
                    map.getSource('route').setData(geojson);
                } else { // Senão, adiciona uma nova camada
                    map.addLayer({
                        id: 'route',
                        type: 'line',
                        source: {
                            type: 'geojson',
                            data: geojson
                        },
                        layout: {
                            'line-join': 'round',
                            'line-cap': 'round'
                        },
                        paint: {
                            'line-color': '#3887be',
                            'line-width': 5,
                            'line-opacity': 0.75
                        }
                    });
                }
                
                // Exibe as instruções da rota
                const duration = Math.round(route.duration / 60); // em minutos
                const distance = (route.distance / 1000).toFixed(2); // em km
                instructionsDiv.innerHTML = `
                    <p><span class="font-bold">Duração:</span> ${duration} min</p>
                    <p><span class="font-bold">Distância:</span> ${distance} km</p>
                `;

                // Ajusta o mapa para mostrar a rota inteira
                const bounds = new mapboxgl.LngLatBounds(startCoords, startCoords);
                bounds.extend(destinationCoords);
                map.fitBounds(bounds, { padding: 70 });

            } catch (error) {
                console.error('Erro ao buscar rota:', error);
                instructionsDiv.textContent = 'Não foi possível traçar a rota.';
            }
        }

        // Função principal que inicia o processo
        function traceRoute(profile) {
            instructionsDiv.textContent = 'Obtendo sua localização...';
            if (!navigator.geolocation) {
                instructionsDiv.textContent = 'Geolocalização não suportada.';
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const startCoords = [position.coords.longitude, position.coords.latitude];
                    // Adiciona marcador da localização inicial do admin
                    new mapboxgl.Marker({ color: '#2ECC40' }) // Cor verde
                        .setLngLat(startCoords)
                        .setPopup(new mapboxgl.Popup().setText('Sua Localização'))
                        .addTo(map);
                    
                    instructionsDiv.textContent = `Traçando rota para ${profile}...`;
                    getRoute(profile, startCoords);
                },
                () => {
                    instructionsDiv.textContent = 'Não foi possível obter sua localização. Verifique as permissões.';
                }
            );
        }

        // Event Listeners para os botões
        document.getElementById('getCarRouteBtn').addEventListener('click', () => traceRoute('driving-traffic'));
        document.getElementById('getMotorcycleRouteBtn').addEventListener('click', () => traceRoute('mapbox/motorcycle'));
    </script>
</body>
</html>
