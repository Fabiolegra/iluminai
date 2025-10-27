<?php
// Carrega o bootstrap da aplicação (autoloader, .env, sessão)
require_once __DIR__ . '/../../bootstrap.php';

// Garante que o script só seja executado se a requisição for POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("location: ../../public/register.php");
    exit;
}

// Inclui o arquivo de configuração do banco de dados
require_once __DIR__ . '/../../config/database.php';

// Armazena os inputs do formulário na sessão para repopular em caso de erro
$_SESSION['input_nome'] = $_POST['nome'] ?? '';
$_SESSION['input_email'] = $_POST['email'] ?? '';

$error_msg = "";
$nome = trim($_POST["nome"]);
$email = trim($_POST["email"]);
$senha = $_POST["senha"];
$confirmar_senha = $_POST["confirmar_senha"];

// 1. Validação dos campos
if (empty($nome) || empty($email) || empty($senha)) {
    $error_msg = "Por favor, preencha todos os campos.";
} elseif ($senha !== $confirmar_senha) {
    $error_msg = "As senhas não coincidem.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error_msg = "O formato do e-mail é inválido.";
}

// 2. Validação do e-mail (verifica se já existe)
if (empty($error_msg)) {
    $sql = "SELECT id FROM users WHERE email = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            $stmt->store_result();
            if ($stmt->num_rows == 1) {
                $error_msg = "Este e-mail já está em uso.";
            }
        } else {
            $error_msg = "Oops! Algo deu errado na verificação. Tente novamente.";
        }
        $stmt->close();
    }
}

// 3. Se houver erros, redireciona de volta com a mensagem
if (!empty($error_msg)) {
    $_SESSION['error_msg'] = $error_msg;
    header("location: ../../public/register.php");
    exit;
}

// 4. Se não houver erros, insere no banco de dados
$sql = "INSERT INTO users (nome, email, senha, tipo) VALUES (?, ?, ?, ?)";
if ($stmt = $conn->prepare($sql)) {
    // Criptografa a senha
    $hashed_password = password_hash($senha, PASSWORD_DEFAULT);
    $tipo = 'usuario';

    $stmt->bind_param("ssss", $nome, $email, $hashed_password, $tipo);

    if ($stmt->execute()) {
        // Limpa os dados da sessão e redireciona para o login
        unset($_SESSION['input_nome']);
        unset($_SESSION['input_email']);
        header("location: ../../public/login.php");
        exit();
    } else {
        $_SESSION['error_msg'] = "Oops! Algo deu errado na inserção. Tente novamente.";
        header("location: ../../public/register.php");
        exit;
    }
    $stmt->close();
}

// Fecha a conexão
$conn->close();
?>