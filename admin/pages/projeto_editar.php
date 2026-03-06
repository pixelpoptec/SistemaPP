<?php
require_once '../config/auth.php';
require_once '../config/functions.php';

// Verificar se o usuário está logado
verificaLogin();

// Verificar se o ID do projeto foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['mensagem'] = "ID do projeto inválido!";
    $_SESSION['tipo_mensagem'] = "danger";
    header("Location: projetos.php");
    exit;
}

$projeto_id = intval($_GET['id']);

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
    $nome = trim($_POST['nome']);
    $descricao = trim($_POST['descricao']);
    $resumo = trim($_POST['resumo']);
    $status = trim($_POST['status']);

    // Validação básica
    $erros = [];

    if (empty($nome)) {
        $erros[] = "O nome do projeto é obrigatório";
    }

    if (empty($erros)) {
        $sql = "UPDATE projetos SET nome = ?, descricao = ?, resumo = ?, status = ?, data_atualizacao = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $nome, $descricao, $resumo, $status, $projeto_id);

        if ($stmt->execute()) {
            $_SESSION['mensagem'] = "Projeto atualizado com sucesso!";
            $_SESSION['tipo_mensagem'] = "success";
            header("Location: projeto_detalhes.php?id=$projeto_id");
            exit;
        } else {
            $erros[] = "Erro ao atualizar o projeto: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Projeto - Sistema de Gerenciamento</title>
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
        .btn-danger {
            background-color: #c58c6d;
            border-color: #c58c6d;
        }
        .btn-danger:hover {
            background-color: #b47e61;
            border-color: #b47e61;
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
                    <h2>Editar Projeto</h2>
                    <a href="projeto_detalhes.php?id=<?php echo $projeto_id; ?>" class="btn btn-secondary">
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
                        <h3 class="mb-0"><i class="bi bi-pencil-square"></i> Editar Projeto</h3>
                    </div>

                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $projeto_id); ?>">
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome do Projeto *</label>
                            <input type="text" class="form-control" id="nome" name="nome" required 
                                value="<?php echo htmlspecialchars($projeto['nome']); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="resumo" class="form-label">Resumo</label>
                            <textarea class="form-control" id="resumo" name="resumo" rows="2"><?php echo htmlspecialchars($projeto['resumo']); ?></textarea>
                            <div class="form-text">Um breve resumo do projeto (máximo 200 caracteres)</div>
                        </div>

                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição Detalhada</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="5"><?php echo htmlspecialchars($projeto['descricao']); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="Em planejamento" <?php echo ($projeto['status'] == 'Em planejamento') ? 'selected' : ''; ?>>Em planejamento</option>
                                <option value="Em desenvolvimento" <?php echo ($projeto['status'] == 'Em desenvolvimento') ? 'selected' : ''; ?>>Em desenvolvimento</option>
                                <option value="Em teste" <?php echo ($projeto['status'] == 'Em teste') ? 'selected' : ''; ?>>Em teste</option>
                                <option value="Concluído" <?php echo ($projeto['status'] == 'Concluído') ? 'selected' : ''; ?>>Concluído</option>
                                <option value="Pausado" <?php echo ($projeto['status'] == 'Pausado') ? 'selected' : ''; ?>>Pausado</option>
                            </select>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="text-muted mb-2">
                                    <small>Criado em: <?php echo date('d/m/Y H:i', strtotime($projeto['data_criacao'])); ?></small>
                                </div>
                                <div class="text-muted">
                                    <small>Última atualização: <?php echo date('d/m/Y H:i', strtotime($projeto['data_atualizacao'])); ?></small>
                                </div>
                            </div>
                            <div class="col-md-6 d-flex justify-content-end">
                                <button type="button" class="btn btn-danger me-2" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal">
                                    Excluir Projeto
                                </button>
                                <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                            </div>
                        </div>
                    </form>
                </div>
            </main>
        </div>

        <?php include '../includes/footer.php'; ?>
    </div>

    <!-- Modal de Confirmação de Exclusão -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="confirmDeleteModalLabel">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir o projeto <strong><?php echo htmlspecialchars($projeto['nome']); ?></strong>?</p>
                    <p class="text-danger"><strong>Atenção:</strong> Esta ação não pode ser desfeita e todos os módulos, telas e documentações relacionados serão excluídos permanentemente.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <a href="projetos.php?excluir=<?php echo $projeto_id; ?>" class="btn btn-danger">Confirmar Exclusão</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/sidebar.js"></script>
</body>
</html>
