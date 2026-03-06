<?php

// Exibir configurações relacionadas ao email
echo "<h2>Configurações de Email do PHP</h2>";
echo "<p>SMTP: " . ini_get('SMTP') . "</p>";
echo "<p>smtp_port: " . ini_get('smtp_port') . "</p>";
echo "<p>sendmail_path: " . ini_get('sendmail_path') . "</p>";
echo "<p>mail.add_x_header: " . ini_get('mail.add_x_header') . "</p>";

// Testar o envio de email
echo "<h2>Teste de Envio</h2>";
$to      = "jgpimenta@yahoo.com.br";
$subject = "Teste de configuração mail()";
$message = "Este é um email de teste para verificar se a função mail() está funcionando.";
$headers = "From: webmaster@example.com" . "\r\n" .
           "Reply-To: webmaster@example.com" . "\r\n" .
           "X-Mailer: PHP/" . phpversion();

if (mail($to, $subject, $message, $headers)) {
    echo "<p style='color:green'>Email enviado com sucesso. Verifique sua caixa de entrada.</p>";
} else {
    echo "<p style='color:red'>Falha ao enviar email. Verifique as configurações do servidor.</p>";

    $error = error_get_last();
    if ($error) {
        echo "<p>Erro: " . $error['message'] . "</p>";
    }
}
