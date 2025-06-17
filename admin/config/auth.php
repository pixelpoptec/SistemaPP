<?php
session_start();
require_once 'db.php';
require_once 'functions.php';

// Função para verificar se o usuário está logado
function verificaLogin() {
    if (!isset($_SESSION['usuario_id'])) {
        // Registrar tentativa de acesso não autorizado
        registrarLog(null, 'ACESSO_NEGADO', 'Tentativa de acesso a área restrita sem autenticação');
        
        // Redirecionar para a página de login
        header('Location: /pp-files/admin/login.php?erro=login_necessario');
        exit();
    }
    
    // Atualiza o timestamp da sessão
    $_SESSION['ultimo_acesso'] = time();
    
    return true;
}

// Função para verificar se o usuário possui uma permissão específica
function verificaPermissao($permissao) {
    // Verificar login primeiro
    verificaLogin();
    
    $usuario_id = $_SESSION['usuario_id'];
    global $conn;
    
    $sql = "SELECT p.nome FROM permissoes p
            JOIN grupo_permissao gp ON p.id = gp.permissao_id
            JOIN usuario_grupo ug ON gp.grupo_id = ug.grupo_id
            WHERE ug.usuario_id = ? AND p.nome = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $usuario_id, $permissao);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return true;
    } else {
        // Registrar tentativa de acesso não autorizado
        registrarLog($usuario_id, 'PERMISSAO_NEGADA', 
                    "Tentativa de acesso à funcionalidade restrita: $permissao");
        
        // Redirecionar para página de acesso negado
        header('Location: /pp-files/admin/index.php?erro=acesso_negado');
        exit();
    }
}

// Função para fazer login
function fazerLogin($email, $senha) {
    global $conn;
    
    // Limpar e validar entradas
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['status' => false, 'mensagem' => 'Email inválido'];
    }
    
    // Preparar consulta segura
    $sql = "SELECT id, nome, email, senha FROM usuarios WHERE email = ? AND ativo = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
	
    if ($result->num_rows === 1) {
        $usuario = $result->fetch_assoc();
		
		//$valor_09 = $senha . " - " . $usuario['senha'];
		//registrarLog($usuario['id'], 'VER SENHA', $valor_09);
		
		$senha_teste = "47Favoritos5$";
		$hash_teste = password_hash($senha_teste, PASSWORD_DEFAULT);
		
        // Verificar senha com password_verify (bcrypt)
        if (password_verify($senha, $usuario['senha'])) {
            
			// Iniciar sessão
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_email'] = $usuario['email'];
            $_SESSION['ultimo_acesso'] = time();
            
            // Buscar grupos do usuário
            $sql = "SELECT g.nome FROM grupos g
                    JOIN usuario_grupo ug ON g.id = ug.grupo_id
                    WHERE ug.usuario_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $usuario['id']);
            $stmt->execute();
            $grupos_result = $stmt->get_result();
            
            $grupos = [];
            while ($grupo = $grupos_result->fetch_assoc()) {
                $grupos[] = $grupo['nome'];
            }
            
            $_SESSION['grupos'] = $grupos;
            
            // Atualizar último acesso
            $sql = "UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $usuario['id']);
            $stmt->execute();
            
            // Registrar log de acesso
            registrarLog($usuario['id'], 'LOGIN', 'Login realizado com sucesso');
            
            return ['status' => true, 'usuario' => $usuario];
        }
    }
    
    // Se chegou aqui, login falhou
    registrarLog(null, 'LOGIN_FALHA', "Tentativa de login com email: $email");
    
    return ['status' => false, 'mensagem' => 'Email ou senha incorretos'];
}

// Função para fazer logout
function fazerLogout() {
    // Registrar logout se o usuário estiver logado
    if (isset($_SESSION['usuario_id'])) {
        registrarLog($_SESSION['usuario_id'], 'LOGOUT', 'Logout realizado');
    }
    
    // Destruir todas as variáveis de sessão
    $_SESSION = array();
    
    // Destruir o cookie da sessão
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destruir a sessão
    session_destroy();
}
