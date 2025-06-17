<?php
require_once 'config/auth.php';

// Verificar se o usuário está logado
verificaLogin();

// Verificar se o usuário tem permissão para acessar o dashboard
verificaPermissao('dashboard');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Acesso</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/header.php'; ?>
        
        <div class="content">
            <?php include 'includes/sidebar.php'; ?>
            
            <main>
                <h2>Dashboard</h2>
                
                <div class="dashboard-stats">
                    <div class="stat-card">
                        <h3>Bem-vindo, <?php echo $_SESSION['usuario_nome']; ?></h3>
                        <p>Último acesso: <?php echo date('d/m/Y H:i:s', $_SESSION['ultimo_acesso']); ?></p>
                    </div>
                    
                    <div class="stat-card">
                        <h3>Seus Grupos</h3>
                        <ul>
                            <?php foreach ($_SESSION['grupos'] as $grupo): ?>
                                <li><?php echo ucfirst($grupo); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                
                <div class="dashboard-actions">
                    <h3>Acesso Rápido</h3>
                    <div class="action-buttons">
                        <!--<?php if (in_array('Admin', $_SESSION['grupos'])): ?>-->
                        <!--<?php endif; ?>-->
                        
						<a href="pages/precificacao.php" class="btn btn-primary">Precificação</a>
                        <a href="pages/tarefas.php" class="btn btn-success">Tarefas</a>
                        <a href="pages/clientes.php" class="btn btn-info">Cliente</a>
                    </div>
                </div>
            </main>
        </div>
        
        <?php include 'includes/footer.php'; ?>
    </div>
    
    <script src="assets/js/script.js"></script>
</body>
</html>
