<?php
session_start();
require_once 'includes/functions.php';
require_once 'config/database.php';

// Verificar se já está logado
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Mensagens de erro ou sucesso
$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Título da página
$pageTitle = 'Login';
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm" style="background-color: #fff6eb; border-color: #ddbea9;">
                <div class="card-body p-4">
                    <h2 class="text-center mb-4" style="color: #6b705c;">Acesso ao Sistema</h2>

                    <?php if (!empty($message)): ?>
                        <div class="alert <?php echo strpos($message, 'sucesso') !== false ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form action="auth/login.php" method="post" id="loginForm">
                        <div class="mb-3">
                            <label for="email" class="form-label" style="color: #6b705c;">E-mail</label>
                            <input type="email" class="form-control" id="email" name="email" required 
                                   style="border-color: #87b7a4;">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label" style="color: #6b705c;">Senha</label>
                            <input type="password" class="form-control" id="password" name="password" required 
                                   style="border-color: #87b7a4;">
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember" style="color: #6b705c;">Lembrar-me</label>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-lg" style="background-color: #87b7a4; color: #ffffff;">Entrar</button>
                        </div>
                    </form>

                    <div class="text-center mt-4">
                        <p class="mb-2" style="color: #6b705c;">Ou acesse com:</p>
                        <div class="d-flex justify-content-center gap-3 mb-3">
                            <a href="auth/gov-br-auth.php" class="btn" style="background-color: #c58c6d; color: #ffffff;">
                                <img src="assets/img/gov-br-logo.png" alt="gov.br" width="24" height="24" class="me-2">
                                gov.br
                            </a>
                            <a href="auth/google-auth.php" class="btn" style="background-color: #ddbea9; color: #ffffff;">
                                <img src="assets/img/gmail-logo.png" alt="Gmail" width="24" height="24" class="me-2">
                                Gmail
                            </a>
                        </div>
                    </div>

                    <hr style="border-color: #ddbea9;">

                    <div class="text-center mt-3">
                        <p style="color: #6b705c;">
                            <a href="auth/recover.php" style="color: #c58c6d; text-decoration: none;">Esqueceu sua senha?</a>
                        </p>
                        <p style="color: #6b705c;">
                            Não tem uma conta? 
                            <a href="auth/register.php" style="color: #c58c6d; text-decoration: none;">Registre-se</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
