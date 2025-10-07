<?php
session_start();

// Protege o script
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../../public/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("location: ../../public/report.php");
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
$foto_path = null;

if (empty($tipo) || empty($descricao) || empty($latitude) || empty($longitude)) {
    $_SESSION['error_msg'] = "Por favor, preencha todos os campos obrigatórios (tipo, descrição e localização).";
    header("location: ../../public/report.php");
    exit;
}

// 2. Validação e processamento do upload da foto (se houver)
if (isset($_FILES['foto']) && $_FILES['foto']['error'] == UPLOAD_ERR_OK) {
    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $file_info = $_FILES['foto'];
    $file_name = $file_info['name'];
    $file_tmp = $file_info['tmp_name'];
    $file_size = $file_info['size'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    $allowed_exts = ['jpg', 'jpeg', 'png'];
    $max_size = 5 * 1024 * 1024; // 5MB

    if (!in_array($file_ext, $allowed_exts)) {
        $_SESSION['error_msg'] = "Formato de arquivo inválido. Apenas JPG e PNG são permitidos.";
        header("location: ../../public/report.php");
        exit;
    }

    if ($file_size > $max_size) {
        $_SESSION['error_msg'] = "O arquivo é muito grande. O tamanho máximo é de 5MB.";
        header("location: ../../public/report.php");
        exit;
    }

    // Gera um nome de arquivo único para evitar conflitos
    $new_file_name = uniqid('img_', true) . '.' . $file_ext;
    $foto_path = $upload_dir . $new_file_name;

    if (!move_uploaded_file($file_tmp, $foto_path)) {
        $_SESSION['error_msg'] = "Ocorreu um erro ao salvar a imagem.";
        header("location: ../../public/report.php");
        exit;
    }
}

// 3. Inserção no banco de dados
$sql = "INSERT INTO ocorrencias (user_id, tipo, descricao, latitude, longitude, foto, status) VALUES (?, ?, ?, ?, ?, ?, 'pendente')";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("issdds", $user_id, $tipo, $descricao, $latitude, $longitude, $foto_path);

    if ($stmt->execute()) {
        // Sucesso! Redireciona para o dashboard (index.php por enquanto) com mensagem.
        $_SESSION['success_msg'] = "Ocorrência reportada com sucesso!";
        header("location: ../../public/dashboard.php");
        exit();
    } else {
        $_SESSION['error_msg'] = "Erro ao salvar a ocorrência no banco de dados.";
        header("location: ../../public/report.php");
        exit;
    }
    $stmt->close();
} else {
    $_SESSION['error_msg'] = "Erro ao preparar a consulta ao banco de dados.";
    header("location: ../../public/report.php");
    exit;
}

$conn->close();
?>