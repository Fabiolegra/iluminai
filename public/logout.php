<?php
// 1. Inicia a sessão
// É necessário iniciar a sessão para poder acessá-la e destruí-la.
session_start();
 
// 2. Desfaz todas as variáveis de sessão
// Limpa o array $_SESSION, removendo todos os dados armazenados.
$_SESSION = array();
 
// 3. Destrói a sessão
// Remove o arquivo de sessão do servidor.
session_destroy();
 
// 4. Redireciona para a página de login
header("location: login.php");
exit;
