<?php
ob_start();
require_once '../../config/auth.php';
require_once '../../config/estudos.php';

// Verificar se o usuário está logado
verificaLogin();

// Processar formulário
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $titulo = trim($_POST['titulo']);

    // Validação
    if (empty($titulo)) {
        $_SESSION['mensagem'] = "O título do caderno é obrigatório.";
        $_SESSION['tipo_mensagem'] = "danger";
    } else {
        // Criar caderno
        $resultado = criarCaderno($conn, $titulo, $_SESSION['usuario_id']);

        if ($resultado) {
            $_SESSION['mensagem'] = "Caderno criado com sucesso!";
            $_SESSION['tipo_mensagem'] = "success";
            header("Location: cadernos.php");
            exit;
        } else {
            $_SESSION['mensagem'] = "Erro ao criar caderno. Tente novamente.";
            $_SESSION['tipo_mensagem'] = "danger";
        }
    }
}
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Caderno - Sistema de Acesso</title>
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
                        <li class="breadcrumb-item active">Novo Caderno</li>
                    </ol>
                </nav>

                <h2>Novo Caderno</h2>

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
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="mb-3">
                            <label for="titulo" class="form-label">Título do Caderno</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" required>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="cadernos.php" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Criar Caderno</button>
                        </div>
                    </form>
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
