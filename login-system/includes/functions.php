<?php
// Funções de utilidade para o sistema de login

/**
 * Sanitiza dados de entrada
 * @param string $data Dados a serem sanitizados
 * @return string Dados sanitizados
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Verifica se o e-mail é válido
 * @param string $email E-mail a ser verificado
 * @return bool Retorna true se o e-mail for válido
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Gera um token seguro
 * @param int $length Tamanho do token
 * @return string Token gerado
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Verifica a força da senha
 * @param string $password Senha a ser verificada
 * @return bool Retorna true se a senha for forte
 */
function isStrongPassword($password) {
    // Pelo menos 8 caracteres, uma letra maiúscula, uma minúscula, um número e um caractere especial
    $uppercase = preg_match('@[A-Z]@', $password);
    $lowercase = preg_match('@[a-z]@', $password);
    $number    = preg_match('@[0-9]@', $password);
    $special   = preg_match('@[^\w]@', $password);

    return strlen($password) >= 8 && $uppercase && $lowercase && $number && $special;
}

/**
 * Envia e-mail usando PHPMailer
 * @param string $to Destinatário
 * @param string $subject Assunto
 * @param string $body Corpo do e-mail
 * @return bool Retorna true se o e-mail for enviado com sucesso
 */
function sendEmail($to, $subject, $body) {
    require 'vendor/autoload.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // Configurações do servidor
        $mail->isSMTP();
        $mail->Host       = 'smtp.example.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'user@example.com';
        $mail->Password   = 'password';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Destinatários
        $mail->setFrom('from@example.com', 'Sistema de Login');
        $mail->addAddress($to);

        // Conteúdo
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Erro ao enviar e-mail: ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Registra tentativas de login para prevenir força bruta
 * @param string $email E-mail tentando login
 * @param bool $success Se a tentativa foi bem-sucedida
 * @return bool Retorna true se o usuário não estiver bloqueado
 */
function recordLoginAttempt($email, $success = false) {
    global $pdo;

    // Limpar tentativas antigas (mais de 30 minutos)
    $sql = "DELETE FROM gov_login_attempts WHERE email = ? AND attempt_time < DATE_SUB(NOW(), INTERVAL 30 MINUTE)";
    executeQuery($sql, [$email]);

    // Se foi bem-sucedido, limpar todas as tentativas
    if ($success) {
        $sql = "DELETE FROM gov_login_attempts WHERE email = ?";
        executeQuery($sql, [$email]);
        return true;
    }

    // Registrar nova tentativa
    $sql = "INSERT INTO gov_login_attempts (email, attempt_time, ip_address) VALUES (?, NOW(), ?)";
    executeQuery($sql, [$email, $_SERVER['REMOTE_ADDR']]);

    // Verificar número de tentativas
    $sql = "SELECT COUNT(*) as attempts FROM gov_login_attempts WHERE email = ?";
    $stmt = executeQuery($sql, [$email]);
    $result = $stmt->fetch();

    // Bloquear após 5 tentativas
    if ($result['attempts'] >= 5) {
        return false; // Usuário bloqueado
    }

    return true; // Usuário não bloqueado
}
