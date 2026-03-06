<?php

ob_start();
require_once '../../config/auth.php';
require_once '../../config/estudos.php';

// Verificar se o usuário está logado
verificaLogin();

// Verificar se o ID foi fornecido
if (!isset($_GET['id']) || empty($_GET['id']) || !isset($_GET['nota_id']) || empty($_GET['nota_id'])) {
    $_SESSION['mensagem'] = "Parâmetros inválidos.";
    $_SESSION['tipo_mensagem'] = "danger";
    header("Location: cadernos.php");
    exit;
}

$id_seq = intval($_GET['id']);
$nota_id = intval($_GET['nota_id']);

// Excluir arquivo
$resultado = excluirArquivo($conn, $id_seq, $_SESSION['usuario_id']);

if ($resultado) {
    $_SESSION['mensagem'] = "Arquivo excluído com sucesso!";
    $_SESSION['tipo_mensagem'] = "success";
} else {
    $_SESSION['mensagem'] = "Erro ao excluir arquivo. Tente novamente.";
    $_SESSION['tipo_mensagem'] = "danger";
}

header("Location: editar_nota.php?id=" . $nota_id);
exit;
ob_end_flush();
