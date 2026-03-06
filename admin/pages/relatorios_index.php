<?php
require_once '../config/auth.php';

// Verificar se o usuário está logado
verificaLogin();

// Verificar se o usuário tem permissão para acessar o painel administrativo
verificaPermissao('admin_panel');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - Sistema de Acesso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include '../includes/header.php'; ?>
        
        <div class="content">
            <?php include '../includes/sidebar.php'; ?>
            
            <main>
                <div class="dashboard-actions">
                    <h3>Relatórios</h3>
                    <div class="action-buttons">
                        <a href="relatorios_tarefas.php" class="btn btn-info">Tarefas</a>
                    </div>
                </div>              
                
                <!--<div class="admin-panel">
                    <div class="panel-section">
                    </div>
                    <div class="panel-section">
                    </div>
                </div>-->
            </main>
        </div>
        
        <?php include '../includes/footer.php'; ?>
    </div>
    
    <script src="../assets/js/script.js"></script>
    <script src="../assets/js/sidebar.js"></script>
</body>
</html>
