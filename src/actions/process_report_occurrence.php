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
    header("location: ../../public/report_occurrence.php");
    exit;
}

// Inclui o arquivo de configuração do banco de dados
require_once __DIR__ . '/../../config/database.php';

// 1. Validação dos campos do formulário
$user_id = $_SESSION['user_id'];
$tipo = $_POST['tipo'];
$descricao = trim($_POST['descricao']);
$latitude = $_POST['latitude'];
$longitude = $_POST['longitude'];
$foto_paths = [null, null, null]; // Array para armazenar os caminhos das 3 fotos

if (empty($tipo) || empty($descricao) || empty($latitude) || empty($longitude)) {
    $_SESSION['error_msg'] = "Por favor, preencha todos os campos obrigatórios (tipo, descrição e localização).";
    header("location: ../../public/report_occurrence.php");
    exit;
}

// 2. Validação e processamento do upload das fotos (se houver)
if (isset($_FILES['fotos']) && !empty(array_filter($_FILES['fotos']['name']))) {
    $files = $_FILES['fotos'];
    $file_count = count($files['name']);

    if ($file_count > 3) {
        $_SESSION['error_msg'] = "Você pode enviar no máximo 3 imagens.";
        header("location: ../../public/report_occurrence.php");
        exit;
    }

    // Configura o cliente S3 uma vez
    $s3Client = new S3Client([
        'version'     => 'latest',
        'region'      => $_ENV['AWS_REGION'],
        'credentials' => [
            'key'    => $_ENV['AWS_ACCESS_KEY_ID'],
            'secret' => $_ENV['AWS_SECRET_ACCESS_KEY'],
        ],
    ]);
    $bucket = $_ENV['AWS_BUCKET'];

    $allowed_exts = ['jpg', 'jpeg', 'png'];
    $max_size = 2 * 1024 * 1024; // 2MB

    for ($i = 0; $i < $file_count; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;

        $file_tmp = $files['tmp_name'][$i];
        $file_size = $files['size'][$i];
        $file_ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));

        if (!in_array($file_ext, $allowed_exts)) {
            $_SESSION['error_msg'] = "Formato de arquivo inválido. Apenas JPG e PNG são permitidos.";
            header("location: ../../public/report_occurrence.php");
            exit;
        }

        if ($file_size > $max_size) {
            $_SESSION['error_msg'] = "Um dos arquivos é muito grande. O tamanho máximo por arquivo é de 2MB.";
            header("location: ../../public/report_occurrence.php");
            exit;
        }

        // Salva as fotos de ocorrências em uma pasta diferente
        $key = 'occurrences/occurrence_' . $user_id . '_' . uniqid() . '.' . $file_ext;

        try {
            // Faz o upload do arquivo para o S3
            $result = $s3Client->putObject([
                'Bucket'     => $bucket,
                'Key'        => $key,
                'SourceFile' => $file_tmp,
            ]);
            // Salva a URL completa do objeto no banco de dados
            if ($i < 3) {
                $foto_paths[$i] = $result['ObjectURL'];
            }
        } catch (AwsException $e) {
            $_SESSION['error_msg'] = "Erro ao fazer upload de uma das imagens: " . $e->getMessage();
            header("location: ../../public/report_occurrence.php");
            exit;
        }
    }
}

// 3. Inserção no banco de dados
$sql = "INSERT INTO ocorrencias (user_id, tipo, descricao, latitude, longitude, foto1, foto2, foto3, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pendente')";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param(
        "issddsss",
        $user_id, $tipo, $descricao, $latitude, $longitude,
        $foto_paths[0],
        $foto_paths[1],
        $foto_paths[2]
    );

    if ($stmt->execute()) {
        // Sucesso! Redireciona para o dashboard (index.php por enquanto) com mensagem.
        $ocorrencia_id = $stmt->insert_id; // Pega o ID da ocorrência recém-criada

        // 4. Adiciona o primeiro registro no histórico de status
        $sql_log = "INSERT INTO ocorrencias_log (ocorrencia_id, status_novo, alterado_por) VALUES (?, ?, ?)";
        if ($stmt_log = $conn->prepare($sql_log)) {
            $status_inicial = 'pendente';
            $stmt_log->bind_param("isi", $ocorrencia_id, $status_inicial, $user_id);
            $stmt_log->execute();
            $stmt_log->close();
        }
        // Fim da adição ao log

        $_SESSION['success_msg'] = "Ocorrência reportada com sucesso!";
        header("location: ../../public/my_occurrence.php");
        exit();
    } else {
        $_SESSION['error_msg'] = "Erro ao salvar a ocorrência no banco de dados.";
        header("location: ../../public/report_occurrence.php");
        exit;
    }
    $stmt->close();
} else {
    $_SESSION['error_msg'] = "Erro ao preparar a consulta ao banco de dados.";
    header("location: ../../public/report_occurrence.php");
    exit;
}

$conn->close();

?>