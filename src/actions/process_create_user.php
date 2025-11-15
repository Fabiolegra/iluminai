<?php
require_once __DIR__ . '/../../bootstrap.php';

// 1. Proteção: Apenas administradores logados podem executar esta ação
if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['tipo'] !== 'admin') {
    $_SESSION['error_msg'] = "Acesso não autorizado.";
    header("location: ../../public/index.php");
    exit;
}

require_once __DIR__ . '/../../config/database.php';

// 2. Validação dos dados do formulário
$nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$senha = $_POST['senha'] ?? '';

if (empty($nome) || empty($email) || empty($senha)) {
    $_SESSION['error_msg'] = "Todos os campos são obrigatórios.";
    header("location: ../../public/manage_users.php");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error_msg'] = "O formato do e-mail é inválido.";
    header("location: ../../public/manage_users.php");
    exit;
}

// 3. Verifica se o e-mail já está em uso
$sql_check = "SELECT id FROM users WHERE email = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("s", $email);
$stmt_check->execute();
$stmt_check->store_result();

if ($stmt_check->num_rows > 0) {
    $_SESSION['error_msg'] = "Este e-mail já está cadastrado no sistema.";
    $stmt_check->close();
    header("location: ../../public/manage_users.php");
    exit;
}
$stmt_check->close();

// 4. Insere o novo usuário no banco de dados
$sql_insert = "INSERT INTO users (nome, email, senha, tipo, status) VALUES (?, ?, ?, ?, ?)";

if ($stmt_insert = $conn->prepare($sql_insert)) {
    // Criptografa a senha
    $hashed_password = password_hash($senha, PASSWORD_DEFAULT);
    $tipo = 'operador';
    $status = 'active';

    // Associa os 5 parâmetros à consulta (nome, email, senha, tipo, status)
    $stmt_insert->bind_param("sssss", $nome, $email, $hashed_password, $tipo, $status);

    if ($stmt_insert->execute()) {
        $_SESSION['success_msg'] = "Operador '{$nome}' criado com sucesso!";
    } else {
        $_SESSION['error_msg'] = "Erro ao criar o operador. Tente novamente.";
    }
    $stmt_insert->close();
} else {
    $_SESSION['error_msg'] = "Erro interno do servidor. Não foi possível preparar a inserção.";
}

$conn->close();

header("location: ../../public/manage_users.php");
exit;
?>