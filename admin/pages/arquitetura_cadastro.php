<?php
require_once '../config/auth.php';
require_once '../config/functions.php';

// Verificar se o usuário está logado
verificaLogin();

// Verificar se o ID do projeto foi fornecido
if (!isset($_GET['projeto_id']) || !is_numeric($_GET['projeto_id'])) {
    $_SESSION['mensagem'] = "ID do projeto inválido!";
    $_SESSION['tipo_mensagem'] = "danger";
    header("Location: projetos.php");
    exit;
}

$projeto_id = intval($_GET['projeto_id']);

// Função para obter detalhes do projeto
function getProjetoDetalhes($conn, $id_seq)
{
    $sql = "SELECT * FROM projetos WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_seq);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return null;
    }

    return $result->fetch_assoc();
}

$projeto = getProjetoDetalhes($conn, $projeto_id);

if (!$projeto) {
    $_SESSION['mensagem'] = "Projeto não encontrado!";
    $_SESSION['tipo_mensagem'] = "danger";
    header("Location: projetos.php");
    exit;
}

// Processar o formulário quando enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $titulo = trim($_POST['titulo']);
    $descricao = trim($_POST['descricao']);
    $diagrama_url = trim($_POST['diagrama_url']);

    // Validação básica
    $erros = [];

    if (empty($titulo)) {
        $erros[] = "O título da documentação é obrigatório";
    }

    if (empty($descricao)) {
        $erros[] = "A descrição da arquitetura é obrigatória";
    }

    if (empty($erros)) {
        $sql = "INSERT INTO arquitetura (projeto_id, titulo, descricao, diagrama_url) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isss", $projeto_id, $titulo, $descricao, $diagrama_url);

        if ($stmt->execute()) {
            $_SESSION['mensagem'] = "Documentação de arquitetura cadastrada com sucesso!";
            $_SESSION['tipo_mensagem'] = "success";
            header("Location: projeto_detalhes.php?id=$projeto_id#arquitetura");
            exit;
        } else {
            $erros[] = "Erro ao cadastrar a documentação: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Arquitetura - Sistema de Gerenciamento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .form-container {
            background-color: #fff6eb;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .form-header {
            background-color: #87b7a4;
            color: white;
            padding: 15px;
            border-radius: 10px 10px 0 0;
            margin: -20px -20px 20px -20px;
        }
        .btn-primary {
            background-color: #6b705c;
            border-color: #6b705c;
        }
        .btn-primary:hover {
            background-color: #5a5f4d;
            border-color: #5a5f4d;
        }
        .btn-secondary {
            background-color: #ddbea9;
            border-color: #ddbea9;
            color: #000;
        }
        .btn-secondary:hover {
            background-color: #c9ab96;
            border-color: #c9ab96;
            color: #000;
        }
        .projeto-info {
            background-color: #f1e3d3;
            border-left: 5px solid #c58c6d;
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include '../includes/header.php'; ?>

        <div class="content">
            <?php if (isMobile()) : ?>
                <?php include '../includes/sidebar_m.php'; ?>
            <?php else : ?>
                <?php include '../includes/sidebar.php'; ?>
            <?php endif; ?>

            <main>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Cadastro de Documentação de Arquitetura</h2>
                    <a href="projeto_detalhes.php?id=<?php echo $projeto_id; ?>#arquitetura" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Voltar ao Projeto
                    </a>
                </div>

                <div class="projeto-info">
                    <h5>Projeto: <?php echo htmlspecialchars($projeto['nome']); ?></h5>
                    <p class="mb-0"><?php echo mb_strimwidth(htmlspecialchars($projeto['resumo']), 0, 150, "..."); ?></p>
                </div>

                <?php if (!empty($erros)) : ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($erros as $erro) : ?>
                            <li><?php echo $erro; ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <div class="form-container">
                    <div class="form-header">
                        <h3 class="mb-0"><i class="bi bi-diagram-3"></i> Nova Documentação de Arquitetura</h3>
                    </div>

                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?projeto_id=" . $projeto_id); ?>">
                        <div class="mb-3">
                            <label for="titulo" class="form-label">Título *</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" required 
                                value="<?php echo isset($_POST['titulo']) ? htmlspecialchars($_POST['titulo']) : ''; ?>">
                            <div class="form-text">Ex: Arquitetura Geral, Diagrama de Classes, Fluxo de Dados, etc.</div>
                        </div>

                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição da Arquitetura *</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="6" required><?php echo isset($_POST['descricao']) ? htmlspecialchars($_POST['descricao']) : ''; ?></textarea>
                            <div class="form-text">Descreva detalhadamente a arquitetura, padrões de projeto, componentes e suas interações</div>
                        </div>

                        <div class="mb-3">
                            <label for="diagrama_url" class="form-label">URL do Diagrama (opcional)</label>
                            <input type="url" class="form-control" id="diagrama_url" name="diagrama_url" 
                                value="<?php echo isset($_POST['diagrama_url']) ? htmlspecialchars($_POST['diagrama_url']) : ''; ?>">
                            <div class="form-text">Link para uma imagem do diagrama de arquitetura</div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="reset" class="btn btn-secondary me-md-2">Limpar</button>
                            <button type="submit" class="btn btn-primary">Cadastrar Documentação</button>
                        </div>
                    </form>
                </div>
            </main>
        </div>

        <?php include '../includes/footer.php'; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/sidebar.js"></script>
</body>
</html>
