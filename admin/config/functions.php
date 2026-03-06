<?php

// Função para registrar logs de acesso
function registrarLog($usuario_id, $acao, $detalhes = '')
{
    global $conn;

    $ip_server = $_SERVER['REMOTE_ADDR'];

    $sql  = "INSERT INTO logs_acesso (usuario_id, ip, acao, detalhes) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $usuario_id, $ip_server, $acao, $detalhes);
    $stmt->execute();
}

// Função para sanitizar entrada de dados
function sanitizar($dado)
{
    $dado = trim($dado);
    $dado = stripslashes($dado);
    $dado = htmlspecialchars($dado);
    return $dado;
}

// Função para gerar token CSRF
function gerarTokenCSRF()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Função para validar token CSRF
function validarTokenCSRF($token)
{
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        registrarLog(
            isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : null,
            'CSRF_FALHA',
            'Possível ataque CSRF detectado'
        );
        return false;
    }
    return true;
}

// Função para verificar força da senha
function verificarForcaSenha($senha)
{
    // Pelo menos 8 caracteres
    if (strlen($senha) < 8) {
        return false;
    }

    // Verificar se contém pelo menos um número
    if (!preg_match('/[0-9]/', $senha)) {
        return false;
    }

    // Verificar se contém pelo menos uma letra maiúscula
    if (!preg_match('/[A-Z]/', $senha)) {
        return false;
    }

    // Verificar se contém pelo menos uma letra minúscula
    if (!preg_match('/[a-z]/', $senha)) {
        return false;
    }

    return true;
}

// Função para obter todas as permissões de um usuário
function obterPermissoesUsuario($usuario_id)
{
    global $conn;

    $sql = "SELECT DISTINCT p.nome FROM permissoes p
            JOIN grupo_permissao gp ON p.id = gp.permissao_id
            JOIN usuario_grupo ug ON gp.grupo_id = ug.grupo_id
            WHERE ug.usuario_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $permissoes = [];
    while ($row = $result->fetch_assoc()) {
        $permissoes[] = $row['nome'];
    }

    return $permissoes;
}

// Função para calcular o espaço disponível no servidor
// O valor retornado é diferente do que está no cPanel.
// Assim, peguei o valor padrão de 100 GB, e o tamanho usado
// no dia 24/06 que era de 0,2 GB e fiz os cálculos aproximados
// do que seria o espaço livre.
function calcularEspacoLivre()
{
    $caminho     = '/';
    $espacoTotal = (disk_total_space($caminho) / (1024 ** 3)) + 26.35;
    $espacoLivre = disk_free_space($caminho) / (1024 ** 3) + 52.36;
    $espacoUsado = $espacoTotal - ($espacoTotal - $espacoLivre);

    return round($espacoUsado, 1) . ' GB';
}

/**
 * Formata tamanho em bytes para uma unidade legível
 *
 * @param int $bytes     Tamanho em bytes
 * @param int $precision Precisão decimal
 * @return string        Tamanho formatado
 */
function formatBytes($bytes, $precision = 2)
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    $bytes = max($bytes, 0);
    $pow   = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow   = min($pow, count($units) - 1);

    $bytes /= (1 << (10 * $pow));

    return round($bytes, $precision) . ' ' . $units[$pow];
}
