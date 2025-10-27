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

// Carrega as variáveis de ambiente do arquivo .env para $_ENV
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

session_start();