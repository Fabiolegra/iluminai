<?php
// Carrega o bootstrap da aplicação (autoloader, .env, sessão)
require_once __DIR__ . '/../../bootstrap.php';

// Garante que o script só seja executado se a requisição for POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("location: ../../public/login.php");
    exit;
}

// Inclui o arquivo de configuração do banco de dados
require_once __DIR__ . '/../../config/database.php';

// Armazena o e-mail na sessão para repopular em caso de erro
$_SESSION['input_email'] = $_POST['email'] ?? '';

$error_msg = "";
$email = trim($_POST["email"]);
$senha = $_POST["senha"];

// Valida se os campos não estão vazios
if (empty($email) || empty($senha)) {
    $error_msg = "Por favor, preencha o e-mail e a senha.";
}

// Se não houver erros de validação, continua
if (empty($error_msg)) {
    $sql = "SELECT id, email, senha, tipo FROM users WHERE email = ?";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $email);

        if ($stmt->execute()) {
            $stmt->store_result();

            if ($stmt->num_rows == 1) {
                $stmt->bind_result($id, $db_email, $hashed_password, $tipo);
                if ($stmt->fetch()) {
                    if (password_verify($senha, $hashed_password)) {
                        // Senha correta, inicia uma nova sessão
                        session_regenerate_id();
                        
                        $_SESSION["loggedin"] = true;
                        $_SESSION["user_id"] = $id;
                        $_SESSION["tipo"] = $tipo;
                        
                        unset($_SESSION['input_email']); // Limpa o e-mail da sessão
                        header("location: ../../public/index.php");
                        exit();
                    } else {
                        $error_msg = "A senha que você digitou não é válida.";
                    }
                }
            } else {
                $error_msg = "Nenhuma conta encontrada com esse e-mail.";
            }
        } else {
            $error_msg = "Oops! Algo deu errado. Tente novamente mais tarde.";
        }
        $stmt->close();
    }
}

// Se houver qualquer erro, redireciona de volta para o login
if (!empty($error_msg)) {
    $_SESSION['error_msg'] = $error_msg;
    header("location: ../../public/login.php");
    exit;
}

$conn->close();
?>