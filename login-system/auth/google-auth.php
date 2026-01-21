<?php
session_start();
require_once '../includes/functions.php';
require_once '../config/database.php';
require_once __DIR__ . '/../../../../vendor/autoload.php'; // Composer autoload

// Configuração do cliente Google
$clientID = '641297363406-ktrs10p11eh24pqoouu4cm3vttmju1ub.apps.googleusercontent.com';
$clientSecret = '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';
$redirectUri = 'https://pixelpop.com.br/pp-files/login-system/auth/google-auth.php';

// Criar cliente Google
$client = new Google_Client();
$client->setClientId($clientID);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);
$client->addScope("email");
$client->addScope("profile");

// Processar callback do Google
if (isset($_GET['code'])) {
    // Trocar código por token de acesso
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token['access_token']);

    // Obter informações do usuário
    $google_oauth = new Google_Service_Oauth2($client);
    $google_account_info = $google_oauth->userinfo->get();

    // Extrair dados
    $email = $google_account_info->email;
    $name = $google_account_info->name;
    $google_id = $google_account_info->id;

    // Verificar se o usuário já existe
    $sql = "SELECT id, name, email, status FROM gov_users WHERE email = ? OR google_id = ?";
    $stmt = executeQuery($sql, [$email, $google_id]);

    if ($stmt && $user = $stmt->fetch()) {
        // Usuário existe, atualizar Google ID se necessário
        if (empty($user['google_id'])) {
            $sql = "UPDATE gov_users SET google_id = ? WHERE id = ?";
            executeQuery($sql, [$google_id, $user['id']]);
        }

        // Verificar se a conta está ativa
        if ($user['status'] !== 'active') {
            $_SESSION['message'] = 'Sua conta não está ativa. Por favor, entre em contato com o suporte.';
            header('Location: ../index.php');
            exit;
        }

        // Login bem-sucedido
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];

        // Registrar login
        $sql = "INSERT INTO gov_login_history (user_id, login_time, ip_address, user_agent, login_method) 
                VALUES (?, NOW(), ?, ?, 'google')";
        executeQuery($sql, [$user['id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);

        // Redirecionar para o dashboard
        header('Location: ../dashboard.php');
        exit;
    } else {
        // Usuário não existe, criar nova conta
        $password = password_hash(generateToken(12), PASSWORD_DEFAULT); // Senha aleatória

        $sql = "INSERT INTO gov_users (name, email, password, google_id, status, created_at) 
                VALUES (?, ?, ?, ?, 'active', NOW())";
        $stmt = executeQuery($sql, [$name, $email, $password, $google_id]);

        if ($stmt) {
            $user_id = $pdo->lastInsertId();

            // Login bem-sucedido
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;

            // Registrar login
            $sql = "INSERT INTO gov_login_history (user_id, login_time, ip_address, user_agent, login_method) 
                    VALUES (?, NOW(), ?, ?, 'google')";
            executeQuery($sql, [$user_id, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);

            // Redirecionar para o dashboard
            header('Location: ../dashboard.php');
            exit;
        } else {
            $_SESSION['message'] = 'Erro ao criar conta. Por favor, tente novamente.';
            header('Location: ../index.php');
            exit;
        }
    }
} else {
    // Gerar URL de autenticação e redirecionar
    $authUrl = $client->createAuthUrl();
    header('Location: ' . $authUrl);
    exit;
}
