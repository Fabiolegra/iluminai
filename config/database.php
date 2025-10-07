<?php
/**
 * Configuração e Conexão com o Banco de Dados
 *
 * Este script centraliza a conexão com o banco de dados para ser reutilizada
 * em toda a aplicação.
 */

// Impede o acesso direto ao arquivo.
if (basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) {
    die('Acesso direto não permitido.');
}

// --- Configurações do Banco de Dados ---
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'iluminai');

// Cria a conexão
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Verifica a conexão
if ($conn->connect_error) {
    // Em um ambiente de produção, seria melhor logar o erro em vez de exibi-lo.
    // Por exemplo: error_log("Database connection failed: " . $conn->connect_error);
    die("ERRO: Falha na conexão com o banco de dados: " . $conn->connect_error);
}
?>
