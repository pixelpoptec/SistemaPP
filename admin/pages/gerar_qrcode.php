<?php
require_once '../config/auth.php';

require_once __DIR__ . '/../../../../vendor/autoload.php';

use chillerlan\QRCode\{QRCode, QROptions};
use chillerlan\QRCode\Common\EccLevel;

verificaLogin();

// Inicializa as variáveis
$qrCodeImage = '';
$errorMessage = '';
$successMessage = '';
$pixKey = '';
$amount = '';
$csrf_token = gerarTokenCSRF();

// Verifica se há dados do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include '../config/functions_qrcode.php';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerador de QR Code PIX</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style_tarefas.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- Font Awesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #87b7a4;
            --secondary-color: #6b705c;
            --accent-color: #c58c6d;
            --light-color: #ddbea9;
            --lightest-color: #f1e3d3;
            --background-color: #fff6eb;
            --dark-color: #000000;
            --white-color: #ffffff;
        }
        
        body {
            background-color: var(--background-color);
            color: var(--secondary-color);
        }
        
        .card {
            border-color: var(--light-color);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: var(--white-color);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-secondary {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }
        
        .btn-secondary:hover {
            background-color: var(--light-color);
            border-color: var(--light-color);
        }
        
        .qrcode-container {
            background-color: var(--white-color);
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            text-align: center;
        }
        
        .qrcode-img {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include '../includes/header.php'; ?>
        
        <div class="content">
            <?php include '../includes/sidebar.php'; ?>
            
            <main>
            
                <div class="container py-5">
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="mb-0 text-center">Gerador de QR Code para Pagamentos PIX</h3>
                                </div>
                                <div class="card-body">
                                    <?php if ($errorMessage) : ?>
                                        <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
                                    <?php endif; ?>
                                    
                                    <?php if ($successMessage) : ?>
                                        <div class="alert alert-success"><?php echo $successMessage; ?></div>
                                    <?php endif; ?>
                                    
                                    <form method="post" action="" id="pixForm">
                                        <div class="mb-3">
                                            <label for="pixKey" class="form-label">Chave PIX*</label>
                                            <input type="text" class="form-control" id="pixKey" name="pixKey" 
                                                   placeholder="CPF, e-mail, celular ou chave aleatória" 
                                                   value="<?php echo htmlspecialchars($pixKey); ?>" required>
                                            <div class="form-text">Informe a chave PIX do beneficiário.</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="amount" class="form-label">Valor (R$)*</label>
                                            <input type="text" class="form-control" id="amount" name="amount" 
                                                   placeholder="0,00" 
                                                   value="<?php echo htmlspecialchars($amount); ?>" required>
                                            <div class="form-text">Use ponto ou vírgula como separador decimal (ex: 15.50 ou 15,50)</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="description" class="form-label">Descrição (opcional)</label>
                                            <input type="text" class="form-control" id="description" name="description" 
                                                   placeholder="Pagamento referente a..." 
                                                   maxlength="50">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Nome do Beneficiário (opcional)</label>
                                            <input type="text" class="form-control" id="name" name="name" 
                                                   placeholder="Nome completo do beneficiário">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="city" class="form-label">Cidade do Beneficiário (opcional)</label>
                                            <input type="text" class="form-control" id="city" name="city" 
                                                   placeholder="Cidade">
                                        </div>
                                        
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary">Gerar QR Code</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            
                            <?php if ($qrCodeImage) : ?>
                            <div class="qrcode-container mt-4">
                                <h4 class="mb-3">QR Code PIX Gerado</h4>
                                <div class="row justify-content-center">
                                    <div class="col-md-6">
                                        <img src="<?php echo $qrCodeImage; ?>" alt="QR Code PIX" class="qrcode-img mb-3">
                                        
                                        <div class="d-grid gap-2">
                                            <a href="<?php echo $qrCodeImage; ?>" download="qrcode_pix.png" class="btn btn-primary">
                                                <i class="bi bi-download"></i> Baixar QR Code
                                            </a>
                                            <button id="copyPixCode" class="btn btn-secondary" data-clipboard-text="<?php echo htmlspecialchars($pixCode); ?>">
                                                <i class="bi bi-clipboard"></i> Copiar Código PIX
                                            </button>
                                        </div>
                                        
                                        <div class="mt-3">
                                            <p class="mb-1"><strong>Chave PIX:</strong> <?php echo htmlspecialchars($pixKey); ?></p>
                                            <p class="mb-1"><strong>Valor:</strong> R$ <?php echo htmlspecialchars(number_format((float)str_replace(',', '.', $amount), 2, ',', '.')); ?></p>
                                            <?php if (!empty($_POST['description'])) : ?>
                                            <p class="mb-1"><strong>Descrição:</strong> <?php echo htmlspecialchars($_POST['description']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>          
            
            </main>
        
        </div>
        <?php include '../includes/footer.php'; ?>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Clipboard.js para funcionalidade de cópia -->
    <script src="https://cdn.jsdelivr.net/npm/clipboard@2.0.11/dist/clipboard.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Formatação de valor monetário
            const amountInput = document.getElementById('amount');
            amountInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value === '') return e.target.value = '';
                value = (parseInt(value) / 100).toFixed(2);
                e.target.value = value.replace('.', ',');
            });
            
            // Inicializa o clipboard para o botão de cópia
            new ClipboardJS('#copyPixCode').on('success', function(e) {
                alert('Código PIX copiado com sucesso!');
                e.clearSelection();
            });
        });
    </script>
</body>
</html>
