<?php
require_once '../../config/auth.php';
require_once '../../config/estudos.php';

// Verificar se o usuário está logado
verificaLogin();

// Verificar se o ID foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['mensagem'] = "ID da nota não fornecido.";
    $_SESSION['tipo_mensagem'] = "danger";
    header("Location: cadernos.php");
    exit;
}

$id_seq = intval($_GET['id']);

// Buscar nota
$nota = buscarNota($conn, $id_seq, $_SESSION['usuario_id']);

// Verificar se a nota existe e pertence ao usuário
if (!$nota) {
    $_SESSION['mensagem'] = "Nota não encontrada ou você não tem permissão para acessá-la.";
    $_SESSION['tipo_mensagem'] = "danger";
    header("Location: cadernos.php");
    exit;
}

// Buscar arquivos da nota
$arquivos = buscarArquivos($conn, $id_seq, $_SESSION['usuario_id']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($nota['titulo']); ?> - Sistema de Acesso</title>
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
                        <li class="breadcrumb-item"><a href="notas.php?caderno_id=<?php echo $nota['caderno_id']; ?>"><?php echo htmlspecialchars($nota['caderno_titulo']); ?></a></li>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($nota['titulo']); ?></li>
                    </ol>
                </nav>

                <?php if (isset($_SESSION['mensagem'])) : ?>
                    <div class="alert alert-<?php echo $_SESSION['tipo_mensagem']; ?>">
                        <?php
                        echo $_SESSION['mensagem'];
                        unset($_SESSION['mensagem']);
                        unset($_SESSION['tipo_mensagem']);
                        ?>
                    </div>
                <?php endif; ?>

                <div class="panel-section">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><?php echo htmlspecialchars($nota['titulo']); ?></h2>
                        <div>
                            <a href="editar_nota.php?id=<?php echo $id_seq; ?>" class="btn btn-warning me-2 no-confirm">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <a href="excluir_nota.php?id=<?php echo $id_seq; ?>" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja excluir esta nota?')">
                                <i class="fas fa-trash"></i> Excluir
                            </a>
                        </div>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted">
                            Criado em: <?php echo date('d/m/Y H:i', strtotime($nota['data_criacao'])); ?> | 
                            Última atualização: <?php echo date('d/m/Y H:i', strtotime($nota['data_atualizacao'])); ?>
                        </small>
                    </div>

                    <div class="content-description mb-4">
                        <?php echo renderizarFormatacao(htmlspecialchars($nota['conteudo'])); ?>
                    </div>

                    <?php if (!empty($arquivos)) : ?>
                        <div class="mt-4">
                            <h4>Arquivos</h4>
                            <ul class="list-group">
                                <?php foreach ($arquivos as $arquivo) : ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><?php echo htmlspecialchars($arquivo['nome']); ?></span>
                                        <div>
                                            <a href="download_arquivo.php?id=<?php echo $arquivo['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="mt-4">
                        <a href="notas.php?caderno_id=<?php echo $nota['caderno_id']; ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Voltar para Notas
                        </a>
                    </div>
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
