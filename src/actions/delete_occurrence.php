<?php
session_start();

// 1. Proteção: Apenas usuários logados podem acessar.
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// 2. Validação: A requisição deve ser POST e conter um ID de ocorrência válido.
if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['ocorrencia_id']) || !filter_var($_POST['ocorrencia_id'], FILTER_VALIDATE_INT)) {
    $_SESSION['error_msg'] = "Requisição inválida.";
    header("location: ../../public/dashboard.php");
    exit;
}

$ocorrencia_id = intval($_POST['ocorrencia_id']);
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['tipo']; // Pega o tipo do usuário (admin ou usuario)

// Inclui o arquivo de conexão com o banco de dados
require_once __DIR__ . '/../../config/database.php';

// 3. Verificação de permissão: Busca a ocorrência para verificar o dono, o status e a foto.
$sql_check = "SELECT user_id, status, foto FROM ocorrencias WHERE id = ?";
if ($stmt_check = $conn->prepare($sql_check)) {
    $stmt_check->bind_param("i", $ocorrencia_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows === 1) {
        $ocorrencia = $result->fetch_assoc();

        // Verifica as permissões de exclusão
        $is_owner = ($ocorrencia['user_id'] === $user_id);
        $is_admin = ($user_type === 'admin');
        $is_pending = ($ocorrencia['status'] === 'pendente');

        // Regra: Admins podem excluir sempre. Usuários normais só podem excluir suas próprias ocorrências pendentes.
        if (!$is_admin && !($is_owner && $is_pending)) {
            $_SESSION['error_msg'] = "Você não tem permissão para excluir esta ocorrência.";
            $stmt_check->close();
            $conn->close();
            // Redireciona para a página de onde veio (ou para o index como fallback)
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '../../public/index.php'));
            exit;
        }

        // 4. Exclusão da ocorrência no banco de dados
        $sql_delete = "DELETE FROM ocorrencias WHERE id = ?";
        if ($stmt_delete = $conn->prepare($sql_delete)) {
            $stmt_delete->bind_param("i", $ocorrencia_id);
            if ($stmt_delete->execute()) {
                // 5. Exclusão do arquivo de foto (se existir)
                // O caminho da foto é relativo à pasta 'public', então precisamos ajustar para o contexto do script
                $foto_path_from_root = __DIR__ . '/../../public/' . $ocorrencia['foto'];
                if (!empty($ocorrencia['foto']) && file_exists($foto_path_from_root)) {
                    unlink($foto_path_from_root);
                } elseif (!empty($ocorrencia['foto']) && file_exists($ocorrencia['foto'])) { // Fallback para caminho relativo
                    unlink($ocorrencia['foto']);
                }
                $_SESSION['success_msg'] = "Ocorrência excluída com sucesso.";
            } else {
                $_SESSION['error_msg'] = "Erro ao excluir a ocorrência.";
            }
            $stmt_delete->close();
        }
    } else {
        $_SESSION['error_msg'] = "Ocorrência não encontrada.";
    }
    $stmt_check->close();
}

$conn->close();

// Redireciona de volta para o dashboard
header("location: ../../public/index.php");
exit;
?>
