<?php
require_once 'config/auth.php';

// Verificar se o usuário já está logado
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit();
}

$erro = '';
$sucesso = '';
$csrf_token = gerarTokenCSRF();

// Processar formulário de registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF
    if (!validarTokenCSRF($_POST['csrf_token'])) {
        $erro = 'Erro de segurança. Por favor, tente novamente.';
    } else {
        $nome = sanitizar($_POST['nome']);
        $email = sanitizar($_POST['email']);
        $senha = $_POST['senha'];
        $confirma_senha = $_POST['confirma_senha'];

        // Validar email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = 'Email inválido';
        }
        // Verificar se as senhas conferem
        elseif ($senha !== $confirma_senha) {
            $erro = 'As senhas não conferem';
        }
        // Verificar força da senha
        elseif (!verificarForcaSenha($senha)) {
            $erro = 'A senha deve ter pelo menos 8 caracteres, incluindo letras maiúsculas, minúsculas e números';
        } else {
            global $conn;

            // Verificar se o email já existe
            $sql = "SELECT id FROM usuarios WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $erro = 'Este email já está cadastrado';
            } else {
                // Hash da senha
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

                // Inserir novo usuário
                $sql = "INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sss", $nome, $email, $senha_hash);

                if ($stmt->execute()) {
                    $usuario_id = $stmt->insert_id;

                    // Atribuir ao grupo 'usuario' por padrão
                    $sql = "INSERT INTO usuario_grupo (usuario_id, grupo_id) 
                            SELECT ?, id FROM grupos WHERE nome = 'usuario'";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $usuario_id);
                    $stmt->execute();

                    registrarLog($usuario_id, 'REGISTRO', 'Novo usuário registrado');

                    $sucesso = 'Registro realizado com sucesso! Agora você pode fazer login.';
                } else {
                    $erro = 'Erro ao registrar usuário: ' . $conn->error;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Sistema de Acesso</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="register-container">
        <h2>Criar Conta</h2>
        
        <?php if (!empty($erro)) : ?>
            <div class="alert alert-danger"><?php echo $erro; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($sucesso)) : ?>
            <div class="alert alert-success"><?php echo $sucesso; ?></div>
            <p><a href="login.php" class="btn btn-primary">Ir para Login</a></p>
        <?php else : ?>
        <form method="post" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="form-group">
                <label for="nome">Nome Completo:</label>
                <input type="text" id="nome" name="nome" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="senha">Senha:</label>
                <input type="password" id="senha" name="senha" required>
                <small class="form-text">A senha deve ter pelo menos 8 caracteres, incluindo letras maiúsculas, minúsculas e números</small>
            </div>
            
            <div class="form-group">
                <label for="confirma_senha">Confirme a Senha:</label>
                <input type="password" id="confirma_senha" name="confirma_senha" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Registrar</button>
        </form>
        
        <p>Já tem uma conta? <a href="login.php">Faça login</a></p>
        <?php endif; ?>
    </div>
    
    <script src="assets/js/script.js"></script>
</body>
</html>
