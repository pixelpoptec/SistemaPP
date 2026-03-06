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

// Função para obter telas do módulo
function getTelasModulo($conn, $modulo_id)
{
    $sql = "SELECT * FROM telas WHERE modulo_id = ? ORDER BY nome";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $modulo_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $telas = [];
    while ($row = $result->fetch_assoc()) {
        $telas[] = $row;
    }

    return $telas;
}

// Função para obter observações do módulo
function getObservacoesModulo($conn, $modulo_id)
{
    $sql = "SELECT om.*, u.nome as usuario_nome 
            FROM observacoes_modulos om
            LEFT JOIN usuarios u ON om.usuario_id = u.id
            WHERE om.modulo_id = ? 
            ORDER BY om.data_registro DESC";
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

$modulo = getModuloDetalhes($conn, $modulo_id);

if (!$modulo) {
    $_SESSION['mensagem'] = "Módulo não encontrado!";
    $_SESSION['tipo_mensagem'] = "danger";
    header("Location: projetos.php");
    exit;
}

$telas = getTelasModulo($conn, $modulo_id);
$observacoes = getObservacoesModulo($conn, $modulo_id);

// Processar o formulário de nova observação
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['nova_observacao'])) {
    $observacao = trim($_POST['observacao']);

    if (!empty($observacao)) {
        $sql = "INSERT INTO observacoes_modulos (modulo_id, observacao, usuario_id) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $usuario_id = $_SESSION['usuario_id'];
        $stmt->bind_param("isi", $modulo_id, $observacao, $usuario_id);

        if ($stmt->execute()) {
            $_SESSION['mensagem'] = "Observação adicionada com sucesso!";
            $_SESSION['tipo_mensagem'] = "success";
            header("Location: modulo_detalhes.php?id=$modulo_id");
            exit;
        } else {
            $_SESSION['mensagem'] = "Erro ao adicionar observação: " . $conn->error;
            $_SESSION['tipo_mensagem'] = "danger";
        }
    } else {
        $_SESSION['mensagem'] = "A observação não pode estar vazia!";
        $_SESSION['tipo_mensagem'] = "warning";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($modulo['nome']); ?> - Sistema de Gerenciamento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .module-header {
            background-color: #87b7a4;
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .section-card {
            background-color: #fff6eb;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            padding: 0;
        }
        .section-header {
            background-color: #ddbea9;
            color: #000;
            padding: 15px;
            border-radius: 10px 10px 0 0;
        }
        .section-body {
            padding: 15px;
        }
        .btn-primary {
            background-color: #6b705c;
            border-color: #6b705c;
        }
        .btn-primary:hover {
            background-color: #5a5f4d;
            border-color: #5a5f4d;
        }
        .btn-success {
            background-color: #87b7a4;
            border-color: #87b7a4;
        }
        .btn-success:hover {
            background-color: #76a693;
            border-color: #76a693;
        }
        .tela-card {
            background-color: #f1e3d3;
            border-left: 5px solid #c58c6d;
            margin-bottom: 10px;
            border-radius: 5px;
            transition: transform 0.2s ease;
        }
        .tela-card:hover {
            transform: translateX(5px);
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
                <?php if (isset($_SESSION['mensagem'])) : ?>
                <div class="alert alert-<?php echo $_SESSION['tipo_mensagem']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['mensagem']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                    <?php
                    unset($_SESSION['mensagem']);
                    unset($_SESSION['tipo_mensagem']);
                endif;
                ?>

                <div class="module-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2><i class="bi bi-grid-3x3-gap"></i> <?php echo htmlspecialchars($modulo['nome']); ?></h2>
                        <div>
                            <a href="modulo_editar.php?id=<?php echo $modulo_id; ?>" class="btn btn-light btn-sm">
                                <i class="bi bi-pencil"></i> Editar
                            </a>
                            <a href="projeto_detalhes.php?id=<?php echo $modulo['projeto_id']; ?>" class="btn btn-light btn-sm ms-2">
                                <i class="bi bi-arrow-left"></i> Voltar ao Projeto
                            </a>
                        </div>
                    </div>
                    <div class="mt-2">
                        <span class="badge bg-<?php
                        switch ($modulo['status']) {
                            case 'Pendente':
                                    echo 'secondary';
                                break;
                            case 'Em desenvolvimento':
                                    echo 'primary';
                                break;
                            case 'Concluído':
                                    echo 'success';
                                break;
                            default:
                                    echo 'info';
                        }
                        ?>"><?php echo $modulo['status']; ?></span>
                        <span class="ms-3 text-light">Projeto: <?php echo htmlspecialchars($modulo['projeto_nome']); ?></span>
                        <span class="ms-3 text-light">Criado em: <?php echo date('d/m/Y', strtotime($modulo['data_criacao'])); ?></span>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-7">
                        <div class="section-card">
                            <div class="section-header">
                                <h3 class="mb-0">Descrição do Módulo</h3>
                            </div>
                            <div class="section-body">
                                <p><?php echo nl2br(htmlspecialchars($modulo['descricao'])); ?></p>
                            </div>
                        </div>

                        <div class="section-card">
                            <div class="section-header d-flex justify-content-between align-items-center">
                                <h3 class="mb-0">Telas do Módulo</h3>
                                <a href="tela_cadastro.php?modulo_id=<?php echo $modulo_id; ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-lg"></i> Nova Tela
                                </a>
                            </div>
                            <div class="section-body">
                                <?php if (count($telas) > 0) : ?>
                                    <?php foreach ($telas as $tela) : ?>
                                        <div class="tela-card p-3 mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h5><?php echo htmlspecialchars($tela['nome']); ?></h5>
                                                <div>
                                                    <a href="tela_editar.php?id=<?php echo $tela['id']; ?>" class="btn btn-sm btn-success">
                                                        <i class="bi bi-pencil"></i> Editar
                                                    </a>
                                                </div>
                                            </div>
                                            <p class="mb-0"><?php echo mb_strimwidth(htmlspecialchars($tela['descricao']), 0, 100, "..."); ?></p>
                                            <?php if (!empty($tela['mockup_url'])) : ?>
                                                <div class="mt-2">
                                                    <a href="<?php echo htmlspecialchars($tela['mockup_url']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                                        <i class="bi bi-image"></i> Ver Mockup
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <div class="alert alert-info mb-0">
                                        Nenhuma tela cadastrada para este módulo. 
                                        <a href="tela_cadastro.php?modulo_id=<?php echo $modulo_id; ?>" class="alert-link">Clique aqui</a> para adicionar.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <div class="section-card">
                            <div class="section-header">
                                <h3 class="mb-0">Observações</h3>
                            </div>
                            <div class="section-body">
                                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $modulo_id); ?>" class="mb-4">
                                    <div class="mb-3">
                                        <label for="observacao" class="form-label">Nova Observação</label>
                                        <textarea class="form-control" id="observacao" name="observacao" rows="3" required></textarea>
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" name="nova_observacao" class="btn btn-primary">Adicionar Observação</button>
                                    </div>
                                </form>

                                <hr>

                                <h5>Histórico de Observações</h5>
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
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>

        <?php include '../includes/footer.php'; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/sidebar.js"></script>
</body>
</html>
