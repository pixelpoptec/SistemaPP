<?php
session_start();
require_once '../includes/functions.php';
require_once '../config/database.php';

// Verificar se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);

    // Validar e-mail
    if (!isValidEmail($email)) {
        $_SESSION['message'] = 'E-mail inválido. Por favor, tente novamente.';
        header('Location: recover.php');
        exit;
    }

    // Verificar se o e-mail existe
    $sql = "SELECT id, name, email FROM gov_users WHERE email = ? AND status = 'active'";
    $stmt = executeQuery($sql, [$email]);

    if ($stmt && $user = $stmt->fetch()) {
        // Gerar token de recuperação
        $token = generateToken();
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Salvar token no banco de dados
        $sql = "INSERT INTO gov_password_resets (user_id, token, expiry) VALUES (?, ?, ?)";
        $stmt = executeQuery($sql, [$user['id'], $token, $expiry]);

        if ($stmt) {
            // Enviar e-mail de recuperação
            $resetUrl = "https://seu-site.com/auth/reset-password.php?token=$token";
            $subject = 'Recuperação de Senha';
            $body = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background-color: #87b7a4; color: white; padding: 10px; text-align: center; }
                        .content { padding: 20px; background-color: #fff6eb; }
                        .button { display: inline-block; background-color: #c58c6d; color: white; padding: 10px 20px; 
                                  text-decoration: none; border-radius: 4px; }
                        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #777; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>Recuperação de Senha</h2>
                        </div>
                        <div class='content'>
                            <p>Olá {$user['name']},</p>
                            <p>Recebemos uma solicitação para redefinir sua senha. Se você não fez esta solicitação, 
                               por favor ignore este e-mail.</p>
                            <p>Para redefinir sua senha, clique no botão abaixo:</p>
                            <p style='text-align: center;'>
                                <a href='$resetUrl' class='button'>Redefinir Senha</a>
                            </p>
                            <p>Este link expira em 1 hora.</p>
                            <p>Se o botão não funcionar, copie e cole o link abaixo em seu navegador:</p>
                            <p>$resetUrl</p>
                        </div>
                        <div class='footer'>
                            <p>Este é um e-mail automático. Por favor, não responda.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";

            if (sendEmail($user['email'], $subject, $body)) {
                $_SESSION['message'] = 'Enviamos um e-mail com instruções para recuperar sua senha.';
                header('Location: ../index.php');
                exit;
            } else {
                $_SESSION['message'] = 'Erro ao enviar e-mail. Por favor, tente novamente.';
                header('Location: recover.php');
                exit;
            }
        } else {
            $_SESSION['message'] = 'Erro ao processar solicitação. Por favor, tente novamente.';
            header('Location: recover.php');
            exit;
        }
    } else {
        // Não informar se o e-mail existe ou não (segurança)
        $_SESSION['message'] = 'Se o e-mail estiver cadastrado, enviaremos instruções para recuperação de senha.';
        header('Location: ../index.php');
        exit;
    }
}

// Título da página
$pageTitle = 'Recuperar Senha';
include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm" style="background-color: #fff6eb; border-color: #ddbea9;">
                <div class="card-body p-4">
                    <h2 class="text-center mb-4" style="color: #6b705c;">Recuperar Senha</h2>

                    <?php if (isset($_SESSION['message'])): ?>
                        <div class="alert <?php echo strpos($_SESSION['message'], 'Erro') !== false ? 'alert-danger' : 'alert-info'; ?> alert-dismissible fade show">
                            <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form action="recover.php" method="post">
                        <div class="mb-3">
                            <label for="email" class="form-label" style="color: #6b705c;">E-mail</label>
                            <input type="email" class="form-control" id="email" name="email" required 
                                   style="border-color: #87b7a4;">
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-lg" style="background-color: #87b7a4; color: #ffffff;">
                                Enviar Link de Recuperação
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-3">
                        <p style="color: #6b705c;">
                            <a href="../index.php" style="color: #c58c6d; text-decoration: none;">Voltar para o Login</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
