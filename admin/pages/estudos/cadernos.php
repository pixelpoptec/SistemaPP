<?php
require_once '../../config/auth.php';
require_once '../../config/estudos.php';

// Verificar se o usuário está logado
// Teste para branch de homologação
verificaLogin();

// Buscar todos os cadernos do usuário
$cadernos = buscarCadernos($conn, $_SESSION['usuario_id']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Estudos - Sistema de Acesso</title>
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Gerenciamento de Estudos</h2>
                    <a href="novo_caderno.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Novo Caderno
                    </a>
                </div>

                <?php if (isset($_SESSION['mensagem'])) : ?>
                    <div class="alert alert-<?php echo $_SESSION['tipo_mensagem']; ?>">
                        <?php
                        echo $_SESSION['mensagem'];
                        unset($_SESSION['mensagem']);
                        unset($_SESSION['tipo_mensagem']);
                        ?>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <?php if (empty($cadernos)) : ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                <p>Você ainda não possui cadernos. Clique em "Novo Caderno" para começar.</p>
                            </div>
                        </div>
                    <?php else : ?>
                        <?php foreach ($cadernos as $caderno) : ?>
                            <div class="col-md-4 mb-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($caderno['titulo']); ?></h5>
                                        <p class="card-text text-muted">
                                            <small>Criado em: <?php echo date('d/m/Y H:i', strtotime($caderno['data_criacao'])); ?></small>
                                        </p>
                                    </div>
                                    <div class="card-footer d-flex justify-content-between">
                                        <a href="notas.php?caderno_id=<?php echo $caderno['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-book-open"></i> Ver Notas
                                        </a>
                                        <div>
                                            <a href="editar_caderno.php?id=<?php echo $caderno['id']; ?>" class="btn btn-sm btn-warning no-confirm">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="excluir_caderno.php?id=<?php echo $caderno['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir este caderno? Todas as notas serão excluídas.')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
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
