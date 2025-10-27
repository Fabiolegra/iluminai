<?php
// 1. Inicia a sessão
// Carrega o bootstrap da aplicação, que também inicia a sessão.
require_once __DIR__ . '/../bootstrap.php';
 
// 2. Desfaz todas as variáveis de sessão
// Limpa o array $_SESSION, removendo todos os dados armazenados.
$_SESSION = array();
 
// 3. Destrói a sessão
// Remove o arquivo de sessão do servidor.
session_destroy();
 
// 4. Redireciona para a página de login
header("location: login.php");
exit;
