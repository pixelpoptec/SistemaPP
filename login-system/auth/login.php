<?php
session_start();
require_once '../includes/functions.php';
require_once '../config/database.php';

// Verificar se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitizar e validar entrada
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password']; // Não sanitize senhas antes de verificá-las
    $remember = isset($_POST['remember']) ? true : false;

    // Validar e-mail
    if (!isValidEmail($email)) {
        $_SESSION['message'] = 'E-mail inválido. Por favor, tente novamente.';
        header('Location: ../index.php');
        exit;
    }

    // Verificar bloqueio por tentativas
    if (!recordLoginAttempt($email)) {
        $_SESSION['message'] = 'Muitas tentativas de login. Tente novamente após 30 minutos.';
        header('Location: ../index.php');
        exit;
    }

    // Buscar usuário no banco de dados
    $sql = "SELECT id, name, email, password, status FROM gov_users WHERE email = ?";
    $stmt = executeQuery($sql, [$email]);

    if ($stmt && $user = $stmt->fetch()) {
        // Verificar se a conta está ativa
        if ($user['status'] !== 'active') {
            $_SESSION['message'] = 'Sua conta não está ativa. Por favor, verifique seu e-mail ou entre em contato com o suporte.';
            header('Location: ../index.php');
            exit;
        }

        // Verificar senha
        if (password_verify($password, $user['password'])) {
            // Login bem-sucedido
            recordLoginAttempt($email, true); // Limpar tentativas

            // Criar sessão
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];

            // Criar cookie para "lembrar-me" se solicitado
            if ($remember) {
                $token = generateToken();
                $expiry = time() + (30 * 24 * 60 * 60); // 30 dias

                // Salvar token no banco de dados
                $sql = "INSERT INTO gov_remember_tokens (user_id, token, expiry) VALUES (?, ?, ?)";
                executeQuery($sql, [$user['id'], $token, date('Y-m-d H:i:s', $expiry)]);

                // Definir cookie
                setcookie('remember_token', $token, $expiry, '/', '', true, true);
            }

            // Registrar login
            $sql = "INSERT INTO gov_login_history (user_id, login_time, ip_address, user_agent) VALUES (?, NOW(), ?, ?)";
            executeQuery($sql, [$user['id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);

            // Redirecionar para o dashboard
            header('Location: ../dashboard.php');
            exit;
        } else {
            // Senha incorreta
            $_SESSION['message'] = 'E-mail ou senha incorretos. Por favor, tente novamente.';
            header('Location: ../index.php');
            exit;
        }
    } else {
        // Usuário não encontrado
        $_SESSION['message'] = 'E-mail ou senha incorretos. Por favor, tente novamente.';
        header('Location: ../index.php');
        exit;
    }
} else {
    // Acesso direto ao script sem POST
    header('Location: ../index.php');
    exit;
}
