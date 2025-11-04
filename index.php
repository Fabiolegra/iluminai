<?php
// Carrega o bootstrap da aplicação (autoloader, .env, sessão)
require_once __DIR__ . '/bootstrap.php';

// Se o usuário já estiver logado, redireciona para o dashboard principal
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: public/index.php");
    exit;
}

// Inclui a parte visual (HTML) da página.
require_once __DIR__ . '/landing.php';
?>