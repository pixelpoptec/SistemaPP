<?php

ob_start();
require_once '../../config/auth.php';
require_once '../../config/estudos.php';

// Verificar se o usuário está logado
verificaLogin();

// Verificar se o ID foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['mensagem'] = "ID da nota não fornecido.";
    $_SESSION['tipo_mensagem'] = "danger";
    header("Location: cadernos.php");
    exit;
}

$id_seq = intval($_GET['id']);

// Buscar nota
$nota = buscarNota($conn, $id_seq, $_SESSION['usuario_id']);

// Verificar se a nota existe e pertence ao usuário
if (!$nota) {
    $_SESSION['mensagem'] = "Nota não encontrada ou você não tem permissão para acessá-la.";
    $_SESSION['tipo_mensagem'] = "danger";
    header("Location: cadernos.php");
    exit;
}

// Salvar o caderno_id para redirecionamento
$caderno_id = $nota['caderno_id'];

// Excluir nota
$resultado = excluirNota($conn, $id_seq, $_SESSION['usuario_id']);

// Excluir arquivo
$resultado_arquivo = excluirArquivo($conn, $id_seq, $_SESSION['usuario_id']);

if ($resultado) {
    $_SESSION['mensagem'] = "Nota excluída com sucesso!";
    $_SESSION['tipo_mensagem'] = "success";
} else {
    $_SESSION['mensagem'] = "Erro ao excluir nota. Tente novamente.";
    $_SESSION['tipo_mensagem'] = "danger";
}

header("Location: notas.php?caderno_id=" . $caderno_id);
exit;
ob_end_flush();
