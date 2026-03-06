<?php
require_once '../config/auth.php';

require_once __DIR__ . '/../../../../vendor/autoload.php'; // Para as bibliotecas PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

// Verificar se o usuário está logado
verificaLogin();

// Verificar se o usuário tem permissão para acessar relatórios
verificaPermissao('relatorios');

// Processar filtros
$dataInicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : '';
$dataFim = isset($_GET['data_fim']) ? $_GET['data_fim'] : '';
$tipoData = isset($_GET['tipo_data']) ? $_GET['tipo_data'] : 'abertura';
$clienteId = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Função para gerar a consulta SQL baseada nos filtros
function gerarConsultaSQL($dataInicio, $dataFim, $tipoData, $clienteId, $status)
{
    $sql = "SELECT t.id, t.nome, t.detalhes, t.status, 
                  DATE_FORMAT(t.data_abertura, '%d/%m/%Y') as data_abertura, 
                  DATE_FORMAT(t.previsao_termino, '%d/%m/%Y') as previsao_termino, 
                  DATE_FORMAT(t.termino_efetivo, '%d/%m/%Y') as termino_efetivo, 
                  t.tempo_horas, t.tempo_minutos,
                  c.nome as cliente_nome
           FROM tarefas t
           LEFT JOIN clientes c ON t.cliente_id = c.id
           WHERE 1=1";

    if (!empty($dataInicio) && !empty($dataFim)) {
        if ($tipoData == 'abertura') {
            $sql .= " AND t.data_abertura BETWEEN '$dataInicio' AND '$dataFim'";
        } elseif ($tipoData == 'previsao') {
            $sql .= " AND t.previsao_termino BETWEEN '$dataInicio' AND '$dataFim'";
        } elseif ($tipoData == 'conclusao') {
            $sql .= " AND t.termino_efetivo BETWEEN '$dataInicio' AND '$dataFim'";
        }
    }

    if ($clienteId > 0) {
        $sql .= " AND t.cliente_id = '$clienteId'";
    }

    if (!empty($status)) {
        $sql .= " AND t.status = '$status'";
    }

    $sql .= " ORDER BY t.data_abertura DESC";

    return $sql;
}

// Executar consulta
$tarefas = [];
if ($_SERVER['REQUEST_METHOD'] == 'GET' && (isset($_GET['filtrar']) || isset($_GET['exportar']))) {
    $sql = gerarConsultaSQL($dataInicio, $dataFim, $tipoData, $clienteId, $status);
    $result_tarefas = $conn->query($sql);
    $tarefas = [];

    while ($tarefa = $result_tarefas->fetch_assoc()) {
        $tarefas[] = $tarefa;
    }
}
/*  $totalRegistros = "preenchida";
    $botaoExportar = "não apertado";
    if (empty($tarefas)) { $totalRegistros = "vazia"; }
    if (isset($_GET['exportar'])) { $botaoExportar = "apertado"; }
    //$totalRegistros = empty($tarefas);
    $arquivo = 'arquivo_' . date('Ymd_His') . '.txt';;
    $conteudo = $botaoExportar . " - " . $totalRegistros;
    file_put_contents($arquivo, $conteudo); */

// Função para formatar tempo
function formatarTempo($horas, $minutos)
{
    return sprintf("%02d:%02d", $horas, $minutos);
}

// Exportar para Excel
if (isset($_GET['exportar']) && !empty($tarefas)) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Relatório de Tarefas');

    // Estilo para o cabeçalho
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '6B705C'],
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000'],
            ],
        ],
    ];

    // Estilo para as linhas
    $rowStyle = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000'],
            ],
        ],
    ];

    // Definir cabeçalhos
    $headers = ['ID', 'Tarefa', 'Detalhes', 'Cliente', 'Abertura', 'Previsão', 'Conclusão', 'Status', 'Tempo'];
    $sheet->fromArray($headers, null, 'A1');

    // Aplicar estilo ao cabeçalho
    $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);

    // Adicionar dados
    $row = 2;
    foreach ($tarefas as $tarefa) {
        $sheet->setCellValue('A' . $row, $tarefa['id']);
        $sheet->setCellValue('B' . $row, $tarefa['nome']);
        $sheet->setCellValue('C' . $row, $tarefa['detalhes']);
        $sheet->setCellValue('D' . $row, $tarefa['cliente_nome']);
        $sheet->setCellValue('E' . $row, $tarefa['data_abertura']);
        $sheet->setCellValue('F' . $row, $tarefa['previsao_termino']);
        $sheet->setCellValue('G' . $row, $tarefa['termino_efetivo']);
        $sheet->setCellValue('H' . $row, $tarefa['status']);
        $sheet->setCellValue('I' . $row, formatarTempo($tarefa['tempo_horas'], $tarefa['tempo_minutos']));

        // Colorir linhas alternadas
        if ($row % 2 == 0) {
            $sheet->getStyle('A' . $row . ':I' . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('F1E3D3');
        } else {
            $sheet->getStyle('A' . $row . ':I' . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('FFF6EB');
        }

        // Aplicar estilo às linhas
        $sheet->getStyle('I' . $row)->getNumberFormat()->setFormatCode('hh:mm');
        $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray($rowStyle);


        $row++;
    }

    // Ajustar largura das colunas
    foreach (range('A', 'I') as $column) {
        if ($column == 'C') {
            $sheet->getColumnDimension($column)->setWidth(75);
        } else {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    // Criar o arquivo Excel
    $writer = new Xlsx($spreadsheet);
    $filename = 'relatorio_tarefas_' . date('Y-m-d_H-i-s') . '.xlsx';

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer->save('php://output');
    exit;
}

//Obter lista de clientes para o select
//Precisa ficar aqui para preencher logo no inicio
$sql_clientes = "SELECT id, nome FROM clientes ORDER BY nome";
$result_clientes = $conn->query($sql_clientes);
$clientes = [];

while ($cliente = $result_clientes->fetch_assoc()) {
    $clientes[] = $cliente;
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - Sistema de Acesso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #fff6eb;
        }
        
        .content {
            display: flex;
        }
        
        main {
            flex-grow: 1;
            padding: 20px;
        }
        
        .filtro-container {
            background-color: #ddbea9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .tabela-resultados {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .btn-primary {
            background-color: #87b7a4;
            border-color: #87b7a4;
        }
        
        .btn-primary:hover {
            background-color: #6b705c;
            border-color: #6b705c;
        }
        
        .btn-success {
            background-color: #6b705c;
            border-color: #6b705c;
        }
        
        .btn-success:hover {
            background-color: #5a5f4d;
            border-color: #5a5f4d;
        }
        
        .table thead th {
            background-color: #6b705c;
            color: #ffffff;
        }
        
        .table tbody tr:nth-child(odd) {
            background-color: #f1e3d3;
        }
        
        .table tbody tr:nth-child(even) {
            background-color: #fff6eb;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        h2 {
            color: #6b705c;
            margin-bottom: 20px;
        }
        
        h3 {
            color: #c58c6d;
            margin-bottom: 15px;
        }
        
        @media (max-width: 768px) {
            .content {
                flex-direction: column;
            }
            
            .filtro-container .row > div {
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include '../includes/header.php'; ?>
        
        <div class="content">
            <?php include '../includes/sidebar.php'; ?>
            
            <main>
                <h2>Relatórios de Tarefas</h2>
                
                <div class="filtro-container">
                    <h3>Filtros</h3>
                    <form method="GET" action="">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="tipo_data" class="form-label">Tipo de Data</label>
                                <select class="form-select" id="tipo_data" name="tipo_data">
                                    <option value="abertura" <?php echo $tipoData == 'abertura' ? 'selected' : ''; ?>>Data de Abertura</option>
                                    <option value="previsao" <?php echo $tipoData == 'previsao' ? 'selected' : ''; ?>>Previsão de Término</option>
                                    <option value="conclusao" <?php echo $tipoData == 'conclusao' ? 'selected' : ''; ?>>Data de Conclusão</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="data_inicio" class="form-label">Data Inicial</label>
                                <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?php echo $dataInicio; ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="data_fim" class="form-label">Data Final</label>
                                <input type="date" class="form-control" id="data_fim" name="data_fim" value="<?php echo $dataFim; ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">Todos</option>
                                    <option value="aberta" <?php echo $status == 'aberta' ? 'selected' : ''; ?>>Aberta</option>
                                    <option value="fazendo" <?php echo $status == 'fazendo' ? 'selected' : ''; ?>>Fazendo</option>
                                    <option value="esperando" <?php echo $status == 'esperando' ? 'selected' : ''; ?>>Esperando</option>
                                    <option value="concluido" <?php echo $status == 'concluido' ? 'selected' : ''; ?>>Concluído</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="cliente_id" class="form-label">Cliente</label>
                                <select class="form-select" id="cliente_id" name="cliente_id">
                                    <option value="0">Todos</option>
                                    <?php foreach ($clientes as $cliente) : ?>
                                        <option value="<?php echo $cliente['id']; ?>" <?php echo $clienteId == $cliente['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cliente['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mt-4">
                                <button type="submit" name="filtrar" value="1" class="btn btn-primary">Filtrar</button>
                                <?php if (!empty($tarefas)) : ?>
                                    <button type="submit" name="exportar" value="1" class="btn btn-success ms-2">Exportar Excel</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="tabela-resultados">
                    <h3>Resultados</h3>
                    
                    <?php if (empty($tarefas) && isset($_GET['filtrar'])) : ?>
                        <div class="alert alert-info">Nenhum resultado encontrado para os filtros selecionados.</div>
                    <?php elseif (!isset($_GET['filtrar'])) : ?>
                        <div class="alert alert-info">Utilize os filtros acima para gerar o relatório.</div>
                    <?php else : ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Tarefa</th>
                                        <th>Cliente</th>
                                        <th>Abertura</th>
                                        <th>Previsão</th>
                                        <th>Conclusão</th>
                                        <th>Tempo</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tarefas as $tarefa) : ?>
                                        <tr>
                                            <td><?php echo $tarefa['id']; ?></td>
                                            <td><?php echo htmlspecialchars($tarefa['nome']); ?></td>
                                            <td><?php echo htmlspecialchars($tarefa['cliente_nome']); ?></td>
                                            <td><?php echo $tarefa['data_abertura']; ?></td>
                                            <td><?php echo $tarefa['previsao_termino']; ?></td>
                                            <td><?php echo $tarefa['termino_efetivo']; ?></td>
                                            <td><?php echo formatarTempo($tarefa['tempo_horas'], $tarefa['tempo_minutos']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php
                                                    echo match ($tarefa['status']) {
                                                        'aberta' => 'primary',
                                                        'fazendo' => 'warning',
                                                        'esperando' => 'info',
                                                        'concluido' => 'success',
                                                        default => 'secondary'
                                                    };
    ?>">
                                                    <?php echo ucfirst($tarefa['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            <p>Total de registros: <strong><?php echo count($tarefas); ?></strong></p>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
        
        <?php include '../includes/footer.php'; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
    <script src="../assets/js/sidebar.js"></script>
    <script>
        // Validação do formulário
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            
            form.addEventListener('submit', function(event) {
                const dataInicio = document.getElementById('data_inicio').value;
                const dataFim = document.getElementById('data_fim').value;
                
                if (dataInicio && dataFim) {
                    if (dataInicio > dataFim) {
                        alert('A data inicial não pode ser posterior à data final.');
                        event.preventDefault();
                    }
                }
            });
        });
    </script>
</body>
</html>
