<?php

session_start();
require_once '../../config/auth.php';

verificaLogin();

// Receber dados via POST como JSON
$input    = json_decode(file_get_contents('php://input'), true);
$response = ['success' => false];

if (!$input || !isset($input['tarefa_id']) || !isset($input['usuario_id'])) {
    $response['message'] = 'Dados incompletos';
    echo json_encode($response);
    exit;
}

$tarefa_id        = (int)$input['tarefa_id'];
$usuario_id       = (int)$input['usuario_id'];
$data_hora_inicio = date('Y-m-d H:i:s');

// Verificar se a tarefa pertence ao usuário e não está concluída/em espera
$sql_check = "SELECT id FROM tarefas
              WHERE id = ?
              AND usuario_id = ?
              AND status != 'concluido'
              AND status != 'esperando'";

$stmt = $conn->prepare($sql_check);
$stmt->bind_param("ii", $tarefa_id, $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $response['message'] = 'Tarefa inválida ou não disponível para rastreamento';
    echo json_encode($response);
    exit;
}

// Verificar se já existe um rastreamento ativo para este usuário
$sql_check_ativo = "SELECT id FROM tempo_rastreamento
                    WHERE usuario_id = ? AND data_hora_fim IS NULL";

$stmt = $conn->prepare($sql_check_ativo);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row            = $result->fetch_assoc();
    $registro_ativo = $row['id'];

    // Finalizar o registro ativo anterior antes de iniciar um novo
    $sql_finalizar = "UPDATE tempo_rastreamento SET data_hora_fim = ? WHERE id = ?";
    $stmt          = $conn->prepare($sql_finalizar);
    $stmt->bind_param("si", $data_hora_inicio, $registro_ativo);
    $stmt->execute();
}

// Inserir novo registro
$sql_insert = "INSERT INTO tempo_rastreamento
               (tarefa_id, usuario_id, data_hora_inicio, segundos_totais)
               VALUES (?, ?, ?, 0)";

$stmt = $conn->prepare($sql_insert);
$stmt->bind_param("iis", $tarefa_id, $usuario_id, $data_hora_inicio);

if ($stmt->execute()) {
    $registro_id = $conn->insert_id;
    $response    = [
        'success'     => true,
        'registro_id' => $registro_id,
        'message'     => 'Rastreamento iniciado com sucesso',
    ];
} else {
    $response['message'] = 'Erro ao iniciar rastreamento: ' . $conn->error;
}

echo json_encode($response);
