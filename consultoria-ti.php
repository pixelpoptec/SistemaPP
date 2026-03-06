<?php

session_start();

// Definindo informações da empresa (simulando dados vindos de um banco de dados)
$empresa = [
    'nome' => 'Pixel Pop',
    'slogan' => 'Onde criatividade e tecnologia se encaixam',
    'descricao' => 'Somos uma empresa inovadora que combina expertise em Consultoria de TI, Impressão 3D e Arte e Mìdia para oferecer soluções completas e personalizadas aos seus clientes.',
    'fundacao' => '2025',
    'consultoria' => [
        [
            'icones' => 'fas fa-laptop',
            'titulo' => 'Soluções de TI personalizadas',
            'descricao' => 'Desenvolvemos estratégias de TI adaptadas às necessidades específicas do seu negócio para maximizar eficiência e inovação'
        ],
        [
            'icones' => 'fab fa-digital-ocean',
            'titulo' => 'Consultoria em transformação digital',
            'descricao' => 'Guiamos sua organização na jornada de transformação digital para abraçar novas tecnologias e impulsionar o crescimento'
        ],
        [
            'icones' => 'fas fa-bars',
            'titulo' => 'Serviços de gestão de TI',
            'descricao' => 'Oferecemos gestão integrada de infraestruturas e operações de TI para otimizar o desempenho e garantir estabilidade.'
        ],
        [
            'icones' => 'fas fa-user',
            'titulo' => 'Manutenção e Suporte',
            'descricao' => 'Proporcionamos suporte contínuo e proativo para garantir operação tranquila e resolução rápida de problemas técnicos'
        ]
    ],
    'modelos' => [
        [
            'arquivo' => 'juliao_logo_v2.png',
            'nome' => 'Consultoria de TI, Arte e Mídia',
            'descricao' => 'Desenvolvimento dos projetos'
        ],
        [
            'arquivo' => 'suzano_logo_v2.png',
            'nome' => 'Chaveiros & Brindes',
            'descricao' => 'Desenvolvimento de sistemas'
        ],
        [
            'arquivo' => 'ieca_logo_v2.png',
            'nome' => 'Arte & Midia',
            'descricao' => 'Comunicação'
        ],
        [
            'arquivo' => 'prodesp_logo_v2.png',
            'nome' => 'QR-Codes',
            'descricao' => 'Desenvolvimento de sistema'
        ],
        [
            'arquivo' => 'newcon_logo_v2.png',
            'nome' => 'Consultoria de TI',
            'descricao' => 'Gestão de suporte'
        ],
        [
            'arquivo' => 'negocio_logo_v2.png',
            'nome' => 'O próximo...',
            'descricao' => 'Vem com a gente!'
        ]
    ]
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $empresa['nome']; ?> - Consultoria de TI</title>
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
            <a href="#contato" class="btn">Solicitar Orçamento</a>
        </div>
    </section>

    <!-- Nossos Serviços -->
    <section id="servicos" class="servicos">
        <div class="container">
            <h2>O que faz uma consultoria de TI?</h2>
            <div class="servicos-grid">
                <?php foreach ($empresa['consultoria'] as $consultoria) : ?>
                <div class="servico-card">
                    <div class="icon">
                        <i class="<?php echo $consultoria['icones']; ?>"></i>
                    </div>
                    <h3><?php echo $consultoria['titulo']; ?></h3>
                    <p><?php echo $consultoria['descricao']; ?></p>
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
                    <p>Desenvolvemos soluções abrangentes e práticas que atuam como suporte estratégico 
                    para nossos clientes nas áreas de TI, software, redes e internet.</p>
                    <p>Somos apaixonados por tecnologia e inovação, sempre 
                    prontos para enfrentar novos desafios (como a IA) e entregar resultados excepcionais.</p>
                </div>
                <div class="imagem">
                    <!-- Placeholder para imagem da empresa -->
                    <img src="img/ti_info_v2.png" alt="Nossa equipe trabalhando">
                </div>
            </div>
        </div>
    </section>

    <!-- Contato -->
    <section id="contato" class="contato">
        <div class="container">
            <h2>Entre em contato</h2>
            
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
                    <input type="hidden" name="form_id" value="CONSULTORIA">
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
                <a href="https://wa.me/5511941082828" target="_blank"><p><i class="fas fa-phone"></i> (11) 9.4108-2828</p></a>
                <a href="mailto:contato@pixelpop.com.br" target="_blank"><p><i class="fas fa-envelope"></i> contato@pixelpop.com.br</p></a>
                <a href="https://instagram.com/pixelpop.tec" target="_blank"><p><i class="fab fa-instagram"></i>pixelpop.tec</p></a>
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
