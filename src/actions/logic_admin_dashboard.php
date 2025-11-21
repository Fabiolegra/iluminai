<?php
/**
 * Lógica de Negócio para o Dashboard do Administrador.
 *
 * Este script é responsável por:
 * - Proteger a página contra acesso não autorizado.
 * - Processar envios de formulários (atualização de status).
 * - Lidar com filtros de busca.
 * - Consultar o banco de dados para obter todas as informações necessárias para a view.
 *
 * A view (`dashboard_admin.php`) simplesmente incluirá este arquivo e usará as variáveis
 * preparadas aqui para renderizar o HTML.
 */

// Carrega o bootstrap da aplicação (autoloader, .env, sessão)
require_once __DIR__ . '/../../bootstrap.php';

// 1. Proteção da página
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    // Se não for admin, redireciona para a página principal com uma mensagem de erro.
    $_SESSION['error_msg'] = "Acesso negado. Você não tem permissão para acessar esta página.";
    header("location: index.php");
    exit;
}

// Inclui o arquivo de configuração do banco de dados
require_once __DIR__ . '/../../config/database.php';

// 2. Processamento do formulário de atualização de status
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['ocorrencia_id'], $_POST['status'])) {
        $ocorrencia_id = intval($_POST['ocorrencia_id']);
        $novo_status = $_POST['status'];

        $status_permitidos = ['pendente', 'em andamento', 'resolvido'];

        // Pega o status atual ANTES de atualizar
        $sql_get_status = "SELECT status FROM ocorrencias WHERE id = ?";
        $stmt_get = $conn->prepare($sql_get_status);
        $stmt_get->bind_param("i", $ocorrencia_id);
        $stmt_get->execute();
        $result_status = $stmt_get->get_result();
        $status_anterior = $result_status->fetch_assoc()['status'];
        $stmt_get->close();

        // Só executa se o status for diferente e válido
        if (in_array($novo_status, $status_permitidos) && $novo_status !== $status_anterior) {
            $sql_update = "UPDATE ocorrencias SET status = ? WHERE id = ?";
            if ($stmt = $conn->prepare($sql_update)) {
                $stmt->bind_param("si", $novo_status, $ocorrencia_id);
                if ($stmt->execute()) {
                    $_SESSION['success_msg'] = "Status da ocorrência #{$ocorrencia_id} atualizado com sucesso!";

                    // Adiciona a mudança ao log
                    $sql_log = "INSERT INTO ocorrencias_log (ocorrencia_id, status_anterior, status_novo, alterado_por) VALUES (?, ?, ?, ?)";
                    $stmt_log = $conn->prepare($sql_log);
                    $stmt_log->bind_param("issi", $ocorrencia_id, $status_anterior, $novo_status, $_SESSION['user_id']);
                    $stmt_log->execute();
                    $stmt_log->close();
                } else {
                    $_SESSION['error_msg'] = "Erro ao atualizar o status.";
                }
                $stmt->close();
            }
        } else {
            $_SESSION['error_msg'] = "Status inválido ou idêntico ao atual.";
        }
    }
    // Redireciona de volta para a própria página de admin para evitar reenvio do formulário
    header("location: dashboard_admin.php");
    exit;
}

// 3. Captura e validação dos filtros
$filtro_data_inicio = $_GET['data_inicio'] ?? '';
$filtro_data_fim = $_GET['data_fim'] ?? '';
$filtro_tipo = $_GET['tipo'] ?? '';
$filtro_status = $_GET['status'] ?? '';

$where_clauses = [];
$params = [];
$types = '';

if (!empty($filtro_data_inicio)) {
    $where_clauses[] = "o.created_at >= ?";
    $params[] = $filtro_data_inicio . ' 00:00:00';
    $types .= 's';
}
if (!empty($filtro_data_fim)) {
    $where_clauses[] = "o.created_at <= ?";
    $params[] = $filtro_data_fim . ' 23:59:59';
    $types .= 's';
}
if (!empty($filtro_tipo)) {
    $where_clauses[] = "o.tipo = ?";
    $params[] = $filtro_tipo;
    $types .= 's';
}
if (!empty($filtro_status)) {
    $where_clauses[] = "o.status = ?";
    $params[] = $filtro_status;
    $types .= 's';
}

$where_sql = !empty($where_clauses) ? " WHERE " . implode(" AND ", $where_clauses) : '';

// 4. Busca de dados para a View

// Ocorrências para a tabela principal (com filtros)
$sql_select = "SELECT o.id, o.tipo, o.status, o.created_at, u.nome as user_nome FROM ocorrencias o JOIN users u ON o.user_id = u.id $where_sql ORDER BY o.created_at DESC";
$stmt_ocorrencias = $conn->prepare($sql_select);
if ($stmt_ocorrencias) {
    if (!empty($params)) $stmt_ocorrencias->bind_param($types, ...$params);
    $stmt_ocorrencias->execute();
    $ocorrencias = $stmt_ocorrencias->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_ocorrencias->close();
} else {
    $ocorrencias = [];
}

// Estatísticas para os cards (considerando filtros de data e tipo)
$stats = ['pendente' => 0, 'em_andamento' => 0, 'resolvido' => 0];
$where_sql_stats = $where_sql; // Usa os mesmos filtros
$params_stats = $params;
$types_stats = $types;
$sql_status_count = "SELECT status, COUNT(id) as total FROM ocorrencias o $where_sql_stats GROUP BY status";
$stmt_status_count = $conn->prepare($sql_status_count);
if ($stmt_status_count) {
    if (!empty($params_stats)) $stmt_status_count->bind_param($types_stats, ...$params_stats);
    $stmt_status_count->execute();
    $result_status = $stmt_status_count->get_result();
    while ($row = $result_status->fetch_assoc()) {
        $key = str_replace(' ', '_', $row['status']);
        if (array_key_exists($key, $stats)) $stats[$key] = $row['total'];
    }
    $stmt_status_count->close();
}

// Contagem total de usuários (não afetada por filtros)
$result_users = $conn->query("SELECT COUNT(id) as total FROM users WHERE tipo = 'usuario'");
$stats['total_users'] = $result_users ? $result_users->fetch_assoc()['total'] : 0;

// Dados para o gráfico (com filtros)
$sql_tipos = "SELECT tipo, COUNT(id) as total FROM ocorrencias o $where_sql GROUP BY tipo";
$stmt_tipos = $conn->prepare($sql_tipos);
if ($stmt_tipos) {
    if (!empty($params)) $stmt_tipos->bind_param($types, ...$params);
    $stmt_tipos->execute();
    $ocorrencias_por_tipo = $stmt_tipos->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_tipos->close();
} else {
    $ocorrencias_por_tipo = [];
}

// Dados para os dropdowns de filtro e atividades recentes (não afetados por filtros)
$tipos_distintos = $conn->query("SELECT DISTINCT tipo FROM ocorrencias ORDER BY tipo ASC")->fetch_all(MYSQLI_ASSOC);
$historico_recente = $conn->query("SELECT l.*, o.tipo as ocorrencia_tipo, u.nome as alterado_por_nome FROM ocorrencias_log l JOIN ocorrencias o ON l.ocorrencia_id = o.id JOIN users u ON l.alterado_por = u.id ORDER BY l.created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Opções de status para os formulários
$status_options = ['pendente', 'em andamento', 'resolvido'];

?>