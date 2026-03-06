<?php
require_once 'config/auth.php';
require_once 'config/functions.php';

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
            <?php include 'includes/sidebar_m.php'; ?>
            
            <main>
                <h2>Dashboard</h2>
                
                <div class="dashboard-stats">
                    <div class="stat-card">
                        <h3>Hospedagem</h3>
                        <p>Espaço livre: <strong><?php echo calcularEspacoLivre(); ?></strong></p>
                    </div>                  
                </div>              
                
                <div class="dashboard-actions">
                    <h3>Acesso Rápido</h3>
                    <div class="action-buttons">
                        <!--<?php if (in_array('Admin', $_SESSION['grupos'])) :
                            ?>-->
                        <!--
                            <?php endif; ?>-->
                        
                        <a href="pages/tarefas_m.php" class="btn btn-success">Tarefas</a>
                        <a href="pages/precificacao.php" class="btn btn-primary">Precificação</a>
                    </div>
                </div>
            </main>
        </div>
        
        <?php include 'includes/footer.php'; ?>
    </div>
    
    <script src="assets/js/script.js"></script>
    <script src="assets/js/sidebar.js"></script>
</body>
</html>
