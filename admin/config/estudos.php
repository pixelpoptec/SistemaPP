<?php

// Funções para o sistema de gerenciamento de estudos

// Buscar todos os cadernos de um usuário
function buscarCadernos($conn, $usuario_id)
{
    $sql = "SELECT * FROM cadernos WHERE usuario_id = ? ORDER BY titulo ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $cadernos = [];
    while ($row = $result->fetch_assoc()) {
        $cadernos[] = $row;
    }

    return $cadernos;
}

// Buscar um caderno específico
function buscarCaderno($conn, $id_seq, $usuario_id)
{
    $sql = "SELECT * FROM cadernos WHERE id = ? AND usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_seq, $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc();
}

// Criar um novo caderno
function criarCaderno($conn, $titulo, $usuario_id)
{
    $sql = "INSERT INTO cadernos (titulo, usuario_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $titulo, $usuario_id);

    if ($stmt->execute()) {
        return $conn->insert_id;
    }

    return false;
}

// Atualizar um caderno
function atualizarCaderno($conn, $id_seq, $titulo, $usuario_id)
{
    $sql = "UPDATE cadernos SET titulo = ? WHERE id = ? AND usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $titulo, $id_seq, $usuario_id);

    return $stmt->execute();
}

// Excluir um caderno
function excluirCaderno($conn, $id_seq, $usuario_id)
{
    $sql = "DELETE FROM cadernos WHERE id = ? AND usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_seq, $usuario_id);

    return $stmt->execute();
}

// Buscar todas as notas de um caderno
function buscarNotas($conn, $caderno_id, $usuario_id)
{
    $sql = "SELECT * FROM notas WHERE caderno_id = ? AND usuario_id = ? ORDER BY data_atualizacao DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $caderno_id, $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $notas = [];
    while ($row = $result->fetch_assoc()) {
        $notas[] = $row;
    }

    return $notas;
}

// Buscar uma nota específica
function buscarNota($conn, $id_seq, $usuario_id)
{
    $sql = "SELECT n.*, c.titulo as caderno_titulo 
            FROM notas n 
            JOIN cadernos c ON n.caderno_id = c.id 
            WHERE n.id = ? AND n.usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_seq, $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc();
}

// Criar uma nova nota
function criarNota($conn, $titulo, $conteudo, $caderno_id, $usuario_id)
{
    $sql = "INSERT INTO notas (titulo, conteudo, caderno_id, usuario_id) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $titulo, $conteudo, $caderno_id, $usuario_id);

    if ($stmt->execute()) {
        return $conn->insert_id;
    }

    return false;
}

// Atualizar uma nota
function atualizarNota($conn, $id_seq, $titulo, $conteudo, $caderno_id, $usuario_id)
{
    $sql = "UPDATE notas SET titulo = ?, conteudo = ?, caderno_id = ? WHERE id = ? AND usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssiii", $titulo, $conteudo, $caderno_id, $id_seq, $usuario_id);

    return $stmt->execute();
}

// Excluir uma nota
function excluirNota($conn, $id_seq, $usuario_id)
{
    $sql = "DELETE FROM notas WHERE id = ? AND usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_seq, $usuario_id);

    return $stmt->execute();
}

// Buscar arquivos de uma nota
function buscarArquivos($conn, $nota_id, $usuario_id)
{
    $sql = "SELECT * FROM arquivos WHERE nota_id = ? AND usuario_id = ? ORDER BY data_upload DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $nota_id, $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $arquivos = [];
    while ($row = $result->fetch_assoc()) {
        $arquivos[] = $row;
    }

    return $arquivos;
}

// Salvar arquivo
function salvarArquivo($conn, $arquivo, $nota_id, $usuario_id)
{
    // Diretório para salvar os arquivos
    $diretorio = "../uploads/estudos/";

    // Criar diretório se não existir
    if (!file_exists($diretorio)) {
        mkdir($diretorio, 0777, true);
    }

    // Gerar nome único para o arquivo
    $nome_arquivo = uniqid() . '_' . $arquivo['name'];
    $caminho_arquivo = $diretorio . $nome_arquivo;

    // Mover o arquivo para o diretório
    if (move_uploaded_file($arquivo['tmp_name'], $caminho_arquivo)) {
        // Salvar informações do arquivo no banco de dados
        $sql = "INSERT INTO arquivos (nome, tipo, tamanho, caminho, nota_id, usuario_id) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssissi",
            $arquivo['name'],
            $arquivo['type'],
            $arquivo['size'],
            $nome_arquivo,
            $nota_id,
            $usuario_id
        );

        if ($stmt->execute()) {
            return $conn->insert_id;
        }
    }

    return false;
}

// Excluir arquivo
function excluirArquivo($conn, $id_seq, $usuario_id)
{
    // Buscar informações do arquivo
    $sql = "SELECT * FROM arquivos WHERE id = ? AND usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_seq, $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $arquivo = $result->fetch_assoc();

    if ($arquivo) {
        // Excluir arquivo físico
        $caminho_arquivo = "../uploads/estudos/" . $arquivo['caminho'];
        if (file_exists($caminho_arquivo)) {
            unlink($caminho_arquivo);
        }

        // Excluir registro do banco de dados
        $sql = "DELETE FROM arquivos WHERE id = ? AND usuario_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $id_seq, $usuario_id);

        return $stmt->execute();
    }

    return false;
}

// Renderizar formatação Markdown-like
function renderizarFormatacao($texto)
{
    // Substituir ** por <strong>
    $texto = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $texto);

    // Substituir * por <em>
    $texto = preg_replace('/\*(.*?)\*/s', '<em>$1</em>', $texto);

    // Substituir ~~ por <del>
    $texto = preg_replace('/~~(.*?)~~/s', '<del>$1</del>', $texto);

    // Substituir quebras de linha por <br>
    $texto = nl2br($texto);

    return $texto;
}
