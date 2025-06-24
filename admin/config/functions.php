<?php
// Função para registrar logs de acesso
function registrarLog($usuario_id, $acao, $detalhes = '') {
    global $conn;
    
    $ip = $_SERVER['REMOTE_ADDR'];
    
    $sql = "INSERT INTO logs_acesso (usuario_id, ip, acao, detalhes) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $usuario_id, $ip, $acao, $detalhes);
    $stmt->execute();
}

// Função para sanitizar entrada de dados
function sanitizar($dado) {
    $dado = trim($dado);
    $dado = stripslashes($dado);
    $dado = htmlspecialchars($dado);
    return $dado;
}

// Função para gerar token CSRF
function gerarTokenCSRF() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Função para validar token CSRF
function validarTokenCSRF($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        registrarLog(isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : null, 
                    'CSRF_FALHA', 'Possível ataque CSRF detectado');
        return false;
    }
    return true;
}

// Função para verificar força da senha
function verificarForcaSenha($senha) {
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
function obterPermissoesUsuario($usuario_id) {
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

//Função para calcular o espaço disponivel no servidor
//O valor retornado é diferente do que esta no cpanel
//Assim, peguei o valor padrão de 100GB, e o tamanho usado
//No dia 24/06 que era de 0,2GB e fiz os calculos aproximados
//Do que seria o espaço livre
function calcularEspacoLivre() {
    $caminho = '/'; //Diretório raiz do servidor
	// Verifica o espaço total no diretório fornecido
    $espacoTotal = (disk_total_space($caminho) / (1024 ** 3)) + 26.35;
	//$espacoTotal = 107374182400 / (1024 ** 3);
    // Verifica o espaço livre no diretório fornecido
    $espacoLivre = disk_free_space($caminho) / (1024 ** 3) + 52.36;
    // Calcula o espaço usado
	$espacoUsado = $espacoTotal - ($espacoTotal - $espacoLivre);

    // Converte o espaço usado de bytes para uma unidade legível
/*     $unidades = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 2;

    while ($espacoUsado >= 1024 && $i < count($unidades) - 1) {
        $espacoUsado /= 1024;
        $i++;
    } */

    //return round($espacoUsado, 2) . ' ' . $unidades[$i];
	return round($espacoUsado, 1) . ' GB';
}