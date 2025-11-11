<?php
require_once __DIR__ . '/../../bootstrap.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../../public/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("location: ../../public/dashboard.php");
    exit;
}

require_once __DIR__ . '/../../config/database.php';

$ocorrencia_id = filter_input(INPUT_POST, 'ocorrencia_id', FILTER_VALIDATE_INT);
if (!$ocorrencia_id) {
    $_SESSION['error_msg'] = "ID de ocorrência inválido.";
    header("location: ../../public/dashboard.php");
    exit;
}

// 1. Busca a ocorrência e verifica a permissão
$stmt_check = $conn->prepare("SELECT * FROM ocorrencias WHERE id = ?");
$stmt_check->bind_param("i", $ocorrencia_id);
$stmt_check->execute();
$result = $stmt_check->get_result();
$ocorrencia = $result->fetch_assoc();
$stmt_check->close();

$is_owner = ($ocorrencia['user_id'] === $_SESSION['user_id']);
$is_admin = ($_SESSION['tipo'] === 'admin');
$is_pending = ($ocorrencia['status'] === 'pendente');

if (!$ocorrencia || !($is_admin || ($is_owner && $is_pending))) {
    $_SESSION['error_msg'] = "Você não tem permissão para editar esta ocorrência.";
    header("location: ../../public/dashboard.php");
    exit;
}

// 2. Validação dos campos do formulário
$tipo = $_POST['tipo'];
$descricao = trim($_POST['descricao']);
$latitude = $_POST['latitude'];
$longitude = $_POST['longitude'];
$remover_fotos = $_POST['remover_fotos'] ?? [];

if (empty($tipo) || empty($descricao) || empty($latitude) || empty($longitude)) {
    $_SESSION['error_msg'] = "Por favor, preencha todos os campos obrigatórios.";
    header("location: ../../public/edit_occurrence.php?id=" . $ocorrencia_id);
    exit;
}

$s3Client = new S3Client([
    'version'     => 'latest',
    'region'      => $_ENV['AWS_REGION'],
    'credentials' => [
        'key'    => $_ENV['AWS_ACCESS_KEY_ID'],
        'secret' => $_ENV['AWS_SECRET_ACCESS_KEY'],
    ],
]);
$bucket = $_ENV['AWS_BUCKET'];

// 3. Remove as fotos marcadas para exclusão
$fotos_atuais = array_filter([$ocorrencia['foto1'], $ocorrencia['foto2'], $ocorrencia['foto3']]);
$fotos_mantidas = array_diff($fotos_atuais, $remover_fotos);

if (!empty($remover_fotos)) {
    $objects_to_delete = [];
    foreach ($remover_fotos as $foto_url) {
        $key = ltrim(parse_url($foto_url, PHP_URL_PATH), '/');
        $objects_to_delete[] = ['Key' => $key];
    }
    if (!empty($objects_to_delete)) {
        $s3Client->deleteObjects([
            'Bucket' => $bucket,
            'Delete' => ['Objects' => $objects_to_delete],
        ]);
    }
}

// 4. Processa o upload de novas fotos
$novas_fotos = [];
if (isset($_FILES['fotos']) && !empty(array_filter($_FILES['fotos']['name']))) {
    $files = $_FILES['fotos'];
    $file_count = count($files['name']);
    $allowed_exts = ['jpg', 'jpeg', 'png'];
    $max_size = 2 * 1024 * 1024; // 2MB

    for ($i = 0; $i < $file_count; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;

        $file_tmp = $files['tmp_name'][$i];
        $file_size = $files['size'][$i];
        $file_ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));

        if (!in_array($file_ext, $allowed_exts) || $file_size > $max_size) {
            $_SESSION['error_msg'] = "Formato de arquivo inválido ou arquivo muito grande (máx 2MB).";
            header("location: ../../public/edit_occurrence.php?id=" . $ocorrencia_id);
            exit;
        }

        $key = 'occurrences/occurrence_' . $ocorrencia['user_id'] . '_' . uniqid() . '.' . $file_ext;

        try {
            $result = $s3Client->putObject([
                'Bucket'     => $bucket,
                'Key'        => $key,
                'SourceFile' => $file_tmp,
            ]);
            $novas_fotos[] = $result['ObjectURL'];
        } catch (AwsException $e) {
            $_SESSION['error_msg'] = "Erro ao fazer upload de uma das imagens: " . $e->getMessage();
            header("location: ../../public/edit_occurrence.php?id=" . $ocorrencia_id);
            exit;
        }
    }
}

// 5. Combina fotos mantidas e novas fotos, limitado a 3
$fotos_finais = array_merge($fotos_mantidas, $novas_fotos);
$fotos_finais = array_slice($fotos_finais, 0, 3);

// Garante que o array tenha 3 posições para o bind_param
$foto_paths = array_pad($fotos_finais, 3, null);

// 6. Atualiza o banco de dados
$sql_update = "UPDATE ocorrencias SET 
                tipo = ?, 
                descricao = ?, 
                latitude = ?, 
                longitude = ?, 
                foto1 = ?, 
                foto2 = ?, 
                foto3 = ? 
               WHERE id = ?";

if ($stmt = $conn->prepare($sql_update)) {
    $stmt->bind_param("ssddsssi", $tipo, $descricao, $latitude, $longitude, $foto_paths[0], $foto_paths[1], $foto_paths[2], $ocorrencia_id);

    if ($stmt->execute()) {
        $_SESSION['success_msg'] = "Ocorrência atualizada com sucesso!";
        header("location: ../../public/details.php?id=" . $ocorrencia_id);
    } else {
        $_SESSION['error_msg'] = "Erro ao atualizar a ocorrência no banco de dados.";
        header("location: ../../public/edit_occurrence.php?id=" . $ocorrencia_id);
    }
    $stmt->close();
}
$conn->close();
exit;
?>