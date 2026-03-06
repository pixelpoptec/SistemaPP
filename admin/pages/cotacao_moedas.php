<?php
require_once '../config/auth.php';

// Verificar se o usuário está logado
//verificaLogin();

/**
 * Página de Cotação de Moedas
 *
 * Esta página permite a inserção das cotações de Euro, Libra e Dólar,
 * e calcula as conversões da moeda selecionada para as outras moedas.
 *
 * PHP 8.x
 */

// Inicializa a sessão se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Valores padrão para as cotações
$default_euro_rate = 6.36;
$default_dolar_rate = 5.56;
$default_libra_rate = 7.31;

// Inicializa as variáveis com os valores da sessão ou valores padrão
$euro_rate = isset($_SESSION['cotacao_euro_rate']) ? $_SESSION['cotacao_euro_rate'] : $default_euro_rate;
$libra_rate = isset($_SESSION['cotacao_libra_rate']) ? $_SESSION['cotacao_libra_rate'] : $default_libra_rate;
$dolar_rate = isset($_SESSION['cotacao_dolar_rate']) ? $_SESSION['cotacao_dolar_rate'] : $default_dolar_rate;
$valor_converter = isset($_SESSION['cotacao_valor_converter']) ? $_SESSION['cotacao_valor_converter'] : '';
$moeda_origem = isset($_SESSION['cotacao_moeda_origem']) ? $_SESSION['cotacao_moeda_origem'] : 'real';

$resultado = null;

// Processar formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validação e sanitização dos dados
    $euro_rate = isset($_POST['euro_rate']) && !empty($_POST['euro_rate']) ?
        filter_var($_POST['euro_rate'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : $default_euro_rate;

    $libra_rate = isset($_POST['libra_rate']) && !empty($_POST['libra_rate']) ?
        filter_var($_POST['libra_rate'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : $default_libra_rate;

    $dolar_rate = isset($_POST['dolar_rate']) && !empty($_POST['dolar_rate']) ?
        filter_var($_POST['dolar_rate'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : $default_dolar_rate;

    $valor_converter = isset($_POST['valor_converter']) && !empty($_POST['valor_converter']) ?
        filter_var($_POST['valor_converter'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : 0;

    $moeda_origem = isset($_POST['moeda_origem']) ?
        htmlspecialchars($_POST['moeda_origem']) : 'real';

    // Salvar os valores na sessão
    $_SESSION['cotacao_euro_rate'] = $euro_rate;
    $_SESSION['cotacao_libra_rate'] = $libra_rate;
    $_SESSION['cotacao_dolar_rate'] = $dolar_rate;
    $_SESSION['cotacao_valor_converter'] = $valor_converter;
    $_SESSION['cotacao_moeda_origem'] = $moeda_origem;

    // Verificar se todos os campos necessários foram preenchidos
    if ($euro_rate > 0 && $libra_rate > 0 && $dolar_rate > 0 && $valor_converter > 0) {
        // Calcular conversões
        $resultado = calcularCotacoes($euro_rate, $libra_rate, $dolar_rate, $valor_converter, $moeda_origem);

        // Salvar o resultado na sessão para possível uso posterior
        $_SESSION['cotacao_resultado'] = $resultado;
    }
} elseif (
    isset($_SESSION['cotacao_resultado']) &&
           isset($_SESSION['cotacao_valor_converter']) &&
           $_SESSION['cotacao_valor_converter'] > 0
) {
    // Recuperar resultado da sessão caso exista e a página seja recarregada
    $resultado = $_SESSION['cotacao_resultado'];
}

/**
 * Realiza os cálculos de conversão da moeda selecionada para as outras moedas
 *
 * @param float $euro_rate Taxa de conversão do Euro para Real
 * @param float $libra_rate Taxa de conversão da Libra para Real
 * @param float $dolar_rate Taxa de conversão do Dólar para Real
 * @param float $valor_converter Valor base para as conversões
 * @param string $moeda_origem Moeda de origem para conversão
 * @return array Array contendo as conversões calculadas
 */
function calcularCotacoes($euro_rate, $libra_rate, $dolar_rate, $valor_converter, $moeda_origem)
{
    $conversoes = [];
    $valor_moeda_origem = $valor_converter;

    // Converter o valor para real primeiro (base comum)
    $valor_em_real = $valor_converter;

    switch ($moeda_origem) {
        case 'euro':
            $valor_em_real = $valor_converter * $euro_rate;
            break;
        case 'libra':
            $valor_em_real = $valor_converter * $libra_rate;
            break;
        case 'dolar':
            $valor_em_real = $valor_converter * $dolar_rate;
            break;
        case 'real':
        default:
            // Já está em real, não precisa converter
            $valor_em_real = $valor_converter;
            break;
    }

    // Agora converter de real para cada moeda
    if ($moeda_origem !== 'real') {
        $conversoes['para_real'] = $valor_em_real;
    }

    if ($moeda_origem !== 'euro') {
        $conversoes['para_euro'] = $valor_em_real / $euro_rate;
    }

    if ($moeda_origem !== 'libra') {
        $conversoes['para_libra'] = $valor_em_real / $libra_rate;
    }

    if ($moeda_origem !== 'dolar') {
        $conversoes['para_dolar'] = $valor_em_real / $dolar_rate;
    }

    return [
        'conversoes' => $conversoes,
        'valor_origem' => $valor_moeda_origem,
        'moeda_origem' => $moeda_origem,
        'taxas' => [
            'euro_rate' => $euro_rate,
            'libra_rate' => $libra_rate,
            'dolar_rate' => $dolar_rate
        ]
    ];
}

// Função para obter o símbolo da moeda
function getSimboloMoeda($moeda)
{
    switch ($moeda) {
        case 'euro':
            return '€';
        case 'libra':
            return '£';
        case 'dolar':
            return '$';
        case 'real':
        default:
            return 'R$';
    }
}

// Função para obter o nome da moeda
function getNomeMoeda($moeda)
{
    switch ($moeda) {
        case 'euro':
            return 'Euro';
        case 'libra':
            return 'Libra';
        case 'dolar':
            return 'Dólar';
        case 'real':
        default:
            return 'Real';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotação de Moedas - Sistema de Acesso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Cores do sistema conforme especificado */
        :root {
            --primary-color: #87b7a4;
            --secondary-color: #6b705c;
            --accent-color: #c58c6d;
            --light-accent: #ddbea9;
            --light-bg: #f1e3d3;
            --very-light-bg: #fff6eb;
            --dark-text: #000000;
            --light-text: #ffffff;
        }
        
        .bg-resultado {
            background-color: var(--primary-color);
        }
        
        .cost-highlight {
            font-weight: bold;
            color: var(--secondary-color);
        }
        
        .price-highlight {
            font-weight: bold;
            color: var(--accent-color);
        }
        
        .conversion-highlight {
            font-weight: bold;
            color: var(--primary-color);
            vertical-align: middle;
            text-align: center;
        }
        
        .panel-section {
            background-color: var(--very-light-bg);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .form-label {
            font-weight: 600;
            color: var(--secondary-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include '../includes/header.php'; ?>
        
        <div class="content">
            <?php if (isMobile()) : ?>
                <?php include '../includes/sidebar_m.php'; ?>
            <?php else : ?>
                <?php include '../includes/sidebar.php'; ?>    
            <?php endif; ?>
            
            <main>
                <h2>Cotação de Moedas</h2>
                
                <div class="panel-section">
                    <h3>Calcular Conversões</h3>
                    
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="euro_rate" class="form-label">Cotação do Euro (R$)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">R$</span>
                                            <input type="number" step="0.01" class="form-control" id="euro_rate" name="euro_rate" min="0.01" required value="<?php echo htmlspecialchars($euro_rate); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="libra_rate" class="form-label">Cotação da Libra (R$)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">R$</span>
                                            <input type="number" step="0.01" class="form-control" id="libra_rate" name="libra_rate" min="0.01" required value="<?php echo htmlspecialchars($libra_rate); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="dolar_rate" class="form-label">Cotação do Dólar (R$)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">R$</span>
                                            <input type="number" step="0.01" class="form-control" id="dolar_rate" name="dolar_rate" min="0.01" required value="<?php echo htmlspecialchars($dolar_rate); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="valor_converter" class="form-label">Valor a Converter</label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" class="form-control" id="valor_converter" name="valor_converter" min="0.01" required value="<?php echo htmlspecialchars($valor_converter); ?>">
                                            <select class="form-select" id="moeda_origem" name="moeda_origem" style="max-width: 120px;">
                                                <option value="real" <?php echo ($moeda_origem === 'real') ? 'selected' : ''; ?>>Real (R$)</option>
                                                <option value="euro" <?php echo ($moeda_origem === 'euro') ? 'selected' : ''; ?>>Euro (€)</option>
                                                <option value="libra" <?php echo ($moeda_origem === 'libra') ? 'selected' : ''; ?>>Libra (£)</option>
                                                <option value="dolar" <?php echo ($moeda_origem === 'dolar') ? 'selected' : ''; ?>>Dólar ($)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">Calcular Conversões</button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <?php if ($resultado) : ?>
                <div class="panel-section">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <h4>Conversões de <?php echo getNomeMoeda($resultado['moeda_origem']); ?> para outras moedas</h4>
                                <ul class="list-group">
                                    <?php foreach ($resultado['conversoes'] as $moeda => $valor) : ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                             <?php
                                                $destino = str_replace('para_', '', $moeda);

                                                ?>
                                            <span class="conversion-highlight">
                                                <?php echo getSimboloMoeda($destino) . ' ' . number_format($valor, 2, ',', '.'); ?>
                                            </span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
        
        <?php include '../includes/footer.php'; ?>
    </div>
    
    <script src="../assets/js/sidebar.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
