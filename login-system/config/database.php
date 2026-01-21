<?php
// Configurações do banco de dados
$host = 'pixelpop.com.br';
$dbname = 'jaimeg36_pixelpop';
$username = 'jaimeg36_admin';
$password = '47Favoritos5$';
$charset = 'utf8';

// Configuração DSN (Data Source Name)
$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

// Opções do PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Criar conexão PDO
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // Em caso de erro na conexão
    throw new PDOException($e->getMessage(), (int)$e->getCode());
}

// Função para executar consultas SQL com segurança
function executeQuery($sql, $params = []) {
    global $pdo;

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        // Log do erro (em produção, use um sistema de log adequado)
        error_log('Database error: ' . $e->getMessage());
        return false;
    }
}
