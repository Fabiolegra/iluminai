<?php
require_once __DIR__ . '/../../bootstrap.php';

// Proteção: Apenas administradores logados
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    $_SESSION['error_msg'] = "Acesso negado.";
    header("location: ../../public/index.php");
    exit;
}

// Validação do token CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error_msg'] = "Token inválido.";
    header("location: ../../public/index.php");
    exit;
}

require_once __DIR__ . '/../../config/database.php';

// Determina o tipo de envio
$type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? 'single';
$admin_id = $_SESSION['user_id'];
$count_sent = 0;

// Valida e processa cada tipo
switch ($type) {
    case 'single':
        // Envio para pessoa específica
        $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if (!$user_id || !$subject || !$message) {
            $_SESSION['error_msg'] = "Todos os campos são obrigatórios.";
            header("location: ../../public/admin_notices.php");
            exit;
        }

        // Verifica se usuário existe
        $stmt_check = $conn->prepare("SELECT nome FROM users WHERE id = ?");
        $stmt_check->bind_param("i", $user_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows === 0) {
            $_SESSION['error_msg'] = "Usuário não encontrado.";
            $stmt_check->close();
            header("location: ../../public/admin_notices.php");
            exit;
        }
        $user_data = $result_check->fetch_assoc();
        $stmt_check->close();

        // Insere o aviso
        $stmt = $conn->prepare("INSERT INTO avisos (user_id, admin_id, assunto, mensagem, lido) VALUES (?, ?, ?, ?, FALSE)");
        $stmt->bind_param("iiss", $user_id, $admin_id, $subject, $message);
        
        if ($stmt->execute()) {
            $count_sent = 1;
            $_SESSION['success_msg'] = "Aviso enviado com sucesso para {$user_data['nome']}.";
        } else {
            $_SESSION['error_msg'] = "Erro ao enviar aviso.";
        }
        $stmt->close();
        break;

    case 'group':
        // Envio para grupo/tipo
        $user_type = filter_input(INPUT_POST, 'user_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if (!$user_type || !$subject || !$message || !in_array($user_type, ['usuario', 'operador', 'admin'])) {
            $_SESSION['error_msg'] = "Dados inválidos.";
            header("location: ../../public/admin_notices.php");
            exit;
        }

        // Busca todos os usuários do tipo
        $stmt_get = $conn->prepare("SELECT id, nome FROM users WHERE tipo = ?");
        $stmt_get->bind_param("s", $user_type);
        $stmt_get->execute();
        $users = $stmt_get->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_get->close();

        if (empty($users)) {
            $_SESSION['error_msg'] = "Nenhum usuário encontrado deste tipo.";
            header("location: ../../public/admin_notices.php");
            exit;
        }

        // Insere avisos para cada usuário
        foreach ($users as $user) {
            $stmt = $conn->prepare("INSERT INTO avisos (user_id, admin_id, assunto, mensagem, lido) VALUES (?, ?, ?, ?, FALSE)");
            $stmt->bind_param("iiss", $user['id'], $admin_id, $subject, $message);
            
            if ($stmt->execute()) {
                $count_sent++;
            }
            $stmt->close();
        }

        $_SESSION['success_msg'] = "Aviso enviado com sucesso para $count_sent usuário(s).";
        break;

    case 'all':
        // Envio para todos
        $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if (!$subject || !$message) {
            $_SESSION['error_msg'] = "Todos os campos são obrigatórios.";
            header("location: ../../public/admin_notices.php");
            exit;
        }

        // Busca todos os usuários
        $stmt_get = $conn->prepare("SELECT id FROM users");
        $stmt_get->execute();
        $users = $stmt_get->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_get->close();

        if (empty($users)) {
            $_SESSION['error_msg'] = "Nenhum usuário encontrado no sistema.";
            header("location: ../../public/admin_notices.php");
            exit;
        }

        // Insere avisos para todos
        foreach ($users as $user) {
            $stmt = $conn->prepare("INSERT INTO avisos (user_id, admin_id, assunto, mensagem, lido) VALUES (?, ?, ?, ?, FALSE)");
            $stmt->bind_param("iiss", $user['id'], $admin_id, $subject, $message);
            
            if ($stmt->execute()) {
                $count_sent++;
            }
            $stmt->close();
        }

        $_SESSION['success_msg'] = "Aviso enviado com sucesso para $count_sent usuário(s).";
        break;

    default:
        $_SESSION['error_msg'] = "Tipo de envio inválido.";
        break;
}

header("location: ../../public/admin_notices.php");
exit;
?>
