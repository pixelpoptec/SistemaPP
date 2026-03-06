<?php
ob_start();
require_once '../../config/auth.php';
require_once '../../config/estudos.php';

// Verificar se o usuário está logado
verificaLogin();

// Verificar se o ID do caderno foi fornecido
if (!isset($_GET['caderno_id']) || empty($_GET['caderno_id'])) {
    $_SESSION['mensagem'] = "ID do caderno não fornecido.";
    $_SESSION['tipo_mensagem'] = "danger";
    header("Location: cadernos.php");
    exit;
}

$caderno_id = intval($_GET['caderno_id']);

// Buscar caderno
$caderno = buscarCaderno($conn, $caderno_id, $_SESSION['usuario_id']);

// Verificar se o caderno existe e pertence ao usuário
if (!$caderno) {
    $_SESSION['mensagem'] = "Caderno não encontrado ou você não tem permissão para acessá-lo.";
    $_SESSION['tipo_mensagem'] = "danger";
    header("Location: cadernos.php");
    exit;
}

// Processar formulário
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $titulo = trim($_POST['titulo']);
    $conteudo = trim($_POST['conteudo']);

    // Validação
    if (empty($titulo)) {
        $_SESSION['mensagem'] = "O título da nota é obrigatório.";
        $_SESSION['tipo_mensagem'] = "danger";
    } else {
        // Criar nota
        $nota_id = criarNota($conn, $titulo, $conteudo, $caderno_id, $_SESSION['usuario_id']);

        if ($nota_id) {
            // Processar arquivos, se houver
            if (isset($_FILES['arquivos']) && !empty($_FILES['arquivos']['name'][0])) {
                $arquivos = reordenarArrayArquivos($_FILES['arquivos']);

                foreach ($arquivos as $arquivo) {
                    if ($arquivo['error'] === 0) {
                        salvarArquivo($conn, $arquivo, $nota_id, $_SESSION['usuario_id']);
                    }
                }
            }

            $_SESSION['mensagem'] = "Nota criada com sucesso!";
            $_SESSION['tipo_mensagem'] = "success";
            header("Location: notas.php?caderno_id=" . $caderno_id);
            exit;
        } else {
            $_SESSION['mensagem'] = "Erro ao criar nota. Tente novamente.";
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
    <title>Nova Nota - Sistema de Acesso</title>
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
                        <li class="breadcrumb-item"><a href="notas.php?caderno_id=<?php echo $caderno_id; ?>"><?php echo htmlspecialchars($caderno['titulo']); ?></a></li>
                        <li class="breadcrumb-item active">Nova Nota</li>
                    </ol>
                </nav>

                <h2>Nova Nota</h2>

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
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?caderno_id=" . $caderno_id; ?>" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="titulo" class="form-label">Título da Nota</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" required>
                        </div>

                        <div class="mb-3">
                            <label for="conteudo" class="form-label">Conteúdo</label>
                            <div id="descricao" class="form-group">
                                <textarea class="form-control" id="conteudo" name="conteudo" rows="10"></textarea>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="arquivos" class="form-label">Arquivos (opcional)</label>
                            <input type="file" class="form-control" id="arquivos" name="arquivos[]" multiple>
                            <small class="form-text text-muted">Você pode selecionar múltiplos arquivos.</small>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="notas.php?caderno_id=<?php echo $caderno_id; ?>" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Criar Nota</button>
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
