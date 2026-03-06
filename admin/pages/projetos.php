<?php
require_once '../config/auth.php';
require_once '../config/functions.php';

// Verificar se o usuário está logado
verificaLogin();

// Função para listar todos os projetos
function listarProjetos($conn)
{
    $sql = "SELECT p.*, COUNT(DISTINCT m.id) AS total_modulos 
            FROM projetos p 
            LEFT JOIN modulos m ON p.id = m.projeto_id 
            GROUP BY p.id 
            ORDER BY p.data_atualizacao DESC";
    $result = $conn->query($sql);

    $projetos = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $projetos[] = $row;
        }
    }
    return $projetos;
}

// Excluir projeto
if (isset($_GET['excluir']) && is_numeric($_GET['excluir'])) {
    $id_seq = intval($_GET['excluir']);
    $sql = "DELETE FROM projetos WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_seq);

    if ($stmt->execute()) {
        $_SESSION['mensagem'] = "Projeto excluído com sucesso!";
        $_SESSION['tipo_mensagem'] = "success";
    } else {
        $_SESSION['mensagem'] = "Erro ao excluir projeto: " . $conn->error;
        $_SESSION['tipo_mensagem'] = "danger";
    }

    header("Location: projetos.php");
    exit;
}

$projetos = listarProjetos($conn);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projetos - Sistema de Gerenciamento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            margin-bottom: 20px;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card-header {
            background-color: #87b7a4;
            color: white;
            border-radius: 10px 10px 0 0 !important;
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
        .btn-danger {
            background-color: #c58c6d;
            border-color: #c58c6d;
        }
        .btn-danger:hover {
            background-color: #b47e61;
            border-color: #b47e61;
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
        .projeto-card {
            background-color: #fff6eb;
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
                    <h2>Gerenciamento de Projetos</h2>
                    <a href="projeto_cadastro.php" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i> Novo Projeto
                    </a>
                </div>

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

                <div class="row">
                    <?php if (count($projetos) > 0) : ?>
                        <?php foreach ($projetos as $projeto) : ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card projeto-card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0"><?php echo htmlspecialchars($projeto['nome']); ?></h5>
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
                                        ?> status-badge"><?php echo $projeto['status']; ?></span>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text"><?php echo mb_strimwidth(htmlspecialchars($projeto['resumo']), 0, 100, "..."); ?></p>
                                        <div class="d-flex justify-content-between">
                                            <small class="text-muted">Módulos: <?php echo $projeto['total_modulos']; ?></small>
                                            <small class="text-muted">Atualizado: <?php echo date('d/m/Y', strtotime($projeto['data_atualizacao'])); ?></small>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-transparent border-0">
                                        <div class="d-flex justify-content-between">
                                            <a href="projeto_detalhes.php?id=<?php echo $projeto['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye"></i> Detalhes
                                            </a>
                                            <a href="projeto_editar.php?id=<?php echo $projeto['id']; ?>" class="btn btn-sm btn-success">
                                                <i class="bi bi-pencil"></i> Editar
                                            </a>
                                            <a href="javascript:void(0)" onclick="confirmarExclusao(<?php echo $projeto['id']; ?>)" class="btn btn-sm btn-danger">
                                                <i class="bi bi-trash"></i> Excluir
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                Nenhum projeto cadastrado. <a href="projeto_cadastro.php" class="alert-link">Clique aqui</a> para criar um novo projeto.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>

        <?php include '../includes/footer.php'; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/sidebar.js"></script>
    <script>
        function confirmarExclusao(id) {
            if (confirm("Tem certeza que deseja excluir este projeto? Esta ação não pode ser desfeita.")) {
                window.location.href = "projetos.php?excluir=" + id;
            }
        }
    </script>
</body>
</html>
