<?php
require_once __DIR__ . '/../../bootstrap.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

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

// 3. Verificação de permissão: Busca a ocorrência para verificar o dono, o status e as fotos.
$sql_check = "SELECT user_id, status, foto1, foto2, foto3 FROM ocorrencias WHERE id = ?";
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
                // 5. Exclusão das fotos do S3 (se existirem)
                $fotos_para_deletar = array_filter([$ocorrencia['foto1'], $ocorrencia['foto2'], $ocorrencia['foto3']]);

                if (!empty($fotos_para_deletar)) {
                    try {
                        $s3Client = new S3Client([
                            'version'     => 'latest',
                            'region'      => $_ENV['AWS_REGION'],
                            'credentials' => [
                                'key'    => $_ENV['AWS_ACCESS_KEY_ID'],
                                'secret' => $_ENV['AWS_SECRET_ACCESS_KEY'],
                            ],
                        ]);
                        $bucket = $_ENV['AWS_BUCKET'];

                        $objects_to_delete = [];
                        foreach ($fotos_para_deletar as $foto_url) {
                            $key = ltrim(parse_url($foto_url, PHP_URL_PATH), '/');
                            $objects_to_delete[] = ['Key' => $key];
                        }

                        $s3Client->deleteObjects([
                            'Bucket' => $bucket,
                            'Delete' => [
                                'Objects' => $objects_to_delete,
                            ],
                        ]);
                    } catch (AwsException $e) {
                        // Mesmo que a exclusão no S3 falhe, a ocorrência no DB foi excluída.
                        // Apenas registra o erro para depuração.
                        error_log("Erro ao deletar objetos do S3: " . $e->getMessage());
                    }
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
