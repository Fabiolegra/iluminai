<?php
// Carrega o bootstrap da aplicação (autoloader, .env, sessão)
require_once __DIR__ . '/../../bootstrap.php';

// 1. Proteção da página
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Valida o ID da ocorrência na URL
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    // Se não houver ID ou não for um número, redireciona para a página principal.
    header("location: index.php");
    exit;
}
$ocorrencia_id = intval($_GET['id']);

// Inclui o arquivo de configuração do banco de dados
require_once __DIR__ . '/../../config/database.php';

// 3. Busca os detalhes da ocorrência (movido para cima para usar nas validações de POST)
$sql_select = "SELECT o.*, u.nome as user_nome, op.nome as operador_nome
               FROM ocorrencias o 
               JOIN users u ON o.user_id = u.id 
               LEFT JOIN users op ON o.operador_id = op.id
               WHERE o.id = ?";
$ocorrencia = null;
if ($stmt = $conn->prepare($sql_select)) {
    $stmt->bind_param("i", $ocorrencia_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $ocorrencia = $result->fetch_assoc();
    }
    $stmt->close();
}

// Variáveis de permissão
$is_admin = ($_SESSION['tipo'] ?? 'usuario') === 'admin';
$is_owner = $ocorrencia ? ($_SESSION['user_id'] === $ocorrencia['user_id']) : false;
$is_assigned_operator = $ocorrencia ? ($_SESSION['user_id'] === $ocorrencia['operador_id']) : false;

// 2. Processamento do formulário de atualização de status (APENAS PARA ADMINS)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 2.1 Processamento de atribuição de operador (APENAS PARA ADMINS)
    if (isset($_POST['assign_operator']) && $_SESSION['tipo'] === 'admin') {
        $operador_id = filter_input(INPUT_POST, 'operador_id', FILTER_VALIDATE_INT);

        if ($operador_id) {
            // Atualiza a ocorrência com o operador e muda o status para "em andamento"
            $sql_assign = "UPDATE ocorrencias SET operador_id = ?, status = 'em andamento' WHERE id = ?";
            if ($stmt_assign = $conn->prepare($sql_assign)) {
                $stmt_assign->bind_param("ii", $operador_id, $ocorrencia_id);
                if ($stmt_assign->execute()) {
                    $_SESSION['success_msg'] = "Operador atribuído e status alterado para 'Em andamento'.";
                    // Adicionar ao log (opcional, mas recomendado)
                } else {
                    $_SESSION['error_msg'] = "Erro ao atribuir operador.";
                }
                $stmt_assign->close();
            }
        } else {
            $_SESSION['error_msg'] = "ID de operador inválido.";
        }
        header("location: details.php?id=" . $ocorrencia_id);
        exit;
    }

    // 2.2 Processamento de mudança de status (Admin ou Operador)
    if (isset($_POST['status'])) {
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

        // Verifica permissão para alterar o status
        $can_update_status = ($is_admin) || // Admin pode sempre
                             ($is_assigned_operator && $novo_status === 'resolvido'); // Operador só pode marcar como resolvido


        // Só executa se o status for diferente e válido
        if ($can_update_status && in_array($novo_status, $status_permitidos) && $novo_status !== $status_anterior) {
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
            $_SESSION['error_msg'] = "Status inválido, idêntico ao atual ou você não tem permissão para esta ação.";
        }
        // Redireciona para a própria página para ver o resultado
        header("location: details.php?id=" . $ocorrencia_id);
        exit;
    }

    // 2.2 Processamento do formulário de novo comentário
    if (isset($_POST['comentario'])) {
        $comentario_texto = trim($_POST['comentario']);
        if (!empty($comentario_texto)) {
            $sql_insert_comment = "INSERT INTO comentarios (ocorrencia_id, user_id, comentario) VALUES (?, ?, ?)";
            if ($stmt_comment = $conn->prepare($sql_insert_comment)) {
                $stmt_comment->bind_param("iis", $ocorrencia_id, $_SESSION['user_id'], $comentario_texto);
                $stmt_comment->execute();
                $stmt_comment->close();
            }
        }
        header("location: details.php?id=" . $ocorrencia_id);
    }
    exit;
}

// 3.0 Busca a lista de operadores para o admin poder atribuir
$operadores = [];
if (($_SESSION['tipo'] ?? 'usuario') === 'admin') {
    $sql_operadores = "SELECT id, nome FROM users WHERE tipo = 'operador' AND status = 'active'";
    $result_operadores = $conn->query($sql_operadores);
    if ($result_operadores) {
        $operadores = $result_operadores->fetch_all(MYSQLI_ASSOC);
    }
}

// 3.1 Busca o histórico de status da ocorrência
$historico = [];
$sql_log = "SELECT l.status_anterior, l.status_novo, l.created_at, u.nome as alterado_por_nome
            FROM ocorrencias_log l
            JOIN users u ON l.alterado_por = u.id
            WHERE l.ocorrencia_id = ?
            ORDER BY l.created_at ASC";
if ($stmt = $conn->prepare($sql_log)) {
    $stmt->bind_param("i", $ocorrencia_id);
    $stmt->execute();
    $historico = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// 3.2 Busca os comentários da ocorrência
$comentarios = [];
$sql_comments = "SELECT c.*, u.nome as user_nome, u.tipo as user_tipo
                 FROM comentarios c
                 JOIN users u ON c.user_id = u.id
                 WHERE c.ocorrencia_id = ?
                 ORDER BY c.created_at ASC";
if ($stmt = $conn->prepare($sql_comments)) {
    $stmt->bind_param("i", $ocorrencia_id);
    $stmt->execute();
    $comentarios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// 4. Atualiza o timestamp de visualização dos comentários para o usuário atual
// Isso "marca como lido" os comentários desta ocorrência
$sql_update_seen = "INSERT INTO comentarios_visualizacao (user_id, ocorrencia_id, last_seen_at) 
                    VALUES (?, ?, NOW()) 
                    ON DUPLICATE KEY UPDATE last_seen_at = NOW()";
if ($stmt_seen = $conn->prepare($sql_update_seen)) {
    $stmt_seen->bind_param("ii", $_SESSION['user_id'], $ocorrencia_id);
    $stmt_seen->execute();
    $stmt_seen->close();
}


// 5. Verificação de segurança: Ocorrência existe? O usuário tem permissão?
if ($ocorrencia === null) {
    // Ocorrência não encontrada, redireciona com erro.
    $_SESSION['error_msg'] = "Ocorrência não encontrada.";
    header("location: index.php");
    exit;
}

if (!$is_admin && !$is_owner && !$is_assigned_operator) {
    // Se não for admin E não for o dono da ocorrência, nega o acesso.
    $_SESSION['error_msg'] = "Acesso negado. Você não tem permissão para ver esta ocorrência.";
    header("location: index.php");
    $conn->close();
    exit;
}

// Mapeamento de status para cores
$status_colors = [
    'pendente' => 'bg-yellow-500/20 text-yellow-400',
    'em andamento' => 'bg-orange-500/20 text-orange-400',
    'resolvido' => 'bg-green-500/20 text-green-400',
];
$status_options = ['pendente', 'em andamento', 'resolvido'];

// Prepara as variáveis de mensagem para a view e limpa a sessão
$success_msg = $_SESSION['success_msg'] ?? null;
$error_msg = $_SESSION['error_msg'] ?? null;
unset($_SESSION['success_msg'], $_SESSION['error_msg']);

?>