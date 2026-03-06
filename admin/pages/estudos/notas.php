<?php
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

// Buscar notas do caderno
$notas = buscarNotas($conn, $caderno_id, $_SESSION['usuario_id']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notas - <?php echo htmlspecialchars($caderno['titulo']); ?> - Sistema de Acesso</title>
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
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($caderno['titulo']); ?></li>
                    </ol>
                </nav>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Notas - <?php echo htmlspecialchars($caderno['titulo']); ?></h2>
                    <a href="nova_nota.php?caderno_id=<?php echo $caderno_id; ?>" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nova Nota
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

                <?php if (empty($notas)) : ?>
                    <div class="alert alert-info">
                        <p>Este caderno ainda não possui notas. Clique em "Nova Nota" para começar.</p>
                    </div>
                <?php else : ?>
                    <div class="accordion" id="accordionNotas">
                        <?php foreach ($notas as $index => $nota) : ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading<?php echo $nota['id']; ?>">
                                    <button class="accordion-button <?php echo ($index > 0) ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $nota['id']; ?>" aria-expanded="<?php echo ($index === 0) ? 'true' : 'false'; ?>" aria-controls="collapse<?php echo $nota['id']; ?>">
                                        <?php echo htmlspecialchars($nota['titulo']) . " (" . date('d/m/Y H:i', strtotime($nota['data_atualizacao'])) . ")";?>
                                        

                                        
                                    </button>
                                </h2>
                                <div id="collapse<?php echo $nota['id']; ?>" class="accordion-collapse collapse <?php echo ($index === 0) ? 'show' : ''; ?>" aria-labelledby="heading<?php echo $nota['id']; ?>" data-bs-parent="#accordionNotas">
                                    <div class="accordion-body">
                                        <div class="content-description mb-3">
                                            <?php echo renderizarFormatacao(htmlspecialchars($nota['conteudo'])); ?>
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            <a href="visualizar_nota.php?id=<?php echo $nota['id']; ?>" class="btn btn-sm btn-info me-2">
                                                <i class="fas fa-eye"></i> Ver Completo
                                            </a>
                                            <a href="editar_nota.php?id=<?php echo $nota['id']; ?>" class="btn btn-sm btn-warning me-2 no-confirm">
                                                <i class="fas fa-edit"></i> Editar
                                            </a>
                                            <a href="excluir_nota.php?id=<?php echo $nota['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir esta nota?')">
                                                <i class="fas fa-trash"></i> Excluir
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>

        <?php include '../../includes/footer.php'; ?>
    </div>

    <script src="../../assets/js/sidebar.js"></script>
    <script src="../../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
