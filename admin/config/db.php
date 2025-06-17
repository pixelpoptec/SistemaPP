<?php
// Configurações de conexão com o banco de dados
define('DB_HOST', 'pixelpop.com.br');
define('DB_USER', 'jaimeg36_admin');
define('DB_PASS', '47Favoritos5$');
define('DB_NAME', 'jaimeg36_pixelpop');

// Criar conexão
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Verificar conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Configurar charset
$conn->set_charset("utf8");
