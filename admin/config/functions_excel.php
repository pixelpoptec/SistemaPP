<?php

session_start();
require_once '../config/auth.php';
require_once __DIR__ . '/../../../../vendor/autoload.php'; // Para as bibliotecas PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

// Verificar se o usuário está logado
verificaLogin();

// Filtros de pesquisa
$filtro_status = isset($_SESSION['filtro_status']) ? $_SESSION['filtro_status'] : '';
$filtro_prioridade = isset($_SESSION['filtro_prioridade']) ? $_SESSION['filtro_prioridade'] : '';
$filtro_cliente = isset($_SESSION['filtro_cliente']) ? $_SESSION['filtro_cliente'] : 0;
$filtro_busca = isset($_SESSION['filtro_busca']) ? $_SESSION['filtro_busca'] : '';

// Função para gerar a consulta SQL baseada nos filtros
function gerarConsultaSQL($filtro_status, $filtro_prioridade, $filtro_cliente, $filtro_busca)
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

    if ($filtro_cliente > 0) {
        $sql .= " AND t.cliente_id = '$filtro_cliente'";
    }

    if (!empty($filtro_status)) {
        $sql .= " AND t.status = '$filtro_status'";
    }

    if (!empty($filtro_prioridade)) {
        $sql .= " AND t.prioridade = '$filtro_prioridade'";
    }

    if (!empty($filtro_busca)) {
        $sql .= " AND (t.nome LIKE '%$filtro_busca%' OR t.detalhes LIKE '%$filtro_busca%')";
    }

    $sql .= " ORDER BY t.data_abertura DESC";

    return $sql;
}

// Função para formatar tempo
function formatarTempo($horas, $minutos)
{
    return sprintf("%02d:%02d", $horas, $minutos);
}

// Exportar para Excel
if (isset($_GET['exportar'])) {
    $sql = gerarConsultaSQL($filtro_status, $filtro_prioridade, $filtro_cliente, $filtro_busca);

    $arquivo = 'arquivo_' . date('Ymd_His') . '.txt';
    ;
    $conteudo = $sql;
    file_put_contents($arquivo, $conteudo);

    $result_tarefas = $conn->query($sql);
    $tarefas = [];

    while ($tarefa = $result_tarefas->fetch_assoc()) {
        $tarefas[] = $tarefa;
    }

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
