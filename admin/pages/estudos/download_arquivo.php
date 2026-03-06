<?php

require_once '../../config/auth.php';
require_once '../../config/estudos.php';

// Verificar se o usuário está logado
verificaLogin();

// Verificar se o ID foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['mensagem'] = "ID do arquivo não fornecido.";
    $_SESSION['tipo_mensagem'] = "danger";
    header("Location: cadernos.php");
    exit;
}

$id_seq = intval($_GET['id']);

// Buscar informações do arquivo
$sql = "SELECT * FROM arquivos WHERE id = ? AND usuario_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id_seq, $_SESSION['usuario_id']);
$stmt->execute();
$result = $stmt->get_result();
$arquivo = $result->fetch_assoc();

// Verificar se o arquivo existe e pertence ao usuário
if (!$arquivo) {
    $_SESSION['mensagem'] = "Arquivo não encontrado ou você não tem permissão para acessá-lo.";
    $_SESSION['tipo_mensagem'] = "danger";
    header("Location: cadernos.php");
    exit;
}

// Caminho do arquivo
$caminho_arquivo = "../../uploads/estudos/" . $arquivo['caminho'];

// Verificar se o arquivo existe
if (!file_exists($caminho_arquivo)) {
    $_SESSION['mensagem'] = "Arquivo não encontrado no servidor.";
    $_SESSION['tipo_mensagem'] = "danger";
    header("Location: cadernos.php");
    exit;
}

// Configurar cabeçalhos para download
header('Content-Description: File Transfer');
header('Content-Type: ' . $arquivo['tipo']);
header('Content-Disposition: attachment; filename="' . $arquivo['nome'] . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($caminho_arquivo));

// Limpar o buffer de saída
ob_clean();
flush();

// Ler o arquivo e enviá-lo para o navegador
readfile($caminho_arquivo);
exit;
