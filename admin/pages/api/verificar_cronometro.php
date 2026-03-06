<?php

// Aqui verificar se o cronometro não foi fechado, caso
// não tenha data de fim, ele recomeça
session_start();
require_once '../../config/auth.php';

verificaLogin();

$response   = ['ativo' => false];
$usuario_id = $_SESSION['usuario_id'];

// Verificar se há um rastreamento ativo para este usuário
$sql = "SELECT tr.*, t.nome as tarefa_nome
        FROM tempo_rastreamento tr
        JOIN tarefas t ON tr.tarefa_id = t.id
        WHERE tr.usuario_id = ? AND tr.data_hora_fim IS NULL";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row             = $result->fetch_assoc();
    $inicio          = new DateTime($row['data_hora_inicio']);
    $agora           = new DateTime();
    $segundos_salvos = (int)$row['segundos_totais'];

    $intervalo          = $inicio->diff($agora);
    $segundos_intervalo = $intervalo->days * 86400
        + $intervalo->h * 3600
        + $intervalo->i * 60
        + $intervalo->s;

    $response = [
        'ativo'            => true,
        'registro_id'      => $row['id'],
        'tarefa_id'        => $row['tarefa_id'],
        'tarefa_titulo'    => $row['tarefa_nome'],
        'segundos_totais'  => $segundos_salvos,
        'observacoes'      => $row['observacoes'],
        'ultima_atualizacao' => $row['data_hora_inicio'],
    ];
}

echo json_encode($response);
