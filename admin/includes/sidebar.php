<button id="sidebar-toggle" class="sidebar-toggle-btn" aria-expanded="false" aria-controls="sidebar-menu" aria-label="Alternar menu lateral">
    <span class="hamburger-icon"></span>
</button>

<aside id="sidebar-menu" class="sidebar" aria-hidden="true">
   <div class="logo">
        <img src="https://pixelpop.com.br/pp-files/admin/img/horcri-marfim-circulo-trans.png" alt="Logo da Empresa" class="aside-logo">
        <span class="large-text"><b>Sistema PP v1.0</b></span>
    </div> 
    <div class="sidebar-header">
        <button id="sidebar-close" class="sidebar-close-btn" aria-label="Fechar menu lateral">
            <span class="close-icon">&times;</span>
        </button>
    </div>
    <hr>
    <nav>
        <ul>
            <li><a href="/pp-files/admin/index.php">Dashboard</a></li>
            <li><a href="/pp-files/admin/pages/estudos/cadernos.php">Estudos</a></li>
            <?php if (in_array('admin_panel', obterPermissoesUsuario($_SESSION['usuario_id']))) : ?>
                <li><a href="/pp-files/admin/pages/precificacao.php">Precificação</a></li>
                <li><a href="/pp-files/admin/pages/cotacao_moedas.php">Cotação</a></li>
            <?php endif; ?>  
            <li><a href="/pp-files/admin/pages/projetos.php">Projetos</a></li>
            <li><a href="/pp-files/admin/pages/tarefas.php">Gerenciar Tarefas</a></li>
            
            <?php if (in_array('gerenciar_usuarios', obterPermissoesUsuario($_SESSION['usuario_id']))) : ?>
                <li><a href="/pp-files/admin/pages/gerenciar_usuarios.php">Gerenciar Usuários</a></li>
            <?php endif; ?>
            
            <?php if (in_array('admin_panel', obterPermissoesUsuario($_SESSION['usuario_id']))) : ?>
                <li><a href="/pp-files/admin/pages/relatorios_index.php">Gerenciar Relatórios</a></li>
                <li><a href="/pp-files/admin/pages/clientes.php">Gerenciar Clientes</a></li>
                <li><a href="/pp-files/admin/pages/gerar_qrcode.php">Gerar QR-Code</a></li>
            <?php endif; ?>

            <?php if (in_array('admin_panel', obterPermissoesUsuario($_SESSION['usuario_id']))) : ?>
                <li><a href="/pp-files/admin/pages/admin_panel.php">Painel Administrativo</a></li>
            <?php endif; ?>            
        </ul>
    </nav>
</aside>

<div class="sidebar-backdrop" id="sidebar-backdrop" aria-hidden="true"></div>

