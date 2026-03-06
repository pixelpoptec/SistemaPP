<?php
require_once '../config/auth.php';
require_once '../config/functions.php';

// Verificar se o usuário está logado
verificaLogin();

// Processar o formulário quando enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = trim($_POST['nome']);
    $descricao = trim($_POST['descricao']);
    $resumo = trim($_POST['resumo']);
    $status = trim($_POST['status']);
    $usuario_id = $_SESSION['usuario_id'];

    // Validação básica
    $erros = [];

    if (empty($nome)) {
        $erros[] = "O nome do projeto é obrigatório";
    }

    if (empty($erros)) {
        $sql = "INSERT INTO projetos (nome, descricao, resumo, status, usuario_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $nome, $descricao, $resumo, $status, $usuario_id);

        if ($stmt->execute()) {
            $projeto_id = $conn->insert_id;
            $_SESSION['mensagem'] = "Projeto cadastrado com sucesso!";
            $_SESSION['tipo_mensagem'] = "success";
            header("Location: projeto_detalhes.php?id=$projeto_id");
            exit;
        } else {
            $erros[] = "Erro ao cadastrar o projeto: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Projeto - Sistema de Gerenciamento</title>
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
                    <h2>Cadastro de Projeto</h2>
                    <a href="projetos.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Voltar
                    </a>
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
                        <h3 class="mb-0"><i class="bi bi-folder-plus"></i> Novo Projeto</h3>
                    </div>

                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome do Projeto *</label>
                            <input type="text" class="form-control" id="nome" name="nome" required 
                                value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="resumo" class="form-label">Resumo</label>
                            <textarea class="form-control" id="resumo" name="resumo" rows="2"><?php echo isset($_POST['resumo']) ? htmlspecialchars($_POST['resumo']) : ''; ?></textarea>
                            <div class="form-text">Um breve resumo do projeto (máximo 200 caracteres)</div>
                        </div>

                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição Detalhada</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="5"><?php echo isset($_POST['descricao']) ? htmlspecialchars($_POST['descricao']) : ''; ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="Em planejamento" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Em planejamento') ? 'selected' : ''; ?>>Em planejamento</option>
                                <option value="Em desenvolvimento" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Em desenvolvimento') ? 'selected' : ''; ?>>Em desenvolvimento</option>
                                <option value="Em teste" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Em teste') ? 'selected' : ''; ?>>Em teste</option>
                                <option value="Concluído" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Concluído') ? 'selected' : ''; ?>>Concluído</option>
                                <option value="Pausado" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Pausado') ? 'selected' : ''; ?>>Pausado</option>
                            </select>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="reset" class="btn btn-secondary me-md-2">Limpar</button>
                            <button type="submit" class="btn btn-primary">Cadastrar Projeto</button>
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
