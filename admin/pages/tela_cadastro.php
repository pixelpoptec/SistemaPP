<?php
require_once '../config/auth.php';
require_once '../config/functions.php';

// Verificar se o usuário está logado
verificaLogin();

// Verificar se o ID do módulo foi fornecido
if (!isset($_GET['modulo_id']) || !is_numeric($_GET['modulo_id'])) {
    $_SESSION['mensagem'] = "ID do módulo inválido!";
    $_SESSION['tipo_mensagem'] = "danger";
    header("Location: projetos.php");
    exit;
}

$modulo_id = intval($_GET['modulo_id']);

// Função para obter detalhes do módulo
function getModuloDetalhes($conn, $id_seq)
{
    $sql = "SELECT m.*, p.id as projeto_id, p.nome as projeto_nome 
            FROM modulos m
            JOIN projetos p ON m.projeto_id = p.id
            WHERE m.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_seq);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return null;
    }

    return $result->fetch_assoc();
}

$modulo = getModuloDetalhes($conn, $modulo_id);

if (!$modulo) {
    $_SESSION['mensagem'] = "Módulo não encontrado!";
    $_SESSION['tipo_mensagem'] = "danger";
    header("Location: projetos.php");
    exit;
}

// Processar o formulário quando enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = trim($_POST['nome']);
    $descricao = trim($_POST['descricao']);
    $mockup_url = trim($_POST['mockup_url']);

    // Validação básica
    $erros = [];

    if (empty($nome)) {
        $erros[] = "O nome da tela é obrigatório";
    }

    if (empty($erros)) {
        $sql = "INSERT INTO telas (modulo_id, nome, descricao, mockup_url) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isss", $modulo_id, $nome, $descricao, $mockup_url);

        if ($stmt->execute()) {
            $_SESSION['mensagem'] = "Tela cadastrada com sucesso!";
            $_SESSION['tipo_mensagem'] = "success";
            header("Location: modulo_detalhes.php?id=$modulo_id");
            exit;
        } else {
            $erros[] = "Erro ao cadastrar a tela: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Tela - Sistema de Gerenciamento</title>
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
        .modulo-info {
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
                    <h2>Cadastro de Tela</h2>
                    <a href="modulo_detalhes.php?id=<?php echo $modulo_id; ?>" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Voltar ao Módulo
                    </a>
                </div>

                <div class="modulo-info">
                    <h5>Módulo: <?php echo htmlspecialchars($modulo['nome']); ?></h5>
                    <p class="mb-0">Projeto: <?php echo htmlspecialchars($modulo['projeto_nome']); ?></p>
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
                        <h3 class="mb-0"><i class="bi bi-window"></i> Nova Tela</h3>
                    </div>

                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?modulo_id=" . $modulo_id); ?>">
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome da Tela *</label>
                            <input type="text" class="form-control" id="nome" name="nome" required 
                                value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição da Tela</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="3"><?php echo isset($_POST['descricao']) ? htmlspecialchars($_POST['descricao']) : ''; ?></textarea>
                            <div class="form-text">Descreva o propósito e funcionalidades desta tela</div>
                        </div>

                        <div class="mb-3">
                            <label for="mockup_url" class="form-label">URL do Mockup (opcional)</label>
                            <input type="url" class="form-control" id="mockup_url" name="mockup_url" 
                                value="<?php echo isset($_POST['mockup_url']) ? htmlspecialchars($_POST['mockup_url']) : ''; ?>">
                            <div class="form-text">Link para uma imagem ou protótipo da tela</div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="reset" class="btn btn-secondary me-md-2">Limpar</button>
                            <button type="submit" class="btn btn-primary">Cadastrar Tela</button>
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
