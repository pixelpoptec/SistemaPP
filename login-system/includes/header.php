<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - Sistema de Login' : 'Sistema de Login'; ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo isset($isSubPage) ? '../assets/css/style.css' : 'assets/css/style.css'; ?>">
</head>
<body>
    <nav class="navbar navbar-expand-lg" style="background-color: #6b705c;">
        <div class="container">
            <a class="navbar-brand" href="<?php echo isset($isSubPage) ? '../index.php' : 'index.php'; ?>" style="color: #ffffff;">
                Sistema de Login
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation" style="border-color: #ffffff;">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo isset($isSubPage) ? '../dashboard.php' : 'dashboard.php'; ?>" style="color: #ffffff;">
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo isset($isSubPage) ? '../profile.php' : 'profile.php'; ?>" style="color: #ffffff;">
                                Perfil
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo isset($isSubPage) ? '../auth/logout.php' : 'auth/logout.php'; ?>" style="color: #ffffff;">
                                Sair
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo isset($isSubPage) ? '../index.php' : 'index.php'; ?>" style="color: #ffffff;">
                                Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo isset($isSubPage) ? '../auth/register.php' : 'auth/register.php'; ?>" style="color: #ffffff;">
                                Registrar
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
