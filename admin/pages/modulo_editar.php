<?php
require_once '../config/auth.php';
require_once '../config/functions.php';

// Verificar se o usuário está logado
verificaLogin();

// Verificar se o ID do módulo foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['mensagem'] = "ID do módulo inválido!";
    $_SESSION['tipo_mensagem'] = "danger";
    header("Location: projetos.php");
    exit;
}

$modulo_id = intval($_GET['id']);

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
    $status = trim($_POST['status']);
    $nova_observacao = trim($_POST['nova_observacao']);

    // Validação básica
    $erros = [];

    if (empty($nome)) {
        $erros[] = "O nome do módulo é obrigatório";
    }

    if (empty($erros)) {
        // Iniciar transação
        $conn->begin_transaction();

        try {
            // Atualizar o módulo
            $sql = "UPDATE modulos SET nome = ?, descricao = ?, status = ?, data_atualizacao = NOW() WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $nome, $descricao, $status, $modulo_id);
            $stmt->execute();

            // Se houver nova observação, inserir na tabela de observações
            if (!empty($nova_observacao)) {
                $sql = "INSERT INTO observacoes_modulos (modulo_id, observacao, usuario_id) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $usuario_id = $_SESSION['usuario_id'];
                $stmt->bind_param("isi", $modulo_id, $nova_observacao, $usuario_id);
                $stmt->execute();
            }

            // Commit da transação
            $conn->commit();

            $_SESSION['mensagem'] = "Módulo atualizado com sucesso!";
            $_SESSION['tipo_mensagem'] = "success";
            header("Location: modulo_detalhes.php?id=$modulo_id");
            exit;
        } catch (Exception $e) {
            // Rollback em caso de erro
            $conn->rollback();
            $erros[] = "Erro ao atualizar o módulo: " . $e->getMessage();
        }
    }
}

// Função para obter observações do módulo
function getObservacoesModulo($conn, $modulo_id)
{
    $sql = "SELECT om.*, u.nome as usuario_nome 
            FROM observacoes_modulos om
            LEFT JOIN usuarios u ON om.usuario_id = u.id
            WHERE om.modulo_id = ? 
            ORDER BY om.data_registro DESC
            LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $modulo_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $observacoes = [];
    while ($row = $result->fetch_assoc()) {
        $observacoes[] = $row;
    }

    return $observacoes;
}

$observacoes = getObservacoesModulo($conn, $modulo_id);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Módulo - Sistema de Gerenciamento</title>
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
        .projeto-info {
            background-color: #f1e3d3;
            border-left: 5px solid #c58c6d;
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .observacao-item {
            border-left: 3px solid #87b7a4;
            padding-left: 15px;
            margin-bottom: 15px;
        }
        .observacao-data {
            color: #6b705c;
            font-size: 0.85rem;
        }
        .observacao-usuario {
            font-weight: bold;
            color: #c58c6d;
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
                    <h2>Editar Módulo</h2>
                    <a href="modulo_detalhes.php?id=<?php echo $modulo_id; ?>" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Voltar
                    </a>
                </div>

                <div class="projeto-info">
                    <h5>Projeto: <?php echo htmlspecialchars($modulo['projeto_nome']); ?></h5>
                    <p class="mb-0">Módulo: <?php echo htmlspecialchars($modulo['nome']); ?></p>
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

                <div class="row">
                    <div class="col-lg-8">
                        <div class="form-container">
                            <div class="form-header">
                                <h3 class="mb-0"><i class="bi bi-pencil-square"></i> Editar Módulo</h3>
                            </div>

                            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $modulo_id); ?>">
                                <div class="mb-3">
                                    <label for="nome" class="form-label">Nome do Módulo *</label>
                                    <input type="text" class="form-control" id="nome" name="nome" required 
                                        value="<?php echo htmlspecialchars($modulo['nome']); ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="descricao" class="form-label">Descrição do Módulo</label>
                                    <textarea class="form-control" id="descricao" name="descricao" rows="4"><?php echo htmlspecialchars($modulo['descricao']); ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="Pendente" <?php echo ($modulo['status'] == 'Pendente') ? 'selected' : ''; ?>>Pendente</option>
                                        <option value="Em desenvolvimento" <?php echo ($modulo['status'] == 'Em desenvolvimento') ? 'selected' : ''; ?>>Em desenvolvimento</option>
                                        <option value="Concluído" <?php echo ($modulo['status'] == 'Concluído') ? 'selected' : ''; ?>>Concluído</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="nova_observacao" class="form-label">Nova Observação</label>
                                    <textarea class="form-control" id="nova_observacao" name="nova_observacao" rows="3"></textarea>
                                    <div class="form-text">Adicione uma nova observação sobre este módulo (opcional)</div>
                                </div>

                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <div class="text-muted mb-2">
                                            <small>Criado em: <?php echo date('d/m/Y H:i', strtotime($modulo['data_criacao'])); ?></small>
                                        </div>
                                        <div class="text-muted">
                                            <small>Última atualização: <?php echo date('d/m/Y H:i', strtotime($modulo['data_atualizacao'])); ?></small>
                                        </div>
                                    </div>
                                    <div class="col-md-6 d-flex justify-content-end">
                                        <button type="button" class="btn btn-danger me-2" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal">
                                            Excluir Módulo
                                        </button>
                                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="form-container">
                            <div class="form-header">
                                <h3 class="mb-0"><i class="bi bi-clock-history"></i> Últimas Observações</h3>
                            </div>

                            <div class="p-2">
                                <?php if (count($observacoes) > 0) : ?>
                                    <?php foreach ($observacoes as $obs) : ?>
                                        <div class="observacao-item">
                                            <p class="mb-1"><?php echo nl2br(htmlspecialchars($obs['observacao'])); ?></p>
                                            <div class="d-flex justify-content-between">
                                                <span class="observacao-usuario"><?php echo htmlspecialchars($obs['usuario_nome'] ?? 'Sistema'); ?></span>
                                                <span class="observacao-data"><?php echo date('d/m/Y H:i', strtotime($obs['data_registro'])); ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <p class="text-muted">Nenhuma observação registrada.</p>
                                <?php endif; ?>

                                <div class="text-center mt-3">
                                    <a href="modulo_detalhes.php?id=<?php echo $modulo_id; ?>" class="btn btn-sm btn-outline-secondary">
                                        Ver todas as observações
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
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
                    <p>Tem certeza que deseja excluir o módulo <strong><?php echo htmlspecialchars($modulo['nome']); ?></strong>?</p>
                    <p class="text-danger"><strong>Atenção:</strong> Esta ação não pode ser desfeita e todas as telas e observações relacionadas serão excluídas permanentemente.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <a href="modulo_excluir.php?id=<?php echo $modulo_id; ?>&projeto_id=<?php echo $modulo['projeto_id']; ?>" class="btn btn-danger">Confirmar Exclusão</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/sidebar.js"></script>
</body>
</html>
