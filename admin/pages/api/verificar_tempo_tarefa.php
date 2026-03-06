<?php

//Esse arquivo retorna o tempo já gravado na tarefa
session_start();
require_once '../../config/auth.php';
verificaLogin();

$tarefa_id = isset($_GET['tarefa_id']) ? (int)$_GET['tarefa_id'] : 0;
$response = ['tempoRegistrado' => false];

if ($tarefa_id <= 0) {
    echo json_encode($response);
    exit;
}

// Verificar se a tarefa existe
$sql_check = "SELECT id, tempo_horas, tempo_minutos FROM tarefas WHERE id = ?";
$stmt = $conn->prepare($sql_check);
$stmt->bind_param("i", $tarefa_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode($response);
    exit;
}

$row = $result->fetch_assoc();
$tempo_gasto = (int)$row['tempo_horas'] / 60 + (int)$row['tempo_minutos'];

if ($tempo_gasto > 0) {
    // Formatar o tempo (tempo_gasto está em minutos)
    $horas = $row['tempo_horas'];
    $minutos = $row['tempo_minutos'];

    $tempo_formatado = sprintf("%02dh%02dm", $horas, $minutos);

    $response = [
        'tempoRegistrado' => true,
        'tempoMinutos' => $minutos,
        'tempoFormatado' => $tempo_formatado
    ];
}

echo json_encode($response);
