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

// Função para obter módulos do projeto
function getModulosProjeto($conn, $projeto_id)
{
    $sql = "SELECT m.*, COUNT(t.id) as total_telas 
            FROM modulos m 
            LEFT JOIN telas t ON m.id = t.modulo_id 
            WHERE m.projeto_id = ? 
            GROUP BY m.id 
            ORDER BY m.data_criacao";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $projeto_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $modulos = [];
    while ($row = $result->fetch_assoc()) {
        $modulos[] = $row;
    }

    return $modulos;
}

// Função para obter arquitetura do projeto
function getArquiteturaProjeto($conn, $projeto_id)
{
    $sql = "SELECT * FROM arquitetura WHERE projeto_id = ? ORDER BY data_criacao DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $projeto_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $arquitetura = [];
    while ($row = $result->fetch_assoc()) {
        $arquitetura[] = $row;
    }

    return $arquitetura;
}

// Função para obter tabelas do banco de dados do projeto
function getTabelasBD($conn, $projeto_id)
{
    $sql = "SELECT * FROM tabelas_bd WHERE projeto_id = ? ORDER BY nome";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $projeto_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $tabelas = [];
    while ($row = $result->fetch_assoc()) {
        $tabelas[] = $row;
    }

    return $tabelas;
}

$projeto = getProjetoDetalhes($conn, $projeto_id);

if (!$projeto) {
    $_SESSION['mensagem'] = "Projeto não encontrado!";
    $_SESSION['tipo_mensagem'] = "danger";
    header("Location: projetos.php");
    exit;
}

$modulos = getModulosProjeto($conn, $projeto_id);
$arquitetura = getArquiteturaProjeto($conn, $projeto_id);
$tabelas = getTabelasBD($conn, $projeto_id);

// Função para obter observações do projeto
function getObservacoesProjeto($conn, $projeto_id)
{
    $sql = "SELECT op.*, u.nome as usuario_nome 
            FROM observacoes_projetos op
            LEFT JOIN usuarios u ON op.usuario_id = u.id
            WHERE op.projeto_id = ? 
            ORDER BY op.data_registro DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $projeto_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $observacoes = [];
    while ($row = $result->fetch_assoc()) {
        $observacoes[] = $row;
    }

    return $observacoes;
}

// Processar o formulário de nova observação de projeto
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['nova_observacao_projeto'])) {
    $observacao = trim($_POST['observacao_projeto']);

    if (!empty($observacao)) {
        $sql = "INSERT INTO observacoes_projetos (projeto_id, observacao, usuario_id) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $usuario_id = $_SESSION['usuario_id'];
        $stmt->bind_param("isi", $projeto_id, $observacao, $usuario_id);

        if ($stmt->execute()) {
            $_SESSION['mensagem'] = "Observação adicionada com sucesso!";
            $_SESSION['tipo_mensagem'] = "success";
            header("Location: projeto_detalhes.php?id=$projeto_id#observacoes");
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

$projeto = getProjetoDetalhes($conn, $projeto_id);
$modulos = getModulosProjeto($conn, $projeto_id);
$arquitetura = getArquiteturaProjeto($conn, $projeto_id);
$tabelas = getTabelasBD($conn, $projeto_id);
$observacoes = getObservacoesProjeto($conn, $projeto_id);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($projeto['nome']); ?> - Sistema de Gerenciamento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .project-header {
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
        .module-card {
            background-color: #f1e3d3;
            border-left: 5px solid #c58c6d;
            margin-bottom: 10px;
            border-radius: 5px;
            transition: transform 0.2s ease;
        }
        .module-card:hover {
            transform: translateX(5px);
        }
        .nav-tabs .nav-link {
            color: #6b705c;
        }
        .nav-tabs .nav-link.active {
            color: #000;
            border-color: #ddbea9 #ddbea9 #fff6eb;
            background-color: #fff6eb;
            font-weight: bold;
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

                <div class="project-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2><i class="bi bi-folder"></i> <?php echo htmlspecialchars($projeto['nome']); ?></h2>
                        <div>
                            <a href="projeto_editar.php?id=<?php echo $projeto_id; ?>" class="btn btn-light btn-sm">
                                <i class="bi bi-pencil"></i> Editar
                            </a>
                            <a href="projetos.php" class="btn btn-light btn-sm ms-2">
                                <i class="bi bi-arrow-left"></i> Voltar
                            </a>
                        </div>
                    </div>
                    <div class="mt-2">
                        <span class="badge bg-<?php
                        switch ($projeto['status']) {
                            case 'Em planejamento':
                                    echo 'secondary';
                                break;
                            case 'Em desenvolvimento':
                                    echo 'primary';
                                break;
                            case 'Em teste':
                                    echo 'warning';
                                break;
                            case 'Concluído':
                                    echo 'success';
                                break;
                            case 'Pausado':
                                    echo 'danger';
                                break;
                            default:
                                    echo 'info';
                        }
                        ?>"><?php echo $projeto['status']; ?></span>
                        <span class="ms-3 text-light">Criado em: <?php echo date('d/m/Y', strtotime($projeto['data_criacao'])); ?></span>
                        <span class="ms-3 text-light">Última atualização: <?php echo date('d/m/Y H:i', strtotime($projeto['data_atualizacao'])); ?></span>
                    </div>
                </div>

                <ul class="nav nav-tabs mb-4" id="projectTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab" aria-controls="info" aria-selected="true">
                            <i class="bi bi-info-circle"></i> Informações
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="modulos-tab" data-bs-toggle="tab" data-bs-target="#modulos" type="button" role="tab" aria-controls="modulos" aria-selected="false">
                            <i class="bi bi-grid-3x3-gap"></i> Módulos
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="arquitetura-tab" data-bs-toggle="tab" data-bs-target="#arquitetura" type="button" role="tab" aria-controls="arquitetura" aria-selected="false">
                            <i class="bi bi-diagram-3"></i> Arquitetura
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tabelas-tab" data-bs-toggle="tab" data-bs-target="#tabelas" type="button" role="tab" aria-controls="tabelas" aria-selected="false">
                            <i class="bi bi-table"></i> Tabelas BD
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="observacoes-tab" data-bs-toggle="tab" data-bs-target="#observacoes" type="button" role="tab" aria-controls="observacoes" aria-selected="false">
                            <i class="bi bi-chat-left-text"></i> Observações
                        </button>
                    </li>                   
                </ul>

                <div class="tab-content" id="projectTabContent">
                    <!-- Aba de Informações --><div class="tab-pane fade show active" id="info" role="tabpanel" aria-labelledby="info-tab">
                        <div class="section-card">
                            <div class="section-header">
                                <h3 class="mb-0">Resumo do Projeto</h3>
                            </div>
                            <div class="section-body">
                                <p><?php echo nl2br(htmlspecialchars($projeto['resumo'])); ?></p>
                            </div>
                        </div>

                        <div class="section-card">
                            <div class="section-header">
                                <h3 class="mb-0">Descrição Detalhada</h3>
                            </div>
                            <div class="section-body">
                                <p><?php echo nl2br(htmlspecialchars($projeto['descricao'])); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Aba de Módulos -->
                    <div class="tab-pane fade" id="modulos" role="tabpanel" aria-labelledby="modulos-tab">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3>Módulos do Projeto</h3>
                            <a href="modulo_cadastro.php?projeto_id=<?php echo $projeto_id; ?>" class="btn btn-primary">
                                <i class="bi bi-plus-lg"></i> Novo Módulo
                            </a>
                        </div>

                        <?php if (count($modulos) > 0) : ?>
                            <?php foreach ($modulos as $modulo) : ?>
                                <div class="module-card p-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h4><?php echo htmlspecialchars($modulo['nome']); ?></h4>
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
                                    </div>
                                    <p class="mb-2"><?php echo mb_strimwidth(htmlspecialchars($modulo['descricao']), 0, 150, "..."); ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span><i class="bi bi-window"></i> Telas: <?php echo $modulo['total_telas']; ?></span>
                                        <div>
                                            <a href="modulo_detalhes.php?id=<?php echo $modulo['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye"></i> Detalhes
                                            </a>
                                            <a href="modulo_editar.php?id=<?php echo $modulo['id']; ?>" class="btn btn-sm btn-success">
                                                <i class="bi bi-pencil"></i> Editar
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <div class="alert alert-info">
                                Nenhum módulo cadastrado para este projeto. 
                                <a href="modulo_cadastro.php?projeto_id=<?php echo $projeto_id; ?>" class="alert-link">Clique aqui</a> para adicionar um módulo.
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Aba de Arquitetura -->
                    <div class="tab-pane fade" id="arquitetura" role="tabpanel" aria-labelledby="arquitetura-tab">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3>Arquitetura do Projeto</h3>
                            <a href="arquitetura_cadastro.php?projeto_id=<?php echo $projeto_id; ?>" class="btn btn-primary">
                                <i class="bi bi-plus-lg"></i> Nova Documentação
                            </a>
                        </div>

                        <?php if (count($arquitetura) > 0) : ?>
                            <div class="accordion" id="accordionArquitetura">
                                <?php foreach ($arquitetura as $index => $arq) : ?>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading<?php echo $index; ?>">
                                            <button class="accordion-button <?php echo $index > 0 ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $index; ?>" aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>" aria-controls="collapse<?php echo $index; ?>">
                                                <?php echo htmlspecialchars($arq['titulo']); ?>
                                            </button>
                                        </h2>
                                        <div id="collapse<?php echo $index; ?>" class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" aria-labelledby="heading<?php echo $index; ?>" data-bs-parent="#accordionArquitetura">
                                            <div class="accordion-body">
                                                <p><?php echo nl2br(htmlspecialchars($arq['descricao'])); ?></p>
                                                <?php if (!empty($arq['diagrama_url'])) : ?>
                                                    <div class="text-center my-3">
                                                        <img src="<?php echo htmlspecialchars($arq['diagrama_url']); ?>" alt="Diagrama de Arquitetura" class="img-fluid border rounded">
                                                    </div>
                                                <?php endif; ?>
                                                <div class="d-flex justify-content-end">
                                                    <a href="arquitetura_editar.php?id=<?php echo $arq['id']; ?>" class="btn btn-sm btn-success">
                                                        <i class="bi bi-pencil"></i> Editar
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else : ?>
                            <div class="alert alert-info">
                                Nenhuma documentação de arquitetura cadastrada para este projeto. 
                                <a href="arquitetura_cadastro.php?projeto_id=<?php echo $projeto_id; ?>" class="alert-link">Clique aqui</a> para adicionar.
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Aba de Tabelas do BD -->
                    <div class="tab-pane fade" id="tabelas" role="tabpanel" aria-labelledby="tabelas-tab">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3>Tabelas do Banco de Dados</h3>
                            <a href="tabela_cadastro.php?projeto_id=<?php echo $projeto_id; ?>" class="btn btn-primary">
                                <i class="bi bi-plus-lg"></i> Nova Tabela
                            </a>
                        </div>

                        <?php if (count($tabelas) > 0) : ?>
                            <div class="accordion" id="accordionTabelas">
                                <?php foreach ($tabelas as $index => $tabela) : ?>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingTabela<?php echo $index; ?>">
                                            <button class="accordion-button <?php echo $index > 0 ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTabela<?php echo $index; ?>" aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>" aria-controls="collapseTabela<?php echo $index; ?>">
                                                <i class="bi bi-table me-2"></i> <?php echo htmlspecialchars($tabela['nome']); ?>
                                            </button>
                                        </h2>
                                        <div id="collapseTabela<?php echo $index; ?>" class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" aria-labelledby="headingTabela<?php echo $index; ?>" data-bs-parent="#accordionTabelas">
                                            <div class="accordion-body">
                                                <h5>Descrição</h5>
                                                <p><?php echo nl2br(htmlspecialchars($tabela['descricao'])); ?></p>

                                                <h5>Código SQL</h5>
                                                <pre class="bg-dark text-light p-3 rounded"><code><?php echo htmlspecialchars($tabela['codigo_sql']); ?></code></pre>

                                                <div class="d-flex justify-content-end mt-3">
                                                    <a href="tabela_editar.php?id=<?php echo $tabela['id']; ?>" class="btn btn-sm btn-success">
                                                        <i class="bi bi-pencil"></i> Editar
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else : ?>
                            <div class="alert alert-info">
                                Nenhuma tabela de banco de dados cadastrada para este projeto. 
                                <a href="tabela_cadastro.php?projeto_id=<?php echo $projeto_id; ?>" class="alert-link">Clique aqui</a> para adicionar.
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Aba de Observações -->
                    <div class="tab-pane fade" id="observacoes" role="tabpanel" aria-labelledby="observacoes-tab">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="section-card">
                                    <div class="section-header">
                                        <h3 class="mb-0">Adicionar Nova Observação</h3>
                                    </div>
                                    <div class="section-body">
                                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $projeto_id); ?>" class="mb-4">
                                            <div class="mb-3">
                                                <label for="observacao_projeto" class="form-label">Observação</label>
                                                <textarea class="form-control" id="observacao_projeto" name="observacao_projeto" rows="4" required></textarea>
                                                <div class="form-text">Adicione observações gerais sobre o projeto, como decisões importantes, mudanças de escopo, etc.</div>
                                            </div>
                                            <div class="d-grid">
                                                <button type="submit" name="nova_observacao_projeto" class="btn btn-primary">Adicionar Observação</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="section-card">
                                    <div class="section-header">
                                        <h3 class="mb-0">Histórico de Observações</h3>
                                    </div>
                                    <div class="section-body" style="max-height: 500px; overflow-y: auto;">
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
                                            <div class="alert alert-info mb-0">
                                                Nenhuma observação registrada para este projeto.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
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
