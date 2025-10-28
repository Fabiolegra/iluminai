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
  <title>IluminAI - Mapa de Ocorrências</title>
  <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body { margin:0; padding:0; font-family: Arial, sans-serif; }
    #map { position: absolute; top: 0; bottom: 0; width: 100%; }
    /* Estilo do marcador no mapa */
    .marker-icon { width: 32px; height: 32px; background: #1f2937; /* bg-gray-800 */ border: 1px solid #4b5563; /* bg-gray-600 */ border-radius: 50%; display: flex; justify-content: center; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.4); cursor: pointer; }
    .marker-icon svg { width: 18px; height: 18px; }
    
    /* Estilo do popup (caixa de diálogo) */
    .mapboxgl-popup-content {
        background-color: #1f2937; /* bg-gray-800 */
        color: #d1d5db; /* text-gray-300 */
        border: 1px solid #374151; /* border-gray-700 */
        border-radius: 0.5rem; /* rounded-lg */
        padding: 0.75rem; /* p-3 */
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        max-width: 280px;
    }
    .toast { visibility: hidden; min-width: 250px; margin-left: -125px; text-align: center; border-radius: 8px; padding: 16px; position: fixed; z-index: 20; left: 50%; top: 80px; font-size: 17px; opacity: 0; transition: opacity 0.5s, top 0.5s; }
    .toast.show { visibility: visible; opacity: 1; top: 100px; }
    .toast.success { background-color: #22C55E; color: white; } /* green-500 */
    .toast.error { background-color: #EF4444; color: white; } /* red-500 */

    /* Painel de Rota */
    #route-panel { position: fixed; bottom: 20px; left: 20px; z-index: 20; background-color: #1f2937; /* bg-gray-800 */ color: #d1d5db; /* text-gray-300 */ padding: 1rem; border-radius: 0.5rem; border: 1px solid #374151; /* border-gray-700 */ box-shadow: 0 4px 6px rgba(0,0,0,0.3); max-width: 250px; display: none; }
    #route-panel h3 { font-weight: bold; font-size: 1.1rem; margin-bottom: 0.5rem; }
    #route-panel p { margin-bottom: 0.25rem; }
    #route-panel button { width: 100%; background-color: #4b5563; /* bg-gray-600 */ padding: 0.5rem; border-radius: 0.375rem; margin-top: 0.75rem; }
    #route-panel button:hover { background-color: #6b7280; /* bg-gray-500 */ }
  </style>
</head>
<body>

  <!-- Navbar -->
  <div class="absolute top-0 left-0 right-0 z-10 bg-gray-900/80 backdrop-blur-sm border-b border-gray-700">
      <?php require_once 'templates/header.php'; ?>
  </div>
  
  <!-- O mapa ocupa a tela inteira -->
  <div id="map"></div>

  <!-- Botão Flutuante para Reportar Problema -->
  <a href="report.php" class="fixed bottom-6 right-6 bg-blue-600 hover:bg-blue-500 text-white font-bold p-4 rounded-full shadow-lg z-10 flex items-center justify-center" title="Reportar Problema">
    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
  </a>

  <!-- Painel para exibir informações da rota -->
  <div id="route-panel">
      <h3>Detalhes da Rota</h3>
      <div id="route-instructions"></div>
      <button type="button" onclick="clearRoute()">Limpar Rota</button>
  </div>

  <!-- Container para a notificação (toast) -->
  <div id="toast-notification" class="toast"></div>

  <script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
  <script>
    // A chave é injetada aqui pelo PHP, não fica exposta no código-fonte HTML/JS
    mapboxgl.accessToken = '<?php echo $_ENV['MAPBOX_TOKEN']; ?>';
    const currentUserId = <?php echo json_encode($_SESSION['user_id']); ?>;
    const currentUserType = <?php echo json_encode($_SESSION['tipo']); ?>;

    const map = new mapboxgl.Map({
      container: 'map',
      style: 'mapbox://styles/mapbox/dark-v11', // Alterado para o tema escuro
      center: [-54.71, -2.44], // Centro de Santarém
      zoom: 13,
      maxBounds: [[-54.80, -2.55], [-54.60, -2.33]], // Limita a navegação à área de Santarém
      attributionControl: false // Desativa o controle de atribuição padrão
    });

    // Adiciona os controles de navegação (zoom, rotação)
    map.addControl(new mapboxgl.NavigationControl(), 'top-left');
    // Adiciona um novo controle de atribuição compacto, que não inclui o link "Improve this map"
    map.addControl(new mapboxgl.AttributionControl({ compact: true }), 'bottom-right');

    // Adiciona o controle para o usuário ver sua própria localização
    const geolocate = new mapboxgl.GeolocateControl({
        positionOptions: {
            enableHighAccuracy: true
        },
        trackUserLocation: true, // Segue a localização do usuário
        showUserHeading: true    // Mostra a direção que o usuário está virado
    });
    map.addControl(geolocate, 'top-left');
    
    // Mapeamento de status para cores dos ícones
    const statusColors = {
      pendente: '#FBBF24',  // Amarelo (amber-400)
      'em andamento': '#F97316', // Laranja (orange-500)
      resolvido: '#22C55E'  // Verde (green-500)
    };

    // Mapeamento de status para cores dos BADGES (consistente com details.php)
    const statusBadgeColors = {
        'pendente': 'bg-yellow-500/20 text-yellow-400',
        'em andamento': 'bg-orange-500/20 text-orange-400',
        'resolvido': 'bg-green-500/20 text-green-400',
    };

    // Mapeamento de tipo para ícones SVG
    const typeIcons = {
        'falta de energia': `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5.268l4.06-4.06a1 1 0 011.414 1.414l-4.06 4.06H19a1 1 0 01.95.684l1.7 4.93a1 1 0 01-.248 1.03l-4.06 4.06a1 1 0 01-1.414-1.414l4.06-4.06V14a1 1 0 01-1-1h-5.268l-4.06 4.06a1 1 0 01-1.414-1.414l4.06-4.06H1a1 1 0 01-.95-.684l-1.7-4.93a1 1 0 01.248-1.03l4.06-4.06a1 1 0 011.414 1.414L1.414 8H6a1 1 0 011 1v5.268l-4.06-4.06a1 1 0 01-1.414-1.414l4.06-4.06V2a1 1 0 011.3-.954z" clip-rule="evenodd" /></svg>`,
        'poste tombado': `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099A.75.75 0 019 2.5h2a.75.75 0 01.743.599l.822 3.287A.75.75 0 0112 6.5h-4a.75.75 0 01-.565-.214l-.822-3.287zM11.75 18a.75.75 0 00.75-.75V8.555a.75.75 0 00-1.5 0V17.25a.75.75 0 00.75.75z" clip-rule="evenodd" /></svg>`,
        'iluminacao apagada': `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M11 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM15.657 5.657a1 1 0 00-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM18 10a1 1 0 01-1 1h-1a1 1 0 110-2h1a1 1 0 011 1zM5.05 14.95a1 1 0 001.414 1.414l.707-.707a1 1 0 00-1.414-1.414l-.707.707zM10 18a1 1 0 01-1-1v-1a1 1 0 112 0v1a1 1 0 01-1 1zM4.343 5.657a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414l.707.707zM2 10a1 1 0 01-1 1h-1a1 1 0 110-2h1a1 1 0 011 1zM14.95 14.95a1 1 0 00-1.414 1.414l.707.707a1 1 0 001.414-1.414l-.707-.707zM10 5a5 5 0 100 10 5 5 0 000-10z" /></svg>`,
        'fio solto': `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM7 9a1 1 0 000 2h6a1 1 0 100-2H7z" clip-rule="evenodd" /></svg>`
    };

    // Ao carregar o mapa, busca as ocorrências
    map.on('load', () => {
      // Dispara a geolocalização automaticamente para o usuário ver sua posição
      geolocate.trigger();

      fetch('occurrences.php') // Corrigido: Removido o '/api/' do caminho
        .then(response => response.json())
        .then(data => {
          if (data.error) {
            console.error('Erro ao buscar ocorrências:', data.error);
            return;
          }
          data.forEach(ocorrencia => {
            // Verifica se o usuário atual pode ver os detalhes (é admin ou o dono)
            const canSeeDetails = currentUserType === 'admin' || currentUserId === ocorrencia.user_id;
            
            let detailsLink = '';
            if (canSeeDetails) {
              detailsLink = `<a href="details.php?id=${ocorrencia.id}" class="bg-gray-600 text-white text-xs font-bold py-1 px-2 rounded hover:bg-gray-700">Detalhes</a>`;
            }

            let traceRouteButton = '';
            if (currentUserType === 'admin') {
                // Passa as coordenadas para a função traceRoute
                traceRouteButton = `<button onclick="traceRoute([${ocorrencia.longitude}, ${ocorrencia.latitude}])" class="bg-blue-600 text-white text-xs font-bold py-1 px-2 rounded hover:bg-blue-700">Traçar Rota</button>`;
            }

            // Cria o conteúdo do popup
            const popup = new mapboxgl.Popup({ offset: 25 }).setHTML(
              `<div class="space-y-2 text-sm">
                 <h3 class="font-bold text-base text-gray-100 capitalize">${ocorrencia.tipo}</h3>
                 <p class="text-gray-400 leading-tight">${ocorrencia.descricao}</p>
                 <div class="border-t border-gray-600 pt-2 mt-2 flex justify-between items-center">
                   <span class="px-2 py-0.5 text-xs font-semibold rounded-full ${statusBadgeColors[ocorrencia.status] || 'bg-gray-600 text-gray-200'}">
                     ${ocorrencia.status}
                   </span>
                   <div class="flex items-center gap-3">
                     ${detailsLink} ${traceRouteButton}
                   </div>
                 </div>
               </div>`
            );

            // Cria o elemento do marcador personalizado
            const el = document.createElement('div');
            el.className = 'marker-icon';
            el.innerHTML = typeIcons[ocorrencia.tipo] || typeIcons['iluminacao apagada']; // Icone padrão
            
            // Pega o SVG dentro do elemento e aplica a cor do status
            const svg = el.getElementsByTagName('svg')[0];
            if (svg) {
                svg.style.fill = statusColors[ocorrencia.status] || '#808080'; // Cor padrão cinza
            }

            // Adiciona o marcador ao mapa
            new mapboxgl.Marker(el)
              .setLngLat([ocorrencia.longitude, ocorrencia.latitude])
              .setPopup(popup)
              .addTo(map);
          });
        })
        .catch(error => console.error('Erro na requisição AJAX:', error));
    });

    // --- LÓGICA PARA TRAÇAR ROTA (PARA ADMINS) ---
    const routePanel = document.getElementById('route-panel');
    const instructionsDiv = document.getElementById('route-instructions');
    let userMarker = null;

    // Função para buscar e desenhar a rota no mapa
    async function getRoute(profile, startCoords, destinationCoords) {
        const url = `https://api.mapbox.com/directions/v5/mapbox/${profile}/${startCoords[0]},${startCoords[1]};${destinationCoords[0]},${destinationCoords[1]}?steps=true&geometries=geojson&access_token=${mapboxgl.accessToken}&language=pt-BR`;

        try {
            const response = await fetch(url);
            const data = await response.json();
            const route = data.routes[0];
            const geojson = { type: 'Feature', properties: {}, geometry: route.geometry };

            // Se a camada da rota já existir, atualiza os dados. Senão, cria uma nova.
            if (map.getSource('route')) {
                map.getSource('route').setData(geojson);
            } else {
                map.addLayer({
                    id: 'route', type: 'line', source: { type: 'geojson', data: geojson },
                    layout: { 'line-join': 'round', 'line-cap': 'round' },
                    paint: { 'line-color': '#3887be', 'line-width': 5, 'line-opacity': 0.75 }
                });
            }
            
            // Exibe as instruções da rota no painel
            const duration = Math.round(route.duration / 60); // em minutos
            const distance = (route.distance / 1000).toFixed(2); // em km
            instructionsDiv.innerHTML = `
                <p><span class="font-bold">Duração:</span> ${duration} min</p>
                <p><span class="font-bold">Distância:</span> ${distance} km</p>
            `;
            routePanel.style.display = 'block';

            // Ajusta o mapa para mostrar a rota inteira
            const bounds = new mapboxgl.LngLatBounds(startCoords, startCoords);
            bounds.extend(destinationCoords);
            map.fitBounds(bounds, { padding: 100 });

        } catch (error) {
            console.error('Erro ao buscar rota:', error);
            showToast('Não foi possível traçar a rota.', 'error');
        }
    }

    // Função principal que inicia o processo de traçar a rota
    function traceRoute(destinationCoords) {
        showToast('Obtendo sua localização...', 'success');
        if (!navigator.geolocation) {
            showToast('Geolocalização não suportada.', 'error');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {
                const startCoords = [position.coords.longitude, position.coords.latitude];
                
                // Remove marcador de usuário anterior se existir
                if (userMarker) userMarker.remove();
                // Adiciona marcador da localização inicial do admin
                userMarker = new mapboxgl.Marker({ color: '#2ECC40' }).setLngLat(startCoords).setPopup(new mapboxgl.Popup().setText('Sua Localização')).addTo(map);
                
                showToast('Traçando rota...', 'success');
                getRoute('driving-traffic', startCoords, destinationCoords); // 'driving-traffic' para rota de carro com trânsito
            },
            () => { showToast('Não foi possível obter sua localização. Verifique as permissões.', 'error'); }
        );
    }

    // Função para limpar a rota do mapa e esconder o painel
    function clearRoute() {
        if (map.getSource('route')) map.removeLayer('route');
        if (map.getSource('route')) map.removeSource('route');
        if (userMarker) userMarker.remove();
        routePanel.style.display = 'none';
    }

    // Função para exibir notificações (toast)
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast-notification');
        toast.textContent = message;
        toast.className = `toast show ${type}`; // Adiciona a classe do tipo (success/error)
        setTimeout(() => {
            toast.className = toast.className.replace("show", "");
        }, 4000); // A notificação desaparece após 4 segundos
    }

    // Verifica se há mensagens na sessão e as exibe
    <?php
    if (isset($_SESSION['success_msg'])) {
        echo "showToast('" . addslashes($_SESSION['success_msg']) . "', 'success');";
        unset($_SESSION['success_msg']);
    } elseif (isset($_SESSION['error_msg'])) {
        echo "showToast('" . addslashes($_SESSION['error_msg']) . "', 'error');";
        unset($_SESSION['error_msg']);
    }
    ?>
  </script>
</body>
</html>