<?php

// Inicializa a sessão para mensagens de feedback
session_start();

//require_once 'notification.php';
require_once '/../config/db.php';
require_once '/../config/email.php';

// Namespace do PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Inicializa variáveis
$nome        = $email = $telefone = $email_admin = $form_id = $mensagem = "";
$erros       = [];


// Verifica se o formulário foi enviado via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Email para receber as mensagens
    $email_admin = EMAIL_ADMIN;
    $form_id     = $_POST['form_id'];

    // Validação dos campos
    if (empty($_POST["nome"])) {
        $erros[] = "Nome é obrigatório";
    } else {
        $nome = limparDados($_POST["nome"]);
    }

    if (empty($_POST["email"])) {
        $erros[] = "Email é obrigatório";
    } else {
        $email = limparDados($_POST["email"]);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros[] = "Formato de email inválido";
        }
    }

    if (empty($_POST["telefone"])) {
        $erros[] = "Telefone é obrigatório";
    } else {
        $telefone = limparDados($_POST["telefone"]);
    }

    if (empty($_POST["mensagem"])) {
        $erros[] = "Mensagem é obrigatória";
    } else {
        $mensagem = limparDados($_POST["mensagem"]);
    }

    // Se houver erros, armazena os dados e redireciona
    if (!empty($erros)) {
        $_SESSION['contato_erros']  = $erros;
        $_SESSION['contato_status'] = "error";
        $_SESSION['contato_data']   = [
            'nome'     => $nome,
            'email'    => $email,
            'telefone' => $telefone,
            'mensagem' => $mensagem,
        ];

        header("Location: impressao-3d.php#contato");
        exit;
    }

    // Continua com o processamento se não houver erros
    require 'PHPMailer/src/Exception.php';
    require 'PHPMailer/src/PHPMailer.php';
    require 'PHPMailer/src/SMTP.php';

    $mail              = new PHPMailer(true);
    $mail->SMTPDebug   = 0;
    $mail->Debugoutput = 'html';
    $mail->CharSet     = 'UTF-8';
    $mail->Encoding    = 'base64';

    try {
        // Conexão com o banco de dados
        $host     = DB_HOST;
        $dbname   = DB_NAME;
        $username = DB_USER;
        $password = DB_PASS;

        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Insere a mensagem no banco de dados
        $data_envio = date('Y-m-d H:i:s');
        $ip         = $_SERVER['REMOTE_ADDR'];
        $status     = 'novo';

        $stmt = $pdo->prepare(
            "INSERT INTO contatos (nome, email, telefone, mensagem, data_envio, ip, status)
             VALUES (:nome, :email, :telefone, :mensagem, :data_envio, :ip, :status)"
        );

        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':telefone', $telefone);
        $stmt->bindParam(':mensagem', $mensagem);
        $stmt->bindParam(':data_envio', $data_envio);
        $stmt->bindParam(':ip', $ip);
        $stmt->bindParam(':status', $status);

        $stmt->execute();
        $id_seq_contato = $pdo->lastInsertId();

        // Configurações do servidor SMTP
        $mail->isSMTP();
        $mail->Host       = EMAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = EMAIL_USER;
        $mail->Password   = EMAIL_PASS;
        $mail->SMTPSecure = 'ssl';
        $mail->Port       = EMAIL_PORT;

        // Remetente e destinatário
        $mail->setFrom(EMAIL_USER, 'Formulário de Contato');
        $mail->addAddress($email_admin, $nome);
        $mail->addReplyTo($email, $nome);

        // Conteúdo do email
        $mail->isHTML(true);
        $mail->Subject = "Site: $nome";

        $data_formatada  = date('d/m/Y H:i', strtotime($data_envio));
        $telefone_exibir = !empty($telefone) ? $telefone : "Não informado";
        $mensagem_html   = nl2br($mensagem);

        $corpo_email = "
        <html>
        <head>
            <title>Formulário de Contato</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #6b705c; color: white; padding: 10px 20px; text-align: center; }
                .content { padding: 20px; background-color: #fff6eb; }
                .info-item { margin-bottom: 10px; }
                .label { font-weight: bold; }
                .message-box { background-color: #fff; padding: 15px; border-radius: 4px; margin-top: 20px; }
                .footer { font-size: 12px; text-align: center; margin-top: 20px; color: #777; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>$nome</h2>
                </div>
                <div class='content'>
                    <p>Você recebeu uma nova mensagem pelo formulário de contato do site:</p>
                    <div class='info-item'><span class='label'>FORMULARIO:</span> $form_id</div>
                    <div class='info-item'><span class='label'>ID:</span> #$id_seq_contato</div>
                    <div class='info-item'><span class='label'>Data/Hora:</span> $data_formatada</div>
                    <div class='info-item'><span class='label'>Nome:</span> $nome</div>
                    <div class='info-item'><span class='label'>Email:</span> $email</div>
                    <div class='info-item'><span class='label'>Telefone:</span> $telefone_exibir</div>
                    <div class='message-box'>
                        <div class='label'>Mensagem:</div>
                        <p>$mensagem_html</p>
                    </div>
                    <p>
                        <a href='https://pixelpop.com.br/admin/contatos.php?id=$id_seq_contato'
                           style='background-color:#6b705c;color:white;padding:10px 15px;text-decoration:none;border-radius:4px;display:inline-block;'>
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

        $mail->Body    = $corpo_email;
        $mail->AltBody = "Nome: $nome\nEmail: $email\nTelefone: $telefone\nMensagem: $mensagem";

        $mail->send();

        $_SESSION['contato_msg']    = "Mensagem enviada com sucesso! Em breve entraremos em contato.";
        $_SESSION['contato_status'] = "success";

        $nome = $email = $telefone = $mensagem = "";
    } catch (Exception $e) {
        $_SESSION['contato_msg']    = "Erro ao enviar mensagem: " . $mail->ErrorInfo;
        $_SESSION['contato_status'] = "error";

        if (isset($pdo) && isset($id_seq_contato)) {
            try {
                $stmt = $pdo->prepare(
                    "UPDATE contatos SET observacoes = 'Problema no envio de emails' WHERE id = :id"
                );
                $stmt->bindParam(':id', $id_seq_contato);
                $stmt->execute();
            } catch (Exception $dbEx) {
                // Erro na atualização do banco de dados
            }
        }
    }

    header("Location: impressao-3d.php#contato");
    exit;
} else {
    header("Location: impressao-3d.php");
    exit;
}

// Função para limpar dados de entrada
function limparDados($dados)
{
    $dados = trim($dados);
    $dados = stripslashes($dados);
    $dados = htmlspecialchars($dados);
    return $dados;
}
