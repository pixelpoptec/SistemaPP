<?php
require_once '/../config/email.php';
require_once '/../config/db.php';

// Namespace do PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Inicializa variáveis
$nome = $email = $telefone = $mensagem = "";
$erros = [];
$enviado = false;

// Verifica se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
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
        // Verifica se o email é válido
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros[] = "Formato de email inválido";
        }
    }

    if (empty($_POST["telefone"])) {
        $erros[] = "telefone é obrigatório";
    } else {
        $telefone = limparDados($_POST["telefone"]);
    }

    if (empty($_POST["mensagem"])) {
        $erros[] = "Mensagem é obrigatória";
    } else {
        $mensagem = limparDados($_POST["mensagem"]);
    }

    // Se não houver erros, envia o email usando PHPMailer
    if (empty($erros)) {
        // Carrega o PHPMailer
        require 'PHPMailer/src/Exception.php';
        require 'PHPMailer/src/PHPMailer.php';
        require 'PHPMailer/src/SMTP.php';

        // Cria uma nova instância do PHPMailer
        $mail = new PHPMailer(true);
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = 'html';

        try {
            // Configurações do servidor
            $mail->isSMTP();                                      // Usar SMTP
            $mail->Host       = EMAIL_HOST;           // Servidor SMTP
            $mail->SMTPAuth   = true;                             // Habilitar autenticação SMTP
            $mail->Username   = EMAIL_USER;       // SMTP username
            $mail->Password   = EMAIL_PASS;                      // SMTP password
            $mail->SMTPSecure = 'ssl';   // Habilitar criptografia TLS
            $mail->Port       = EMAIL_PORT;                              // Porta TCP para conexão

            // Remetente e destinatário
            $mail->setFrom(EMAIL_USER, 'Formulário de Contato');
            $mail->addAddress($email, $nome);
            $mail->addAddress(EMAIL_USER, $nome);           // Adicionar destinatário
            $mail->addReplyTo($email, $nome);                           // Endereço para resposta

            // Conteúdo do email
            $mail->isHTML(true);                                  // Formato do email como HTML
            $mail->Subject = "Contato do site: $telefone";

            // Corpo do email
            $corpoEmail = "
            <h2>Nova mensagem do formulário de contato</h2>
            <p><strong>Nome:</strong> $nome</p>
            <p><strong>Email:</strong> $email</p>
            <p><strong>telefone:</strong> $telefone</p>
            <p><strong>Mensagem:</strong></p>
            <p>" . nl2br($mensagem) . "</p>
            ";

            $mail->Body    = $corpoEmail;
            $mail->AltBody = "Nome: $nome\nEmail: $email\ntelefone: $telefone\nMensagem: $mensagem"; // Para clientes que não suportam HTML

            $mail->send();
            $enviado = true;

            // Limpa os campos após envio bem-sucedido
            $nome = $email = $telefone = $mensagem = "";
        } catch (Exception $e) {
            $erros[] = "Erro ao enviar mensagem: " . $mail->ErrorInfo;
        }
    }
}

// Função para limpar dados de entrada
function limparDados($dados)
{
    $dados = trim($dados);
    $dados = stripslashes($dados);
    $dados = htmlspecialchars($dados);
    return $dados;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulário de Contato</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        
        h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        input, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }
        
        textarea {
            height: 150px;
            resize: vertical;
        }
        
        button {
            background-color: #4CAF50;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        
        button:hover {
            background-color: #45a049;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <h1>Entre em Contato</h1>
    
    <?php if ($enviado) : ?>
        <div class="alert alert-success">
            <p>Sua mensagem foi enviada com sucesso! Entraremos em contato em breve.</p>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($erros)) : ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($erros as $erro) : ?>
                    <li><?php echo $erro; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <div class="form-group">
            <label for="nome">Nome</label>
            <input type="text" id="nome" name="nome" value="<?php echo $nome; ?>" placeholder="Seu nome completo">
        </div>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo $email; ?>" placeholder="seu.email@exemplo.com">
        </div>
        
        <div class="form-group">
            <label for="telefone">Telefone</label>
            <input type="text" id="telefone" name="telefone" value="<?php echo $telefone; ?>" placeholder="(11)99999-9999">
        </div>
        
        <div class="form-group">
            <label for="mensagem">Mensagem</label>
            <textarea id="mensagem" name="mensagem" placeholder="Digite sua mensagem aqui..."><?php echo $mensagem; ?></textarea>
        </div>
        
        <button type="submit">Enviar Mensagem</button>
    </form>
</body>
</html>
