<?php
/**
 * Arquivo de Bootstrap da Aplicação
 *
 * Carrega o autoloader do Composer, as variáveis de ambiente do .env
 * e inicia a sessão. Este arquivo deve ser o primeiro a ser incluído
 * em todas as páginas públicas.
 */

// Carrega o autoloader do Composer
require_once __DIR__ . '/vendor/autoload.php';

date_default_timezone_set('America/Sao_Paulo');

// Carrega as variáveis de ambiente do arquivo .env para $_ENV
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

session_start();

// Verifica se o usuário está logado e se sua conta ainda está ativa
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && isset($_SESSION["user_id"])) {
    require_once __DIR__ . '/config/database.php';
    
    $user_id = $_SESSION["user_id"];
    $stmt = $conn->prepare("SELECT status FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Se o usuário foi bloqueado, desconecta-o imediatamente
        if ($row['status'] === 'blocked') {
            session_destroy();
            $_SESSION['error_msg'] = "Sua conta foi bloqueada. Entre em contato com o administrador.";
            header("location: /iluminai/public/login.php");
            exit;
        }
    }
    $stmt->close();
}