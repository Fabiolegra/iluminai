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
  <!-- CSS e JS do Mapbox Geocoder -->
  <script src="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v5.0.0/mapbox-gl-geocoder.min.js"></script>
  <link rel="stylesheet" href="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v5.0.0/mapbox-gl-geocoder.css" type="text/css">

  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body { margin:0; padding:0; font-family: Arial, sans-serif; }
    #map { position: absolute; top: 0; bottom: 0; width: 100%; }
    /* Estilo dos marcadores no mapa */
    .marker-assigned {
        border: 3px solid white;
        box-shadow: 0 0 10px 3px #ffffff70;
    }
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
    /* Estilo para o campo de busca do Geocoder */
    .mapboxgl-ctrl-geocoder {
        width: 100%;
        max-width: 350px;
    }
    /* Customização do Geocoder */
    .mapboxgl-ctrl-geocoder input {
        background-color: #1f2937 !important;
        color: #d1d5db !important;
        border: 2px solid #4b5563 !important;
        border-radius: 0.5rem !important;
        padding: 0.75rem 1rem !important;
        padding-right: 2.5rem !important;
        font-size: 0.95rem !important;
        transition: all 0.3s ease !important;
    }
    .mapboxgl-ctrl-geocoder input:focus {
        background-color: #111827 !important;
        color: #ffffff !important;
        border-color: #3b82f6 !important;
        box-shadow: 0 0 10px rgba(59, 130, 246, 0.3) !important;
        outline: none !important;
    }
    .mapboxgl-ctrl-geocoder input::placeholder {
        color: #f2f4f7ff !important;
    }
    .mapboxgl-ctrl-geocoder button {
        background-color: #3b82f6 !important;
        border: none !important;
    }
    .mapboxgl-ctrl-geocoder button:hover {
        background-color: #2563eb !important;
    }
    .mapboxgl-ctrl-geocoder .suggestions {
        background-color: #1f2937 !important;
        border: 1px solid #374151 !important;
        border-radius: 0.5rem !important;
    }
    .mapboxgl-ctrl-geocoder .suggestions > div {
        color: #d1d5db !important;
        border-bottom: 1px solid #374151 !important;
        padding: 0.75rem 1rem !important;
        cursor: pointer !important;
    }
    .mapboxgl-ctrl-geocoder .suggestions > div:hover {
        background-color: #374151 !important;
        color: #ffffff !important;
    }
    /* Ajuste para o Geocoder não ficar sob o header */
    .mapboxgl-ctrl-top-left {
        margin-top: 5rem; /* 80px, para dar espaço abaixo do header h-16 (64px) */
        margin-left: 1rem;
    }
    .mapboxgl-ctrl-top-right {
        display: none;
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

    /* Painel de Filtros para Operador */
    #filter-panel { position: fixed; top: 140px; right: 20px; z-index: 20; background-color: #1f2937; color: #d1d5db; padding: 1rem; border-radius: 0.5rem; border: 1px solid #374151; box-shadow: 0 4px 6px rgba(0,0,0,0.3); display: none; }
    #filter-panel h3 { font-weight: bold; font-size: 0.9rem; margin-bottom: 0.75rem; color: #ffffff; }
    #filter-panel label { display: flex; align-items: center; gap: 0.5rem; cursor: pointer; margin-bottom: 0.5rem; font-size: 0.9rem; }
    #filter-panel input[type="radio"] { cursor: pointer; }
    #filter-panel label:hover { color: #ffffff; }

    /* Responsivo para dispositivos menores */
    @media (max-width: 768px) {
        .mapboxgl-ctrl-geocoder { max-width: 280px; }
        .mapboxgl-ctrl-geocoder input { padding: 0.6rem 0.8rem !important; font-size: 0.9rem !important; }
        .mapboxgl-ctrl-geocoder .suggestions > div { padding: 0.6rem 0.8rem !important; font-size: 0.9rem !important; }
        
        #filter-panel { top: 80px; right: 10px; padding: 0.75rem; width: 200px; }
        #filter-panel h3 { font-size: 0.8rem; margin-bottom: 0.5rem; }
        #filter-panel label { font-size: 0.8rem; margin-bottom: 0.4rem; }
        
        #route-panel { bottom: 80px; left: 10px; right: 10px; max-width: none; }
        
        .mapboxgl-popup-content { max-width: 250px; font-size: 0.9rem; }
        .mapboxgl-popup-content .font-bold { font-size: 0.95rem; }
        .mapboxgl-popup-content .text-xs { font-size: 0.7rem; }
        
        .marker-icon { width: 28px; height: 28px; }
        .marker-icon svg { width: 16px; height: 16px; }
        
        a[href="report_occurrence.php"] { width: 48px !important; height: 48px !important; bottom: 20px; right: 20px; }
        a[href="report_occurrence.php"] svg { width: 24px; height: 24px; }
    }

    @media (max-width: 480px) {
        .mapboxgl-ctrl-geocoder { max-width: calc(100% - 30px); left: 15px; right: 15px; }
        .mapboxgl-ctrl-geocoder input { padding: 0.5rem 0.7rem !important; font-size: 0.85rem !important; }
        .mapboxgl-ctrl-geocoder .suggestions > div { padding: 0.5rem 0.7rem !important; font-size: 0.8rem !important; }
        
        #filter-panel { top: 70px; right: 5px; left: 5px; width: auto; padding: 0.5rem; }
        #filter-panel h3 { font-size: 0.75rem; margin-bottom: 0.4rem; }
        #filter-panel label { font-size: 0.75rem; gap: 0.3rem; }
        
        #route-panel { bottom: 70px; left: 5px; right: 5px; padding: 0.75rem; max-width: none; font-size: 0.85rem; }
        #route-panel h3 { font-size: 0.9rem; margin-bottom: 0.3rem; }
        #route-panel button { padding: 0.4rem; font-size: 0.8rem; }
        
        .mapboxgl-popup-content { max-width: 220px; font-size: 0.85rem; padding: 0.5rem; }
        .mapboxgl-popup-content .font-bold { font-size: 0.9rem; }
        .mapboxgl-popup-content a, .mapboxgl-popup-content button { font-size: 0.65rem; padding: 0.4px 0.8px; }
        
        .marker-icon { width: 24px; height: 24px; }
        .marker-icon svg { width: 14px; height: 14px; }
        
        a[href="report_occurrence.php"] { width: 44px !important; height: 44px !important; bottom: 16px; right: 16px; }
        a[href="report_occurrence.php"] svg { width: 20px; height: 20px; }
    }
  </style>
</head>
<body>

  <!-- Navbar -->
  <div class="absolute top-0 left-0 right-0 z-10 bg-gray-900/80 backdrop-blur-sm border-b border-gray-700">
      <?php require_once 'templates/header.php'; ?>
  </div>

  <!-- Painel de Filtros para Operador -->
  <?php if ($_SESSION['tipo'] === 'operador'): ?>
    <div id="filter-panel">
      <h3>Filtrar Ocorrências</h3>
      <label>
        <input type="radio" name="filter" value="all" checked onchange="updateOccurrencesFilter(this.value)">
        Todas as Ocorrências
      </label>
      <label>
        <input type="radio" name="filter" value="mine" onchange="updateOccurrencesFilter(this.value)">
        Minhas Ocorrências
      </label>
    </div>
  <?php endif; ?>
  
  <!-- O mapa ocupa a tela inteira -->
  <div id="map"></div>

  <?php if ($_SESSION['tipo'] !== 'operador'): ?>
    <!-- Botão Flutuante para Reportar Problema -->
    <a href="report_occurrence.php" class="fixed bottom-6 right-6 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-full shadow-lg z-10 flex items-center justify-center transition-all" style="width: 56px; height: 56px;" title="Reportar Problema">
      <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
    </a>
  <?php endif; ?>

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
    // map.addControl(new mapboxgl.NavigationControl(), 'top-left');

    // Adiciona o controle de busca de endereço (Geocoder)
    const geocoder = new MapboxGeocoder({
        accessToken: mapboxgl.accessToken,
        mapboxgl: mapboxgl,
        marker: false, // Não adiciona um marcador permanente no resultado da busca
        placeholder: 'Buscar endereço...',
        bbox: [-54.80, -2.55, -54.60, -2.33], // Limita a busca à área de Santarém
        proximity: { // Influencia os resultados para serem mais próximos deste ponto
            longitude: -54.71,
            latitude: -2.44
        }
    });
    map.addControl(geocoder, 'top-left');
    // map.addControl(new mapboxgl.NavigationControl(), 'top-left');
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
    map.addControl(geolocate, 'bottom-right');

    // Variável global para armazenar o marcador do usuário
    window.userLocationMarker = null;

    // Event listener para quando a localização é obtida com sucesso
    map.on('geolocate', function(e) {
        const coords = e.coords;
        console.log('Posição do usuário:', coords.latitude, coords.longitude);
        
        // Remove marcador anterior se existir
        if (window.userLocationMarker) {
            window.userLocationMarker.remove();
        }
        
        // Cria um marcador customizado para a localização do usuário
        const userEl = document.createElement('div');
        userEl.style.width = '30px';
        userEl.style.height = '30px';
        userEl.style.backgroundColor = '#3B82F6';
        userEl.style.borderRadius = '50%';
        userEl.style.border = '3px solid white';
        userEl.style.boxShadow = '0 0 10px rgba(59, 130, 246, 0.5)';
        userEl.style.cursor = 'pointer';
        userEl.className = 'user-location-marker';
        
        // Adiciona o marcador ao mapa
        window.userLocationMarker = new mapboxgl.Marker(userEl)
            .setLngLat([coords.longitude, coords.latitude])
            .setPopup(new mapboxgl.Popup().setText('Sua Localização Atual'))
            .addTo(map);
    });
    
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
            const isAssignedOperator = currentUserType === 'operador' && currentUserId === ocorrencia.operador_id;
            // Verifica se o usuário atual pode ver os detalhes (admin, dono ou operador atribuído)
            const canSeeDetails = currentUserType === 'admin' || currentUserId === ocorrencia.user_id || isAssignedOperator;
            
            let detailsLink = '';
            if (canSeeDetails) {
              // Para o operador, o link de detalhes só aparece se a ocorrência for dele
              detailsLink = `<a href="details.php?id=${ocorrencia.id}" class="bg-gray-600 text-white text-xs font-bold py-1 px-2 rounded hover:bg-gray-700">Detalhes</a>`;
            }

            let traceRouteButton = '';
            if (currentUserType === 'admin' || currentUserType === 'operador') {
                // Passa as coordenadas para a função traceRoute (admin e operador)
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
            el.className = 'marker-icon occurrence-marker';
            // Adiciona uma classe e animação de destaque se for uma ocorrência do operador
            let animationHTML = '';
            if (isAssignedOperator) {
                el.classList.add('marker-assigned');
                animationHTML = '<span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>';
            }
            // Adiciona a animação (se houver) e o ícone. 
            // O ícone é envolvido por um span relativo para que a animação absoluta funcione corretamente por trás.
            const iconSVG = typeIcons[ocorrencia.tipo] || typeIcons['iluminacao apagada'];
            // A animação é adicionada primeiro, e o ícone depois, dentro de um span relativo.
            el.innerHTML = `${animationHTML}<span class="relative flex justify-center items-center">${iconSVG}</span>`;
            
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

    // --- LÓGICA DE FILTRO PARA OPERADORES ---
    let currentFilter = 'all';

    function updateOccurrencesFilter(filter) {
        currentFilter = filter;
        
        // Remove apenas os marcadores de ocorrências (não remove o marcador de localização do usuário)
        const occurrenceMarkers = document.querySelectorAll('.occurrence-marker');
        occurrenceMarkers.forEach(marker => {
            marker.remove();
        });
        
        // Busca as ocorrências com o filtro aplicado
        const url = currentUserType === 'operador' ? `occurrences.php?filter=${filter}` : 'occurrences.php';
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Erro ao buscar ocorrências:', data.error);
                    return;
                }
                
                // Re-adiciona os marcadores com o filtro aplicado
                data.forEach(ocorrencia => {
                    const isAssignedOperator = currentUserType === 'operador' && currentUserId === ocorrencia.operador_id;
                    const canSeeDetails = currentUserType === 'admin' || currentUserId === ocorrencia.user_id || isAssignedOperator;
                    
                    let detailsLink = '';
                    if (canSeeDetails) {
                        detailsLink = `<a href="details.php?id=${ocorrencia.id}" class="bg-gray-600 text-white text-xs font-bold py-1 px-2 rounded hover:bg-gray-700">Detalhes</a>`;
                    }

                    let traceRouteButton = '';
                    if (currentUserType === 'admin' || currentUserType === 'operador') {
                        traceRouteButton = `<button onclick="traceRoute([${ocorrencia.longitude}, ${ocorrencia.latitude}])" class="bg-blue-600 text-white text-xs font-bold py-1 px-2 rounded hover:bg-blue-700">Traçar Rota</button>`;
                    }

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

                    const el = document.createElement('div');
                    el.className = 'marker-icon occurrence-marker';
                    let animationHTML = '';
                    if (isAssignedOperator) {
                        el.classList.add('marker-assigned');
                        animationHTML = '<span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>';
                    }
                    const iconSVG = typeIcons[ocorrencia.tipo] || typeIcons['iluminacao apagada'];
                    el.innerHTML = `${animationHTML}<span class="relative flex justify-center items-center">${iconSVG}</span>`;
                    
                    const svg = el.getElementsByTagName('svg')[0];
                    if (svg) {
                        svg.style.fill = statusColors[ocorrencia.status] || '#808080';
                    }

                    new mapboxgl.Marker(el)
                        .setLngLat([ocorrencia.longitude, ocorrencia.latitude])
                        .setPopup(popup)
                        .addTo(map);
                });
            })
            .catch(error => console.error('Erro na requisição AJAX:', error));
    }

    // Mostra o painel de filtros se for operador
    if (currentUserType === 'operador') {
        document.getElementById('filter-panel').style.display = 'block';
    }

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