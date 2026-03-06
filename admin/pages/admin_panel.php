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
                <h2>Painel Administrativo</h2>
                
                <div class="admin-panel">
                    <div class="panel-section">
                        <h3>Estatísticas do Sistema</h3>
                        
                        <?php
                        // Obter estatísticas
                        $sql_usuarios = "SELECT COUNT(*) as total FROM usuarios";
                        $sql_ativos = "SELECT COUNT(*) as total FROM usuarios WHERE ativo = 1";
                        $sql_logs = "SELECT COUNT(*) as total FROM logs_acesso";

                        $result_usuarios = $conn->query($sql_usuarios);
                        $result_ativos = $conn->query($sql_ativos);
                        $result_logs = $conn->query($sql_logs);

                        $total_usuarios = $result_usuarios->fetch_assoc()['total'];
                        $total_ativos = $result_ativos->fetch_assoc()['total'];
                        $total_logs = $result_logs->fetch_assoc()['total'];
                        ?>
                        
                        <div class="stats-grid">
                            <div class="stat-item">
                                <h5>Total de Usuários</h5>
                                <span class="stat-value"><?php echo $total_usuarios; ?></span>
                            </div>
                            
                            <div class="stat-item">
                                <h5>Usuários Ativos</h5>
                                <span class="stat-value"><?php echo $total_ativos; ?></span>
                            </div>
                            
                            <div class="stat-item">
                                <h5>Logs de Acesso</h5>
                                <span class="stat-value"><?php echo $total_logs; ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!--
                    <div class="panel-section">
                        <h3>Últimos Acessos</h3>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover data-table">
                                <thead>
                                    <tr>
                                        <th>Usuário</th>
                                        <th>Ação</th>
                                        <th>Data/Hora</th>
                                        <th>IP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT l.*, u.nome as usuario_nome 
                                            FROM logs_acesso l
                                            LEFT JOIN usuarios u ON l.usuario_id = u.id
                                            ORDER BY l.data_hora DESC
                                            LIMIT 10";
                                    $result = $conn->query($sql);

                                    while ($log = $result->fetch_assoc()) :
                                        ?>
                                    <tr>
                                        <td data-label="Usuário:"><?php echo $log['usuario_nome'] ?? 'Anônimo'; ?></td>
                                        <td data-label="Ação:"><?php echo $log['acao']; ?></td>
                                        <td data-label="Data/Hora:"><?php echo date('d/m/Y H:i:s', strtotime($log['data_hora'])); ?></td>
                                        <td data-label="IP:"><?php echo $log['ip']; ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    
                    <div class="panel-actions">
                        <a href="../pages/gerenciar_usuarios.php" class="btn btn-primary">Gerenciar Usuários</a>
                        <a href="../pages/relatorios.php" class="btn btn-info">Ver Relatórios</a>
                    </div>
                    -->
                </div>
            </main>
        </div>
        
        <?php include '../includes/footer.php'; ?>
    </div>
    
    <script src="../assets/js/script.js"></script>
    <script src="../assets/js/sidebar.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
