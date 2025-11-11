<?php
require_once __DIR__ . '/../../bootstrap.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

// Protege o script
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../../public/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("location: ../../public/profile.php");
    exit;
}

require_once __DIR__ . '/../../config/database.php';

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

// --- AÇÃO: ATUALIZAR NOME ---
if ($action === 'update_name') {
    $novo_nome = trim($_POST['nome']);

    if (empty($novo_nome)) {
        $_SESSION['error_msg'] = "O nome não pode ficar em branco.";
    } else {
        $sql = "UPDATE users SET nome = ? WHERE id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("si", $novo_nome, $user_id);
            if ($stmt->execute()) {
                $_SESSION['success_msg'] = "Seu nome foi atualizado com sucesso!";
            } else {
                $_SESSION['error_msg'] = "Ocorreu um erro ao atualizar seu nome.";
            }
            $stmt->close();
        }
    }
}

// --- AÇÃO: ATUALIZAR SENHA ---
elseif ($action === 'update_password') {
    $senha_atual = $_POST['senha_atual'];
    $nova_senha = $_POST['nova_senha'];
    $confirmar_nova_senha = $_POST['confirmar_nova_senha'];

    if (empty($senha_atual) || empty($nova_senha) || empty($confirmar_nova_senha)) {
        $_SESSION['error_msg'] = "Todos os campos de senha são obrigatórios.";
    } elseif ($nova_senha !== $confirmar_nova_senha) {
        $_SESSION['error_msg'] = "A nova senha e a confirmação não coincidem.";
    } else {
        // 1. Buscar a senha atual do usuário no banco
        $sql_get_pass = "SELECT senha FROM users WHERE id = ?";
        if ($stmt_get = $conn->prepare($sql_get_pass)) {
            $stmt_get->bind_param("i", $user_id);
            $stmt_get->execute();
            $result = $stmt_get->get_result();
            $user = $result->fetch_assoc();
            $stmt_get->close();

            // 2. Verificar se a senha atual fornecida está correta
            if (password_verify($senha_atual, $user['senha'])) {
                // 3. Se estiver correta, atualizar para a nova senha
                $hashed_new_password = password_hash($nova_senha, PASSWORD_DEFAULT);
                $sql_update_pass = "UPDATE users SET senha = ? WHERE id = ?";
                if ($stmt_update = $conn->prepare($sql_update_pass)) {
                    $stmt_update->bind_param("si", $hashed_new_password, $user_id);
                    if ($stmt_update->execute()) {
                        $_SESSION['success_msg'] = "Sua senha foi alterada com sucesso!";
                    } else {
                        $_SESSION['error_msg'] = "Ocorreu um erro ao alterar sua senha.";
                    }
                    $stmt_update->close();
                }
            } else {
                $_SESSION['error_msg'] = "A senha atual está incorreta.";
            }
        }
    }
}

// --- AÇÃO: ATUALIZAR FOTO DE PERFIL ---
elseif ($action === 'update_photo') {
    if (!isset($_FILES['foto_perfil']) || $_FILES['foto_perfil']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error_msg'] = "Nenhum arquivo enviado ou ocorreu um erro no upload.";
        header("location: ../../public/profile.php");
        exit;
    }

    $file_info = $_FILES['foto_perfil'];
    $file_tmp = $file_info['tmp_name'];
    $file_size = $file_info['size'];
    $file_ext = strtolower(pathinfo($file_info['name'], PATHINFO_EXTENSION));

    $allowed_exts = ['jpg', 'jpeg', 'png'];
    $max_size = 2 * 1024 * 1024; // 2MB

    if (!in_array($file_ext, $allowed_exts)) {
        $_SESSION['error_msg'] = "Formato de arquivo inválido. Apenas JPG e PNG são permitidos.";
    } elseif ($file_size > $max_size) {
        $_SESSION['error_msg'] = "O arquivo é muito grande. O tamanho máximo é de 2MB.";
    } else {
        // Configura o cliente S3
        $s3Client = new S3Client([
            'version'     => 'latest',
            'region'      => $_ENV['AWS_REGION'],
            'credentials' => [
                'key'    => $_ENV['AWS_ACCESS_KEY_ID'],
                'secret' => $_ENV['AWS_SECRET_ACCESS_KEY'],
            ],
        ]);

        $bucket = $_ENV['AWS_BUCKET'];
        $key = 'profiles/user_' . $user_id . '_' . uniqid() . '.' . $file_ext;

        try {
            // 1. Busca a foto antiga para deletar depois
            $stmt_get = $conn->prepare("SELECT foto_perfil FROM users WHERE id = ?");
            $stmt_get->bind_param("i", $user_id);
            $stmt_get->execute();
            $old_photo_url = $stmt_get->get_result()->fetch_assoc()['foto_perfil'];
            $stmt_get->close();

            // 2. Faz o upload do novo arquivo para o S3
            $result = $s3Client->putObject([
                'Bucket'     => $bucket,
                'Key'        => $key,
                'SourceFile' => $file_tmp,
            ]);
            $new_photo_url = $result['ObjectURL'];

            // 3. Atualiza o caminho no banco de dados
            $stmt_update = $conn->prepare("UPDATE users SET foto_perfil = ? WHERE id = ?");
            $stmt_update->bind_param("si", $new_photo_url, $user_id);
            $stmt_update->execute();
            $stmt_update->close();

            // 4. Deleta a foto antiga do S3, se existir
            if ($old_photo_url) {
                // Extrai o 'key' (caminho do objeto no bucket) a partir da URL completa.
                // Ex: https://bucket.s3.region.amazonaws.com/profiles/user_1_abc.jpg -> profiles/user_1_abc.jpg
                $old_key = ltrim(parse_url($old_photo_url, PHP_URL_PATH), '/');
                
                $s3Client->deleteObject(['Bucket' => $bucket, 'Key' => $old_key]);
            }

            $_SESSION['success_msg'] = "Foto de perfil atualizada com sucesso!";
        } catch (AwsException $e) {
            $_SESSION['error_msg'] = "Erro ao fazer upload para o S3: " . $e->getMessage();
        }
    }
}

else {
    $_SESSION['error_msg'] = "Ação inválida.";
}

$conn->close();

// Redireciona de volta para a página de perfil
header("location: ../../public/profile.php");
exit;
?>