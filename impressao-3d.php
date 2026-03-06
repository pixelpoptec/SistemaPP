<?php

session_start();

// Definindo informações da empresa (simulando dados vindos de um banco de dados)
$empresa = [
    'nome' => 'Pixel Pop',
    'slogan' => 'Onde criatividade e tecnologia se encaixam',
    'descricao' => 'Somos uma empresa inovadora que combina expertise em Consultoria de TI, Impressão 3D e Arte e Mìdia para oferecer soluções completas e personalizadas aos seus clientes.',
    'fundacao' => '2025',
    'servicos' => [
        [
            'icones' => 'fa-solid fa-cube',
            'titulo' => 'Impressão 3D sob demanda',
            'descricao' => 'Oferecemos soluções de impressão rápida para criar produtos personalizados quando você mais precisa'
        ],
        [
            'icones' => 'fa-solid fa-note-sticky',
            'titulo' => 'Prototipagem rápida',
            'descricao' => 'Transformamos conceitos em protótipos funcionais com agilidade para acelerar o desenvolvimento de produtos'
        ],
        [
            'icones' => 'fa-solid fa-cubes',
            'titulo' => 'Modelagem 3D',
            'descricao' => 'Criamos modelos digitais detalhados que servem de base para projetos precisos e inovadores'
        ],
        [
            'icones' => 'fa-solid fa-brain',
            'titulo' => 'Consultoria em ideias',
            'descricao' => 'Orientação especializada para implementação de como usar melhor a impressão 3D'
        ]
    ],
    'modelos' => [
        [
            'arquivo' => 'nazgul.jpg',
            'nome' => 'Action Figures',
            'descricao' => 'Personagens que todos amam e conhecem'
        ],
        [
            'arquivo' => 'chaveiro-adam.jpg',
            'nome' => 'Chaveiros & Brindes',
            'descricao' => 'Kit de chaveiros personalizados'
        ],
        [
            'arquivo' => 'decoracao.jpg',
            'nome' => 'Enfeites & Decoração',
            'descricao' => 'Itens personalizados de decoração'
        ],
        [
            'arquivo' => 'nati-pedro.jpg',
            'nome' => 'QR-Codes',
            'descricao' => 'Itens para eventos e festas'
        ],
        [
            'arquivo' => 'caixacorreio.jpg',
            'nome' => 'Decoração & Eventos',
            'descricao' => 'Objeto usado em uma peça de teatro'
        ],
        [
            'arquivo' => 'placa-pix.jpg',
            'nome' => 'Placas PIX',
            'descricao' => 'Placas funcionais e decorativas'
        ]
    ]
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $empresa['nome']; ?> - Impressão 3D de Qualidade</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/buttons.css">
    <link rel="stylesheet" href="css/notifications.css">
    
    <script src="assets/js/notifications.js"></script>
    <!-- Fonte Google -->
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@300;400;700&display=swap" rel="stylesheet">
    <!-- Font Awesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

</head>
<body>
    <!-- Cabeçalho -->
    <?php include 'includes/header.php'; ?>

    <!-- Banner Principal -->
    <section class="hero">
        <div class="container">
            <!--<div class="banner"></div>-->
            <a href="#contato" class="btn">Solicitar Orçamento</a>
            <!--<button class="btn">Clique Aqui</button>
            <a href="#" class="btn">Link como Botão</a>
            <button class="btn btn-large">Botão Grande</button>
            <button class="btn btn-small">Botão Pequeno</button>-->
        </div>
    </section>

    <!-- Nossos Serviços -->
    <section id="servicos" class="servicos">
        <div class="container">
            <h2>Nossos serviços para impressão 3D</h2>
            <div class="servicos-grid">
                <?php foreach ($empresa['servicos'] as $servico) : ?>
                <div class="servico-card">
                    <div class="icon">
                        <i class="<?php echo $servico['icones']; ?>"></i>
                    </div>
                    <h3><?php echo $servico['titulo']; ?></h3>
                    <p><?php echo $servico['descricao']; ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <!-- Galeria de Projetos -->
    <section id="projetos" class="projetos">
        <div class="container">
            <h2>Alguns projetos realizados</h2>
            <div class="galeria">
                <?php foreach ($empresa['modelos'] as $modelo) : ?>
                <div class="projeto-item">
                    <img src="img/<?php echo $modelo['arquivo']; ?>" alt="<?php echo $modelo['nome']; ?>">
                    <div class="overlay">
                        <h3> <?php echo $modelo['nome']; ?></h3>
                        <p> <?php echo $modelo['descricao']; ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <!-- Sobre a Empresa -->
    <section id="sobre" class="sobre">
        <div class="container">
            <h2>Sobre nossa empresa</h2>
            <div class="flex-container">
                <div class="texto">
                    <p><?php echo $empresa['descricao']; ?></p>
                    <p>Nós criamos soluções que vão além das dimensões convencionais. Assim como um cubo que gira em suas mãos, 
                    nossas impressoras 3D moldam ideias em formas tangíveis com precisão milimétrica.</p>
                    <p>Somos apaixonados por tecnologia e inovação, sempre 
                    prontos para enfrentar novos desafios e entregar resultados excepcionais.</p>
                </div>
                <div class="imagem">
                    <!-- Placeholder para imagem da empresa -->
                    <img src="img/nazgul.jpg" alt="Nossa equipe trabalhando">
                </div>
            </div>
        </div>
    </section>

    <!-- Contato -->
    <section id="contato" class="contato">
        <div class="container">
            <h2>Entre em Contato</h2>
            
            <?php if (isset($_SESSION['contato_msg'])) : ?>
                <div class="alert alert-<?php echo $_SESSION['contato_status']; ?>">
                    <?php echo $_SESSION['contato_msg']; ?>
                </div>
                <?php
                // Limpa as mensagens da sessão após exibir
                unset($_SESSION['contato_msg']);
                unset($_SESSION['contato_status']);
                ?>
            <?php endif; ?> 

            <?php if (isset($_SESSION['contato_erros']) && !empty($_SESSION['contato_erros'])) : ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($_SESSION['contato_erros'] as $erro) : ?>
                            <li><?php echo $erro; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php unset($_SESSION['contato_erros']); ?>
            <?php endif; ?>         
            
            <div class="formulario">
                <form action="processar_contato.php" method="post">
                    <input type="hidden" name="form_id" value="IMPRESSAO">
                    <div class="form-group">
                        <label for="nome">Nome</label>
                        <input type="text" id="nome" name="nome" required 
                               value="<?php echo isset($_SESSION['contato_data']['nome']) ? $_SESSION['contato_data']['nome'] : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email" required
                               value="<?php echo isset($_SESSION['contato_data']['email']) ? $_SESSION['contato_data']['email'] : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="telefone">Telefone</label>
                        <input type="tel" id="telefone" name="telefone"
                               value="<?php echo isset($_SESSION['contato_data']['telefone']) ? $_SESSION['contato_data']['telefone'] : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="mensagem">Mensagem</label>
                        <textarea id="mensagem" name="mensagem" rows="5" required><?php echo isset($_SESSION['contato_data']['mensagem']) ? $_SESSION['contato_data']['mensagem'] : ''; ?></textarea>
                    </div>
                    <button type="submit" class="btn">Enviar Mensagem</button>
                </form>
                <?php
                // Limpa os dados do formulário após exibir
                if (isset($_SESSION['contato_data'])) {
                    unset($_SESSION['contato_data']);
                }
                ?>              
            </div>
            <div class="info-contato">
                <h3>Informações de Contato</h3>
                <p><i class="fas fa-map-marker-alt"></i> Atibaia, SP</p>
                <a href="https://wa.me/5511941082828" target="_blank"><p><i class="fab fa-whatsapp"></i> (11) 9.4108-2828</p></a>
                <a href="mailto:contato@pixelpop.com.br" target="_blank"><p><i class="fas fa-envelope"></i> contato@pixelpop.com.br</p></a>
                <a href="https://instagram.com/pixelpop.tec" target="_blank"><p><i class="fab fa-instagram"></i> pixelpop.tec</p></a>
                <!--<a href="https://instagram.com/pixelpop.tec" target="_blank"><p><img src="img/logo-insta.png" alt="Instagram" class="footer-logo">@pixelpop.tec</p></a>-->
            </div>
        </div>
    </section>

    <!-- Rodapé -->
    <?php include 'includes/footer.php'; ?>

    <!-- Script JS (opcional, para funcionalidades adicionais) -->
    <script>
        // Script para menu responsivo
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.querySelector('.menu-toggle');
            const navMenu = document.querySelector('.nav-menu');
            
            if (menuToggle) {
                menuToggle.addEventListener('click', function() {
                    navMenu.classList.toggle('active');
                    menuToggle.classList.toggle('active');
                });
            }
        });
    </script>
</body>
</html>
