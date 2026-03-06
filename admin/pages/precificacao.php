<?php
require_once '../config/auth.php';

// Verificar se o usuário está logado
verificaLogin();

// Função para buscar configurações
function getConfiguracoes($conn)
{
    $sql = "SELECT * FROM configuracoes LIMIT 1";
    $result = $conn->query($sql);
    return $result->fetch_assoc();
}

// Função para buscar material PLA/PTEG
function getMaterial($conn)
{
    $sql = "SELECT * FROM materiais WHERE nome = 'PLA/PTEG' LIMIT 1";
    $result = $conn->query($sql);
    return $result->fetch_assoc();
}

// Função para buscar dados da impressora
function getImpressora($conn)
{
    $sql = "SELECT * FROM impressoras LIMIT 1";
    $result = $conn->query($sql);
    return $result->fetch_assoc();
}

// Função para calcular o preço
function calcularPreco($conn, $hora, $minuto, $peso_g, $qtd_pecas, $acessorios_uni, $acessorios_tot, $titulo)
{
    // Buscar dados necessários
    $config = getConfiguracoes($conn);
    $material = getMaterial($conn);
    $impressora = getImpressora($conn);

    // Cálculos baseados na planilha
    $tempo_minutos = ($hora * 60) + $minuto;
    $tempo_horas = $tempo_minutos / 60;

    // Custo do material
    $custo_material = ($peso_g / 1000) * $material['custo_kg'];

    // Custo energético
    $gasto_kw = ($impressora['potencia_w'] / 1000) * $tempo_horas;
    $custo_energia = $gasto_kw * $impressora['custo_kw_h'];

    // Custo fixo
    $total_horas_mes = $config['dias_mes'] * $config['horas_dia'];
    $custo_fixo_hora = $config['custo_fixo_mensal'] / $total_horas_mes;
    // @phpmd suppress SuperGlobals
    $custo_fx_impressora = $custo_fixo_hora * $config['perc_uso_impressora'];
    $custo_fixo_total = $custo_fx_impressora * $tempo_horas;

    // Custo de amortização da máquina
    $amortizacao = ($impressora['valor_maquina'] / $impressora['vida_util_horas']) * $tempo_horas;

    // Calcular custo total de produção
    $custo_producao = $custo_material + $custo_energia + $custo_fixo_total + $amortizacao;

    // Ajuste para falhas
    $custo_producao = ($custo_producao * (1 + $config['perc_falhas'])) + $acessorios_tot + ($acessorios_uni * $qtd_pecas);

    // Cálculos de preço e lucro
    $preco_consumidor = $custo_producao * $config['markup'];
    $preco_lojista = $custo_producao * $config['markup_lojista'];

    // Lucro padrão
    $lucro_padrao = $preco_consumidor - $custo_producao - (($tempo_horas * $config['imposto']) / $qtd_pecas);

    // Lucro líquido
    // @phpmd suppress SuperGlobals
    $lucro_liquido = $lucro_padrao - ($preco_consumidor * $config['tx_cartao']) - ($preco_consumidor * $config['custo_anuncio']);
    // @phpmd suppress SuperGlobals
    $lucro_liq_porc = $lucro_liquido / $preco_consumidor;

    // Valor total
    $valor_total = $preco_consumidor * $qtd_pecas;

    if ($titulo != "ND") {
        // Salvar no histórico apenas se inserir um Título
        //para não encher o BD de registros desnecessários
        $sql = "INSERT INTO historico_precificacao (titulo, qtd_pecas, hora, minuto, peso_g, custo_producao, preco_consumidor, preco_lojista, lucro_padrao, lucro_liquido) 
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siiidddddd", $titulo, $qtd_pecas, $hora, $minuto, $peso_g, $custo_producao, $preco_consumidor, $preco_lojista, $lucro_padrao, $lucro_liquido);
        $stmt->execute();
    }

    // Retornar resultados
    return [
        'custo_material' => $custo_material,
        'custo_energia' => $custo_energia,
        'custo_fixo' => $custo_fixo_total,
        'amortizacao' => $amortizacao,
        'custo_producao' => $custo_producao,
        'preco_consumidor' => $preco_consumidor,
        'preco_lojista' => $preco_lojista,
        'lucro_padrao' => $lucro_padrao,
        'lucro_liquido' => $lucro_liquido,
        'lucro_liq_porc' => $lucro_liq_porc,
        'valor_total' => $valor_total,
        'qtd_pecas' => $qtd_pecas,
        'acessorios_uni' => $acessorios_uni
    ];
}

// Processar formulário
$resultado = null;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $hora = isset($_POST['hora']) && !empty($_POST['hora']) ? intval($_POST['hora']) : 0;
    $minuto = isset($_POST['minuto']) && !empty($_POST['minuto']) ? intval($_POST['minuto']) : 0;
    $peso_g = floatval($_POST['peso_g']);
    $qtd_pecas = intval($_POST['qtd_pecas']);
    $acessorios_uni = isset($_POST['acessorios_uni']) && !empty($_POST['acessorios_uni']) ? floatval($_POST['acessorios_uni']) : 0;
    $acessorios_tot = isset($_POST['acessorios_tot']) && !empty($_POST['acessorios_tot']) ? floatval($_POST['acessorios_tot']) : 0;
    $titulo = isset($_POST['titulo']) && !empty($_POST['titulo']) ? (string)($_POST['titulo']) : "ND";

    $resultado = calcularPreco($conn, $hora, $minuto, $peso_g, $qtd_pecas, $acessorios_uni, $acessorios_tot, $titulo);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calculadora de Precificação 3D - Sistema de Acesso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
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
                <h2>Precificação 3D</h2>
                
                <div class="panel-section">
                    <h3>Calcular Preço</h3>
                    
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <label for="hora" class="form-label">Horas</label>
                                <input type="number" class="form-control" id="hora" name="hora" min="0" max="999">
                            </div>
                            <div class="col-md-3">
                                <label for="minuto" class="form-label">Minutos</label>
                                <input type="number" class="form-control" id="minuto" name="minuto" min="0" max="59">
                            </div>
                            <div class="col-md-3">
                                <label for="peso_g" class="form-label">Peso (gramas)</label>
                                <input type="number" step="0.01" class="form-control" id="peso_g" name="peso_g" min="0" required>
                            </div>
                            <div class="col-md-3">
                                <label for="qtd_pecas" class="form-label">Qtd peças</label>
                                <input type="number" step="1" class="form-control" id="qtd_pecas" name="qtd_pecas" min="1" value="1" required>
                            </div>
                            <div class="col-md-3">
                                <label for="acessorios_uni" class="form-label">Acessórios (Unidade)</label>
                                <input type="number" step="0.01" class="form-control" id="acessorios_uni" name="acessorios_uni" min="0">
                            </div>        
                            <div class="col-md-3">
                                <label for="acessorios_tot" class="form-label">Acessórios (Conjunto)</label>
                                <input type="number" step="0.01" class="form-control" id="acessorios_tot" name="acessorios_tot" min="0">
                            </div>
                            <div class="col-md-3">
                                <label for="titulo" class="form-label">Título</label>
                                <input type="string" class="form-control" id="titulo" name="titulo">
                            </div>                          
                        </div>
                        <button type="submit" class="btn btn-primary">Calcular Preço</button>
                    </form>
                </div>
                
                <?php if ($resultado) : ?>
                <div class="panel-section">
                    <div class="card-header bg-resultado text-white">
                        <h3>Resultado da Precificação</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h4>Custos totais (<?php echo $resultado['qtd_pecas']; ?>)</h4>
                                <ul class="list-group">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Material (PLA/PTEG)
                                        <span>R$ <?php echo number_format($resultado['custo_material'], 2, ',', '.'); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Energia
                                        <span>R$ <?php echo number_format($resultado['custo_energia'], 2, ',', '.'); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Custo Fixo
                                        <span>R$ <?php echo number_format($resultado['custo_fixo'], 2, ',', '.'); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Amortização
                                        <span>R$ <?php echo number_format($resultado['amortizacao'], 2, ',', '.'); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <strong>Custo Total</strong>
                                        <span class="cost-highlight">R$ <?php echo number_format($resultado['custo_producao'], 2, ',', '.'); ?></span>
                                    </li>
                                </ul>
                            </div>
                            
                            <div class="col-md-6">
                                <h4>Preços e Lucros</h4>
                                <ul class="list-group">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <strong>Preço final</strong>
                                        <span class="price-highlight">R$ <?php echo number_format($resultado['preco_consumidor'], 2, ',', '.'); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <strong>Lucro Líquido</strong>
                                        <span class="profit-highlight">R$ <?php echo number_format($resultado['lucro_liquido'], 2, ',', '.'); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <strong>Lucro Líquido (%)</strong>
                                        <span class="profit-highlight"><?php echo number_format($resultado['lucro_liq_porc'] * 100, 2, ',', '.'); ?> %</span>
                                    </li>    
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <strong>Preço por unidade</strong>
                                        <span class="price-highlight">R$ <?php echo number_format($resultado['preco_consumidor'] / $resultado['qtd_pecas'], 2, ',', '.'); ?></span>
                                    </li>                                
                                </ul>
                            </div>

                            <div class="col-md-6 mt-4">
                                <h4>Custos por unidade</h4>
                                <ul class="list-group">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Material (PLA/PTEG)
                                        <span>R$ <?php echo number_format($resultado['custo_material'] / $resultado['qtd_pecas'], 2, ',', '.'); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Energia
                                        <span>R$ <?php echo number_format($resultado['custo_energia'] / $resultado['qtd_pecas'], 2, ',', '.'); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Custo Fixo
                                        <span>R$ <?php echo number_format($resultado['custo_fixo'] / $resultado['qtd_pecas'], 2, ',', '.'); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Amortização
                                        <span>R$ <?php echo number_format($resultado['amortizacao'] / $resultado['qtd_pecas'], 2, ',', '.'); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <strong>Custo Unitário</strong>
                                        <span class="cost-highlight">R$ <?php echo number_format($resultado['custo_producao'] / $resultado['qtd_pecas'], 2, ',', '.'); ?></span>
                                    </li>
                                </ul>
                            </div>                                    
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <p>Para uma impressão de <?php echo $hora; ?> hora(s) e <?php echo $minuto; ?> minuto(s), 
                                    com peso de <?php echo $peso_g; ?> gramas, o preço recomendado é 
                                    <strong>R$ <?php echo number_format($resultado['preco_consumidor'], 2, ',', '.'); ?></strong>, 
                                    gerando um lucro líquido de <strong>R$ <?php echo number_format($resultado['lucro_liquido'], 2, ',', '.'); ?></strong>.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!--<div class="panel-actions">
                    <a href="javascript:window.print()" class="btn btn-info">Imprimir Resultado</a>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-primary">Novo Cálculo</a>
                </div>-->
            </main>
        </div>
        
        <?php include '../includes/footer.php'; ?>
    </div>
    
    <script src="../assets/js/sidebar.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
