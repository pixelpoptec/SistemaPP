<?php
// Script de deploy automático via webhook GitHub
// Salve este arquivo como webhook.php no servidor

// Configurações
$secret = "47Favoritos5$"; // Uma chave secreta que você define
$repositoryPath = "E:/Sites/pixelpop/"; // Caminho completo para o diretório do seu projeto
$branch = "main"; // Branch que será atualizada

// Registrar evento no log
function logMessage($message) {
    $date = date('Y-m-d H:i:s');
    file_put_contents('deploy_log.txt', "[$date] $message" . PHP_EOL, FILE_APPEND);
}

// Verificar se o request veio do GitHub
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar assinatura (segurança)
    $headers = getallheaders();
    $hubSignature = isset($headers['X-Hub-Signature-256']) ? $headers['X-Hub-Signature-256'] : '';
    
    $payload = file_get_contents('php://input');
    $calculatedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
    
    if (!hash_equals($calculatedSignature, $hubSignature)) {
        logMessage("Erro: Assinatura inválida");
        http_response_code(403);
        exit("Acesso negado");
    }
    
    // Decodificar o payload
    $data = json_decode($payload, true);
    
    // Verificar se é um push para a branch correta
    if ($data['ref'] === "refs/heads/$branch") {
        logMessage("Recebido push para branch $branch. Iniciando deploy...");
        
        // Mudar para o diretório do repositório
        chdir($repositoryPath);
        
        // Comandos para atualizar o repositório
        $commands = [
            'git reset --hard HEAD',
            'git pull origin ' . $branch,
            // Adicione aqui outros comandos, como atualização de dependências
            // 'composer install',
            // 'npm install',
            // 'npm run build',
        ];
        
        // Executar os comandos
        $output = [];
        $success = true;
        
        foreach ($commands as $command) {
            exec($command . " 2>&1", $cmdOutput, $returnCode);
            $output[] = $command . ": " . implode("\n", $cmdOutput);
            
            if ($returnCode !== 0) {
                $success = false;
                logMessage("Erro ao executar: $command");
                break;
            }
        }
        
        // Registrar resultado
        if ($success) {
            logMessage("Deploy concluído com sucesso:\n" . implode("\n", $output));
            echo "Deploy realizado com sucesso!";
        } else {
            logMessage("Deploy falhou:\n" . implode("\n", $output));
            http_response_code(500);
            echo "Erro durante o deploy.";
        }
    } else {
        logMessage("Push para branch diferente de $branch. Nenhuma ação necessária.");
        echo "Branch não monitorada.";
    }
} else {
    logMessage("Método de requisição inválido");
    http_response_code(400);
    echo "Método de requisição inválido.";
}
?>
