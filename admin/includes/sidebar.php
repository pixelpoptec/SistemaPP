<aside class="sidebar">
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
