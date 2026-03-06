<?php
require_once '../config/auth.php';

// Verificar se o usuário está logado
verificaLogin();

// Verificar se o usuário tem permissão para gerenciar usuários
verificaPermissao('gerenciar_usuarios');

// Inicializar variáveis
$erro = '';
$sucesso = '';
$csrf_token = gerarTokenCSRF();

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF
    if (!validarTokenCSRF($_POST['csrf_token'])) {
        $erro = 'Erro de segurança. Por favor, tente novamente.';
    } else {
        $acao = $_POST['acao'] ?? '';

        // Adicionar usuário
        if ($acao === 'adicionar') {
            $nome = sanitizar($_POST['nome']);
            $email = sanitizar($_POST['email']);
            $senha = $_POST['senha'];
            $grupo_id = (int)$_POST['grupo_id'];

            // Validar email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $erro = 'Email inválido';
            }
            // Verificar força da senha
            elseif (!verificarForcaSenha($senha)) {
                $erro = 'A senha deve ter pelo menos 8 caracteres, incluindo letras maiúsculas, minúsculas e números';
            } else {
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

                        // Atribuir ao grupo selecionado
                        $sql = "INSERT INTO usuario_grupo (usuario_id, grupo_id) VALUES (?, ?)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("ii", $usuario_id, $grupo_id);
                        $stmt->execute();

                        registrarLog($_SESSION['usuario_id'], 'USUARIO_ADICIONADO', "Usuário ID: $usuario_id adicionado");

                        $sucesso = 'Usuário adicionado com sucesso!';
                    } else {
                        $erro = 'Erro ao adicionar usuário: ' . $conn->error;
                    }
                }
            }
        }

        // Ativar/Desativar usuário
        elseif ($acao === 'alternar_status') {
            $usuario_id = (int)$_POST['usuario_id'];
            $novo_status = (int)$_POST['novo_status'];

            // Não permitir que um usuário desative a si mesmo
            if ($usuario_id === $_SESSION['usuario_id'] && $novo_status === 0) {
                $erro = 'Você não pode desativar sua própria conta';
            } else {
                $sql = "UPDATE usuarios SET ativo = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $novo_status, $usuario_id);

                if ($stmt->execute()) {
                    $status_texto = $novo_status ? 'ativado' : 'desativado';
                    registrarLog($_SESSION['usuario_id'], 'USUARIO_STATUS_ALTERADO', "Usuário ID: $usuario_id $status_texto");

                    $sucesso = "Usuário $status_texto com sucesso!";
                } else {
                    $erro = 'Erro ao alterar status do usuário: ' . $conn->error;
                }
            }
        }

        // Alterar grupo do usuário
        elseif ($acao === 'alterar_grupo') {
            $usuario_id = (int)$_POST['usuario_id'];
            $grupo_id = (int)$_POST['grupo_id'];

            // Remover grupos atuais
            $sql = "DELETE FROM usuario_grupo WHERE usuario_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $usuario_id);
            $stmt->execute();

            // Adicionar novo grupo
            $sql = "INSERT INTO usuario_grupo (usuario_id, grupo_id) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $usuario_id, $grupo_id);

            if ($stmt->execute()) {
                registrarLog($_SESSION['usuario_id'], 'USUARIO_GRUPO_ALTERADO', "Usuário ID: $usuario_id, Grupo ID: $grupo_id");

                $sucesso = "Grupo do usuário alterado com sucesso!";
            } else {
                $erro = 'Erro ao alterar grupo do usuário: ' . $conn->error;
            }
        }
    }
}

// Obter lista de grupos
$sql_grupos = "SELECT id, nome, descricao FROM grupos ORDER BY nome";
$grupos_result = $conn->query($sql_grupos);
$grupos = [];

while ($grupo = $grupos_result->fetch_assoc()) {
    $grupos[] = $grupo;
}

// Obter lista de usuários
$sql_usuarios = "SELECT u.id, u.nome, u.email, u.ativo, u.data_criacao, u.ultimo_acesso, g.nome as grupo_nome, g.id as grupo_id
                FROM usuarios u
                LEFT JOIN usuario_grupo ug ON u.id = ug.usuario_id
                LEFT JOIN grupos g ON ug.grupo_id = g.id
                ORDER BY u.nome";
$usuarios_result = $conn->query($sql_usuarios);
$usuarios = [];

while ($usuario = $usuarios_result->fetch_assoc()) {
    $usuarios[] = $usuario;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - Sistema de Acesso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include '../includes/header.php'; ?>
        
        <div class="content">
            <?php include '../includes/sidebar.php'; ?>
            
            <main>
                <h2>Gerenciar Usuários</h2>
                
                <?php if (!empty($erro)) : ?>
                    <div class="alert alert-danger"><?php echo $erro; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($sucesso)) : ?>
                    <div class="alert alert-success"><?php echo $sucesso; ?></div>
                <?php endif; ?>
                
                <div class="user-management">
                    <div class="panel-section">
                        <h3>Adicionar Novo Usuário</h3>
                        
                        <form method="post" action="" class="form-add-user">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="acao" value="adicionar">
                            
                            <div class="form-group">
                                <label for="nome">Nome Completo</label>
                                <input type="text" id="nome" name="nome" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="senha">Senha</label>
                                <input type="password" id="senha" name="senha" required>
                                <small class="form-text">A senha deve ter pelo menos 8 caracteres, incluindo letras maiúsculas, minúsculas e números</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="grupo_id">Grupo</label>
                                <select id="grupo_id" name="grupo_id" required>
                                    <?php foreach ($grupos as $grupo) : ?>
                                        <option value="<?php echo $grupo['id']; ?>"><?php echo $grupo['nome']; ?> - <?php echo $grupo['descricao']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Adicionar Usuário</button>
                        </form>
                    </div>
                    
                    <div class="panel-section">
                        <h3>Usuários Cadastrados</h3>
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-hover data-table">
                                <thead>
                                    <tr>
                                        <!--<th>ID</th>-->
                                        <th>Nome</th>
                                        <th>Email</th>
                                        <th>Grupo</th>
                                        <th>Status</th>
                                        <!--<th>Data de Cadastro</th>-->
                                        <!--<th>Último Acesso</th>-->
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usuarios as $usuario) : ?>
                                    <tr>
                                        <!--<td><?php echo $usuario['id']; ?></td>-->
                                        <td><?php echo $usuario['nome']; ?></td>
                                        <td><?php echo $usuario['email']; ?></td>
                                        <td><?php echo $usuario['grupo_nome']; ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $usuario['ativo'] ? 'active' : 'inactive'; ?>">
                                                <?php echo $usuario['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                            </span>
                                        </td>
                                        <!--<td><?php echo date('d/m/Y', strtotime($usuario['data_criacao'])); ?></td>-->
                                        <!--<td><?php echo $usuario['ultimo_acesso'] ? date('d/m/Y H:i', strtotime($usuario['ultimo_acesso'])) : 'Nunca'; ?></td>-->
                                        <td class="actions">
                                            <!-- Alternar Status -->
                                            <form method="post" action="" class="inline-form">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                <input type="hidden" name="acao" value="alternar_status">
                                                <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                                <input type="hidden" name="novo_status" value="<?php echo $usuario['ativo'] ? 0 : 1; ?>">
                                                
                                                <button type="submit" class="btn btn-sm btn-<?php echo $usuario['ativo'] ? 'warning' : 'success'; ?>">
                                                    <?php echo $usuario['ativo'] ? 'Desativar' : 'Ativar'; ?>
                                                </button>
                                            </form>
                                            
                                            <!-- Alterar Grupo -->
                                            <form method="post" action="" class="inline-form grupo-form">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                <input type="hidden" name="acao" value="alterar_grupo">
                                                <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                                
                                                <select name="grupo_id" class="select-grupo">
                                                    <?php foreach ($grupos as $grupo) : ?>
                                                        <option value="<?php echo $grupo['id']; ?>" <?php echo ($grupo['id'] == $usuario['grupo_id']) ? 'selected' : ''; ?>>
                                                            <?php echo $grupo['nome']; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                
                                                <button type="submit" class="btn btn-sm btn-info">Alterar</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>  
                    </div>
                </div>
            </main>
        </div>
        
        <?php include '../includes/footer.php'; ?>
    </div>
    
    <script src="../assets/js/script.js"></script>
    <script src="../assets/js/sidebar.js"></script>
</body>
</html>
