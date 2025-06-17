<?php
require_once 'notification.php';

// Inicializa a sessão para mensagens de feedback
session_start();

// Verifica se o formulário foi enviado via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Configurações
    $debug_mode = false; // Mudar para false em produção
    $email_admin = "jgpimenta@yahoo.com.br"; // Email para receber as mensagens
    
    // Função para limpar e validar dados de entrada
    function limparInput($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
    
    // Captura e limpa os dados enviados
    $nome = isset($_POST['nome']) ? limparInput($_POST['nome']) : '';
    $email = isset($_POST['email']) ? limparInput($_POST['email']) : '';
    $telefone = isset($_POST['telefone']) ? limparInput($_POST['telefone']) : '';
    $mensagem = isset($_POST['mensagem']) ? limparInput($_POST['mensagem']) : '';
    
    // Array para armazenar mensagens de erro
    $erros = [];
    
    // Validação dos campos
    if (empty($nome)) {
        $erros[] = "O campo nome é obrigatório.";
    } elseif (strlen($nome) < 3) {
        $erros[] = "O nome deve ter pelo menos 3 caracteres.";
    }
    
    if (empty($email)) {
        $erros[] = "O campo email é obrigatório.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = "Formato de email inválido.";
    }
    
    if (!empty($telefone)) {
        // Remove caracteres não numéricos para validação
        $telefone_numerico = preg_replace('/[^0-9]/', '', $telefone);
        if (strlen($telefone_numerico) < 10) {
            $erros[] = "Número de telefone inválido. Informe DDD + número.";
        }
    }
    
    if (empty($mensagem)) {
        $erros[] = "O campo mensagem é obrigatório.";
    } elseif (strlen($mensagem) < 10) {
        $erros[] = "A mensagem deve ter pelo menos 10 caracteres.";
    }
    
    // Se não houver erros, prossegue com o processamento
if (!empty($erros)) {
        // Formata a lista de erros para HTML
        $errorDetails = Notification::formatErrorList($erros);
        
        // Configura a notificação
        Notification::set(
            "Por favor, corrija os seguintes problemas no formulário:",
            "error",
            $errorDetails
        );
        
        // Armazena os dados enviados para preencher o formulário novamente
        $_SESSION['contato_data'] = [
            'nome' => $nome,
            'email' => $email,
            'telefone' => $telefone,
            'mensagem' => $mensagem
        ];
        
        // Redireciona de volta para a página do formulário
        header("Location: index.php#contato");
        exit;
    }
    
    // Continua com o processamento se não houver erros
    try {
            // Substitua essas configurações pelas suas
            $host = 'pixelpop.com.br';
            $dbname = 'jaimeg36_pixelpop';
            $username = 'jaimeg36_admin';
            $password = '47Favoritos5$';
            
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Insere a mensagem no banco de dados
            $data_envio = date('Y-m-d H:i:s');
            $ip = $_SERVER['REMOTE_ADDR'];
            $status = 'novo'; // Status inicial: novo, em_analise, respondido, fechado
            
            $stmt = $pdo->prepare("INSERT INTO contatos (nome, email, telefone, mensagem, data_envio, ip, status) 
                                  VALUES (:nome, :email, :telefone, :mensagem, :data_envio, :ip, :status)");
            
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':telefone', $telefone);
            $stmt->bindParam(':mensagem', $mensagem);
            $stmt->bindParam(':data_envio', $data_envio);
            $stmt->bindParam(':ip', $ip);
            $stmt->bindParam(':status', $status);
            
            $stmt->execute();
            $id_contato = $pdo->lastInsertId();
            
            // Prepara o envio de email de notificação para o administrador
            $para = $email_admin;
            $assunto = "Nova mensagem de contato - Pixel Pop";
            
            // Corpo do email em HTML
            $corpo_email = "
            <html>
            <head>
                <title>Nova mensagem de contato</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #003a75; color: white; padding: 10px 20px; text-align: center; }
                    .content { padding: 20px; background-color: #f5f5f5; }
                    .info-item { margin-bottom: 10px; }
                    .label { font-weight: bold; }
                    .message-box { background-color: #fff; padding: 15px; border-radius: 4px; margin-top: 20px; }
                    .footer { font-size: 12px; text-align: center; margin-top: 20px; color: #777; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Nova Mensagem de Contato</h2>
                    </div>
                    <div class='content'>
                        <p>Você recebeu uma nova mensagem pelo formulário de contato do site:</p>
                        
                        <div class='info-item'>
                            <span class='label'>ID:</span> #$id_contato
                        </div>
                        <div class='info-item'>
                            <span class='label'>Data/Hora:</span> " . date('d/m/Y H:i', strtotime($data_envio)) . "
                        </div>
                        <div class='info-item'>
                            <span class='label'>Nome:</span> $nome
                        </div>
                        <div class='info-item'>
                            <span class='label'>Email:</span> $email
                        </div>
                        <div class='info-item'>
                            <span class='label'>Telefone:</span> " . (!empty($telefone) ? $telefone : "Não informado") . "
                        </div>
                        
                        <div class='message-box'>
                            <div class='label'>Mensagem:</div>
                            <p>" . nl2br($mensagem) . "</p>
                        </div>
                        
                        <p>
                            <a href='https://pixelpop.com.br/admin/contatos.php?id=$id_contato' 
                               style='background-color:#0056b3;color:white;padding:10px 15px;text-decoration:none;border-radius:4px;display:inline-block;'>
                               Gerenciar Mensagem
                            </a>
                        </p>
                    </div>
                    <div class='footer'>
                        <p>Este é um email automático. Por favor, não responda diretamente.</p>
                        <p>IP do remetente: $ip</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            // Cabeçalhos para email HTML
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: Pixel Pop<contato@pixelpop.com.br>" . "\r\n";
            
            // Prepara o email de confirmação para o usuário
            $para_usuario = $email;
            $assunto_usuario = "Recebemos sua mensagem - Pixel Pop";
            
            $corpo_email_usuario = "
            <html>
            <head>
                <title>Confirmação de Contato</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #003a75; color: white; padding: 10px 20px; text-align: center; }
                    .content { padding: 20px; background-color: #f5f5f5; }
                    .footer { font-size: 12px; text-align: center; margin-top: 20px; color: #777; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Pixel Pop</h2>
                    </div>
                    <div class='content'>
                        <h3>Olá, $nome!</h3>
                        <p>Recebemos sua mensagem e agradecemos por entrar em contato conosco.</p>
                        <p>Um de nossos atendentes analisará sua mensagem e responderá o mais breve possível.</p>
                        <p>Abaixo está uma cópia da sua mensagem:</p>
                        
                        <div style='background-color:#fff;padding:15px;border-radius:4px;margin:20px 0;'>
                            " . nl2br($mensagem) . "
                        </div>
                        
                        <p>Caso tenha alguma dúvida adicional, você pode responder a este email ou entrar em contato pelo telefone (11) 9.4108-2828.</p>
                    </div>
                    <div class='footer'>
                        <p>&copy; " . date('Y') . " Pixel Pop. Todos os direitos reservados.</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            $headers_usuario = "MIME-Version: 1.0" . "\r\n";
            $headers_usuario .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers_usuario .= "From: Pixel Pop <contato@pixelpop.com.br>" . "\r\n";
            
            // Tenta enviar os emails
            if ($debug_mode) {
                // Em modo de debug, apenas simula o envio
                $_SESSION['contato_msg'] = "Mensagem enviada com sucesso! Em modo de produção, emails seriam enviados.";
                $_SESSION['contato_status'] = "success";
            } else {
                // Envia os emails reais
                $admin_email_enviado = mail($para, $assunto, $corpo_email, $headers);
                $usuario_email_enviado = mail($para_usuario, $assunto_usuario, $corpo_email_usuario, $headers_usuario);
                
                if ($admin_email_enviado && $usuario_email_enviado) {
                    $_SESSION['contato_msg'] = "Mensagem enviada com sucesso! Em breve entraremos em contato.";
                    $_SESSION['contato_status'] = "success";
                } else {
                    // Se houver problema no envio dos emails, registra isso no banco
                    $stmt = $pdo->prepare("UPDATE contatos SET observacoes = 'Problema no envio de emails' WHERE id = :id");
                    $stmt->bindParam(':id', $id_contato);
                    $stmt->execute();
                    
                    $_SESSION['contato_msg'] = "Sua mensagem foi recebida, mas houve um problema no envio dos emails de confirmação.";
                    $_SESSION['contato_status'] = "warning";
                }
            }
        
        // Se tudo ocorrer bem, exibe notificação de sucesso
        Notification::set(
            "Sua mensagem foi enviada com sucesso! Em breve entraremos em contato.",
            "success"
        );
        
    } catch (PDOException $e) {
        // Em caso de erro no processamento
        if ($debug_mode) {
            Notification::set(
                "Ocorreu um erro ao processar sua mensagem.",
                "error",
                "Detalhes técnicos: " . $e->getMessage()
            );
        } else {
            Notification::set(
                "Ocorreu um erro ao processar sua mensagem. Por favor, tente novamente mais tarde.",
                "error"
            );
        }
    }
    
    // Redireciona de volta para a página inicial
    header("Location: impressao-3d.php#contato");
    exit;
} else {
    // Se alguém acessar diretamente esta página sem enviar o formulário
    header("Location: impressao-3d.php");
    exit;
}
?>
