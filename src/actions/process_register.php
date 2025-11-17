<?php
// Carrega o bootstrap da aplicação (autoloader, .env, sessão)
require_once __DIR__ . '/../../bootstrap.php';

// Garante que o script só seja executado se a requisição for POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("location: ../../public/register.php");
    exit;
}

// Inclui a função de envio de e-mail
require_once __DIR__ . '/send_email.php';

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
$sql = "INSERT INTO users (nome, email, senha, tipo, status, confirmation_token, token_expires_at) VALUES (?, ?, ?, ?, 'pending', ?, ?)";
if ($stmt = $conn->prepare($sql)) {
    // Criptografa a senha
    $hashed_password = password_hash($senha, PASSWORD_DEFAULT);
    $tipo = 'usuario';
    $token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $stmt->bind_param("ssssss", $nome, $email, $hashed_password, $tipo, $token, $expires_at);

    if ($stmt->execute()) {
        // Envia o e-mail de confirmação
        $base_url = rtrim($_ENV['APP_URL'], '/'); // Garante que não haja barras duplicadas
        $confirmation_link = $base_url . "/public/confirm_email.php?token=" . $token;
        $email_subject = "Confirme sua conta no IluminAI";
        $email_body = "
            <h2>Bem-vindo ao IluminAI!</h2>
            <p>Obrigado por se cadastrar. Por favor, clique no link abaixo para ativar sua conta:</p>
            <p><a href='{$confirmation_link}' style='padding: 10px 15px; background-color: #2563EB; color: white; text-decoration: none; border-radius: 5px;'>Confirmar E-mail</a></p>
            <p>Se você não consegue clicar no botão, copie e cole o seguinte link no seu navegador:</p>
            <p>" . htmlspecialchars($confirmation_link) . "</p>
            <p>Este link expira em 1 hora.</p>
        "; // A string agora usa aspas duplas para interpolação

        if (send_email($email, $email_subject, $email_body)) {
            $_SESSION['success_msg'] = "Cadastro realizado! Um e-mail de confirmação foi enviado para você. Por favor, verifique sua caixa de entrada.";
        } else {
            // Pega a mensagem de erro detalhada da sessão, se existir
            $detailed_error = $_SESSION['debug_email_error'] ?? 'Erro desconhecido no envio.';
            unset($_SESSION['debug_email_error']); // Limpa a variável de debug

            $_SESSION['error_msg'] = "Cadastro realizado, mas o e-mail de confirmação falhou. <br><strong>Detalhe técnico:</strong> " . htmlspecialchars($detailed_error);
            
            // Como o usuário foi criado, o melhor é redirecionar para o login para ver o erro.
        }

        // Limpa os dados da sessão e redireciona para o login com a mensagem
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