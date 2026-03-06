<?php
require_once 'config/auth.php';

// Verificar se o usuário já está logado
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit();
}

$erro = '';
$csrf_token = gerarTokenCSRF();

// Processar formulário de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF
    if (!validarTokenCSRF($_POST['csrf_token'])) {
        $erro = 'Erro de segurança. Por favor, tente novamente.';
    } else {
        $email = sanitizar($_POST['email']);
        $senha = $_POST['senha'];
        
        $resultado = fazerLogin($email, $senha);
        
        if ($resultado['status']) {
			if (isMobile()) {
				header('Location: index_m.php');
				exit;
			} else {
				header('Location: index.php');
			}				
            
            exit();
        } else {
            $erro = $resultado['mensagem'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Acesso</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <h2 class="gradiente">Login - v1.40</h2>
        
        <?php if (!empty($erro)): ?>
            <div class="alert alert-danger"><?php echo $erro; ?></div>
        <?php endif; ?>
        
        <?php if (isset($_GET['erro']) && $_GET['erro'] === 'login_necessario'): ?>
            <div class="alert alert-warning">Você precisa fazer login para acessar esta página.</div>
        <?php endif; ?>
        
        <?php if (isset($_GET['logout']) && $_GET['logout'] === 'success'): ?>
            <div class="alert alert-success">Logout realizado com sucesso!</div>
        <?php endif; ?>
        
        <form method="post" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="senha">Senha:</label>
                <input type="password" id="senha" name="senha" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Entrar</button>
        </form>

        <!--<p>Não tem uma conta? <a href="register.php">Registre-se</a></p>-->
    </div>
    
	<img src="https://pixelpop.com.br/pp-files/admin/img/horcri-olivia-circulo-trans.png" alt="Logo da Empresa" class="header-principal">
	
    <script src="assets/js/script.js"></script>
</body>
</html>
