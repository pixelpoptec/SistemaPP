<?php
session_start();
require_once '../includes/functions.php';
require_once '../config/database.php';

// Configurações do gov.br (OpenID Connect)
$clientId = 'SEU_CLIENT_ID_GOVBR';
$clientSecret = 'SEU_CLIENT_SECRET_GOVBR';
$redirectUri = 'https://seu-site.com/auth/gov-br-auth.php';
$authEndpoint = 'https://sso.acesso.gov.br/authorize';
$tokenEndpoint = 'https://sso.acesso.gov.br/token';
$userInfoEndpoint = 'https://sso.acesso.gov.br/userinfo';

// Iniciar fluxo de autenticação
if (!isset($_GET['code'])) {
    // Gerar state para segurança
    $state = generateToken();
    $_SESSION['oauth_state'] = $state;

    // Construir URL de autorização
    $authUrl = $authEndpoint . '?' . http_build_query([
        'response_type' => 'code',
        'client_id' => $clientId,
        'redirect_uri' => $redirectUri,
        'scope' => 'openid email profile',
        'state' => $state,
    ]);

    // Redirecionar para gov.br
    header('Location: ' . $authUrl);
    exit;
} else {
    // Processar callback
    $code = $_GET['code'];
    $state = $_GET['state'] ?? '';

    // Verificar state para prevenir CSRF
    if ($state !== $_SESSION['oauth_state']) {
        $_SESSION['message'] = 'Erro de segurança. Por favor, tente novamente.';
        header('Location: ../index.php');
        exit;
    }

    // Trocar código por token
    $tokenData = getTokenFromCode($code, $clientId, $clientSecret, $redirectUri, $tokenEndpoint);

    if (!$tokenData || isset($tokenData['error'])) {
        $_SESSION['message'] = 'Erro ao autenticar com gov.br. Por favor, tente novamente.';
        header('Location: ../index.php');
        exit;
    }

    // Obter informações do usuário
    $userInfo = getUserInfo($tokenData['access_token'], $userInfoEndpoint);

    if (!$userInfo || isset($userInfo['error'])) {
        $_SESSION['message'] = 'Erro ao obter informações do usuário. Por favor, tente novamente.';
        header('Location: ../index.php');
        exit;
    }

    // Extrair dados
    $email = $userInfo['email'] ?? '';
    $name = $userInfo['name'] ?? '';
    $govbr_id = $userInfo['sub'] ?? ''; // ID único do usuário no gov.br

    if (empty($email)) {
        $_SESSION['message'] = 'Não foi possível obter seu e-mail. Verifique suas permissões no gov.br.';
        header('Location: ../index.php');
        exit;
    }

    // Verificar se o usuário já existe
    $sql = "SELECT id, name, email, status FROM gov_users WHERE email = ? OR govbr_id = ?";
    $stmt = executeQuery($sql, [$email, $govbr_id]);

    if ($stmt && $user = $stmt->fetch()) {
        // Usuário existe, atualizar gov.br ID se necessário
        if (empty($user['govbr_id']) && !empty($govbr_id)) {
            $sql = "UPDATE gov_users SET govbr_id = ? WHERE id = ?";
            executeQuery($sql, [$govbr_id, $user['id']]);
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
                VALUES (?, NOW(), ?, ?, 'govbr')";
        executeQuery($sql, [$user['id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);

        // Redirecionar para o dashboard
        header('Location: ../dashboard.php');
        exit;
    } else {
        // Usuário não existe, criar nova conta
        $password = password_hash(generateToken(12), PASSWORD_DEFAULT); // Senha aleatória

        $sql = "INSERT INTO gov_users (name, email, password, govbr_id, status, created_at) 
                VALUES (?, ?, ?, ?, 'active', NOW())";
        $stmt = executeQuery($sql, [$name, $email, $password, $govbr_id]);

        if ($stmt) {
            $user_id = $pdo->lastInsertId();

            // Login bem-sucedido
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;

            // Registrar login
            $sql = "INSERT INTO gov_login_history (user_id, login_time, ip_address, user_agent, login_method) 
                    VALUES (?, NOW(), ?, ?, 'govbr')";
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
}

/**
 * Obter token de acesso usando código de autorização
 */
function getTokenFromCode($code, $clientId, $clientSecret, $redirectUri, $tokenEndpoint) {
    $ch = curl_init($tokenEndpoint);

    $params = [
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => $redirectUri,
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
    ];

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

/**
 * Obter informações do usuário usando token de acesso
 */
function getUserInfo($accessToken, $userInfoEndpoint) {
    $ch = curl_init($userInfoEndpoint);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}
