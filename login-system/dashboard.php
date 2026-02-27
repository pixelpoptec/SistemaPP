<?php
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    // Redirecionar para a página de login se não estiver logado
    header('Location: index.php');
    exit;
}

// Título da página
$pageTitle = 'Dashboard';
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm" style="background-color: #fff6eb; border-color: #ddbea9;">
                <div class="card-body p-4 text-center">
                    <h2 style="color: #6b705c;">Dashboard</h2>
                    <div class="alert alert-success mt-4" role="alert" style="background-color: #87b7a4; color: #ffffff; border-color: #87b7a4;">
                        <p class="mb-0 fs-4">Foi massa, ficou TOP</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
