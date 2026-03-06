<?php

ob_start();
require_once '../../config/auth.php';
require_once '../../config/estudos.php';

// Verificar se o usuário está logado
verificaLogin();

// Verificar se o ID foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['mensagem'] = "ID do caderno não fornecido.";
    $_SESSION['tipo_mensagem'] = "danger";
    header("Location: cadernos.php");
    exit;
}

$id_seq = intval($_GET['id']);

// Buscar caderno
$caderno = buscarCaderno($conn, $id_seq, $_SESSION['usuario_id']);

// Verificar se o caderno existe e pertence ao usuário
if (!$caderno) {
    $_SESSION['mensagem'] = "Caderno não encontrado ou você não tem permissão para acessá-lo.";
    $_SESSION['tipo_mensagem'] = "danger";
    header("Location: cadernos.php");
    exit;
}

// Excluir caderno
$resultado = excluirCaderno($conn, $id_seq, $_SESSION['usuario_id']);

if ($resultado) {
    $_SESSION['mensagem'] = "Caderno excluído com sucesso!";
    $_SESSION['tipo_mensagem'] = "success";
} else {
    $_SESSION['mensagem'] = "Erro ao excluir caderno. Tente novamente.";
    $_SESSION['tipo_mensagem'] = "danger";
}

header("Location: cadernos.php");
exit;
ob_end_flush();
