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

// Buscar cadernos para o select
$cadernos = buscarCadernos($conn, $_SESSION['usuario_id']);

// Buscar arquivos da nota
$arquivos = buscarArquivos($conn, $id_seq, $_SESSION['usuario_id']);

// Processar formulário
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $titulo = trim($_POST['titulo']);
    $conteudo = trim($_POST['conteudo']);
    $caderno_id = intval($_POST['caderno_id']);

    // Validação
    if (empty($titulo)) {
        $_SESSION['mensagem'] = "O título da nota é obrigatório.";
        $_SESSION['tipo_mensagem'] = "danger";
    } else {
        // Atualizar nota
        $resultado = atualizarNota($conn, $id_seq, $titulo, $conteudo, $caderno_id, $_SESSION['usuario_id']);

        if ($resultado) {
            // Processar arquivos, se houver
            if (isset($_FILES['arquivos']) && !empty($_FILES['arquivos']['name'][0])) {
                $arquivos_novos = reordenarArrayArquivos($_FILES['arquivos']);

                foreach ($arquivos_novos as $arquivo) {
                    if ($arquivo['error'] === 0) {
                        salvarArquivo($conn, $arquivo, $id_seq, $_SESSION['usuario_id']);
                    }
                }
            }

            $_SESSION['mensagem'] = "Nota atualizada com sucesso!";
            $_SESSION['tipo_mensagem'] = "success";
            header("Location: notas.php?caderno_id=" . $caderno_id);
            exit;
        } else {
            $_SESSION['mensagem'] = "Erro ao atualizar nota. Tente novamente.";
            $_SESSION['tipo_mensagem'] = "danger";
        }
    }
}

// Função para reordenar array de arquivos
function reordenarArrayArquivos($arquivos_array)
{
    $arquivos = [];
    $total = count($arquivos_array['name']);

    for ($i = 0; $i < $total; $i++) {
        $arquivos[$i] = [
            'name' => $arquivos_array['name'][$i],
            'type' => $arquivos_array['type'][$i],
            'tmp_name' => $arquivos_array['tmp_name'][$i],
            'error' => $arquivos_array['error'][$i],
            'size' => $arquivos_array['size'][$i]
        ];
    }

    return $arquivos;
}
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Nota - Sistema de Acesso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include '../../includes/header.php'; ?>

        <div class="content">
            <?php if (isMobile()) : ?>
                <?php include '../../includes/sidebar_m.php'; ?>
            <?php else : ?>
                <?php include '../../includes/sidebar.php'; ?>
            <?php endif; ?>

            <main>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="cadernos.php">Estudos</a></li>
                        <li class="breadcrumb-item"><a href="notas.php?caderno_id=<?php echo $nota['caderno_id']; ?>"><?php echo htmlspecialchars($nota['caderno_titulo']); ?></a></li>
                        <li class="breadcrumb-item active">Editar Nota</li>
                    </ol>
                </nav>

                <h2>Editar Nota</h2>

                <?php if (isset($_SESSION['mensagem'])) : ?>
                    <div class="alert alert-<?php echo $_SESSION['tipo_mensagem']; ?>">
                        <?php
                        echo $_SESSION['mensagem'];
                        unset($_SESSION['mensagem']);
                        unset($_SESSION['tipo_mensagem']);
                        ?>
                    </div>
                <?php endif; ?>

                <div class="panel-section">
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $id_seq; ?>" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="titulo" class="form-label">Título da Nota</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" value="<?php echo htmlspecialchars($nota['titulo']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="caderno_id" class="form-label">Caderno</label>
                            <select class="form-control" id="caderno_id" name="caderno_id" required>
                                <?php foreach ($cadernos as $caderno) : ?>
                                    <option value="<?php echo $caderno['id']; ?>" <?php echo ($caderno['id'] == $nota['caderno_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($caderno['titulo']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="conteudo" class="form-label">Conteúdo</label>
                            <div id="descricao" class="form-group">
                                <textarea class="form-control" id="conteudo" name="conteudo" rows="10"><?php echo htmlspecialchars($nota['conteudo']); ?></textarea>
                            </div>
                        </div>

                        <?php if (!empty($arquivos)) : ?>
                            <div class="mb-3">
                                <label class="form-label">Arquivos Atuais</label>
                                <ul class="list-group">
                                    <?php foreach ($arquivos as $arquivo) : ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span><?php echo htmlspecialchars($arquivo['nome']); ?></span>
                                            <div>
                                                <a href="download_arquivo.php?id=<?php echo $arquivo['id']; ?>" class="btn btn-sm btn-info me-2">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <a href="excluir_arquivo.php?id=<?php echo $arquivo['id']; ?>&nota_id=<?php echo $id_seq; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir este arquivo?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="arquivos" class="form-label">Adicionar Novos Arquivos (opcional)</label>
                            <input type="file" class="form-control" id="arquivos" name="arquivos[]" multiple>
                            <small class="form-text text-muted">Você pode selecionar múltiplos arquivos.</small>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="notas.php?caderno_id=<?php echo $nota['caderno_id']; ?>" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Atualizar Nota</button>
                        </div>
                    </form>
                </div>
            </main>
        </div>

        <?php include '../../includes/footer.php'; ?>
    </div>

    <script src="../../assets/js/sidebar.js"></script>
    <script src="../../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
