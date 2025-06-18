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
            
            <?php if (in_array('admin_panel', obterPermissoesUsuario($_SESSION['usuario_id']))): ?>
                <li><a href="/pp-files/admin/pages/admin_panel.php">Painel Administrativo</a></li>
            <?php endif; ?>
            
            <?php if (in_array('gerenciar_usuarios', obterPermissoesUsuario($_SESSION['usuario_id']))): ?>
                <li><a href="/pp-files/admin/pages/gerenciar_usuarios.php">Gerenciar Usuários</a></li>
            <?php endif; ?>
            
            <?php if (in_array('relatorios', obterPermissoesUsuario($_SESSION['usuario_id']))): ?>
                <li><a href="/pp-files/admin/pages/relatorios.php">Relatórios</a></li>
            <?php endif; ?>
            
            <li><a href="/pp-files/admin/pages/perfil.php">Meu Perfil</a></li>
            <li><a href="/pp-files/admin/pages/precificacao.php">Precificação</a></li>
            <li><a href="/pp-files/admin/pages/tarefas.php">Controle de Tarefas</a></li>
            <li><a href="/pp-files/admin/pages/clientes.php">Gerenciar Clientes</a></li>            
        </ul>
    </nav>
</aside>

<div class="sidebar-backdrop" id="sidebar-backdrop" aria-hidden="true"></div>

