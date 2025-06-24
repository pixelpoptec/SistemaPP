-- Criação do banco de dados
CREATE DATABASE IF NOT EXISTS jaimeg36_pixelpop;
USE jaimeg36_pixelpop;

-- Tabela de usuários
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    ativo BOOLEAN DEFAULT TRUE,
    ultimo_acesso DATETIME NULL
);

-- Tabela de grupos de acesso
CREATE TABLE grupos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    descricao VARCHAR(255)
);

-- Tabela de relação entre usuários e grupos
CREATE TABLE usuario_grupo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    grupo_id INT NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE,
    UNIQUE (usuario_id, grupo_id)
);

-- Tabela de permissões
CREATE TABLE permissoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL UNIQUE,
    descricao VARCHAR(255)
);

-- Tabela de relação entre grupos e permissões
CREATE TABLE grupo_permissao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grupo_id INT NOT NULL,
    permissao_id INT NOT NULL,
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE,
    FOREIGN KEY (permissao_id) REFERENCES permissoes(id) ON DELETE CASCADE,
    UNIQUE (grupo_id, permissao_id)
);

-- Tabela de logs de acesso
CREATE TABLE logs_acesso (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    data_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip VARCHAR(45),
    acao VARCHAR(50),
    detalhes TEXT,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- Tabela de Clientes
CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    telefone VARCHAR(20),
    empresa VARCHAR(100),
    endereco TEXT,
    observacoes TEXT,
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP,
    usuario_id INT,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Tabela de Tarefas
CREATE TABLE tarefas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    detalhes TEXT,
    status ENUM('aberta', 'fazendo', 'esperando', 'concluido') DEFAULT 'aberta',
    data_abertura DATETIME DEFAULT CURRENT_TIMESTAMP,
    previsao_termino DATE,
    termino_efetivo DATETIME NULL,
    tempo_horas INT DEFAULT 0,
    tempo_minutos INT DEFAULT 0,
    prioridade ENUM('baixa', 'media', 'alta', 'urgente') DEFAULT 'media',
    cliente_id INT,
    usuario_id INT,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

----------------------------------------------
---- SISTEMA DE PRECIFICAÇÃO -----------------
----------------------------------------------
-- Tabela para configurações gerais
CREATE TABLE configuracoes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    custo_fixo_mensal DECIMAL(10,2) NOT NULL,
    dias_mes INT NOT NULL,
    horas_dia INT NOT NULL,
    perc_uso_impressora DECIMAL(5,2) NOT NULL,
    markup DECIMAL(5,2) NOT NULL,
    perc_falhas DECIMAL(5,2) NOT NULL,
    imposto DECIMAL(5,2) NOT NULL,
    tx_cartao DECIMAL(5,2) NOT NULL,
    custo_anuncio DECIMAL(5,2) NOT NULL
);

-- Tabela para materiais
CREATE TABLE materiais (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(50) NOT NULL,
    custo_kg DECIMAL(10,2) NOT NULL
);

-- Tabela para impressoras
CREATE TABLE impressoras (
    id INT PRIMARY KEY AUTO_INCREMENT,
    modelo VARCHAR(50) NOT NULL,
    potencia_w INT NOT NULL,
    custo_kw_h DECIMAL(10,2) NOT NULL,
    valor_maquina DECIMAL(10,2) NOT NULL,
    vida_util_horas INT NOT NULL
);

-- Tabela para acessórios e embalagens
CREATE TABLE acessorios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(50) NOT NULL,
    custo_un DECIMAL(10,2) NOT NULL
);

-- Tabela para histórico de precificações
CREATE TABLE historico_precificacao (
    id INT PRIMARY KEY AUTO_INCREMENT,
	titulo VARCHAR(100) DEFAULT 'ND',
	qtd_pecas INT DEFAULT 1,
    hora INT NOT NULL,
    minuto INT NOT NULL,
    peso_g DECIMAL(10,2) NOT NULL,
    custo_producao DECIMAL(10,2) NOT NULL,
    preco_consumidor DECIMAL(10,2) NOT NULL,
    preco_lojista DECIMAL(10,2) NOT NULL,
    lucro_padrao DECIMAL(10,2) NOT NULL,
    lucro_liquido DECIMAL(10,2) NOT NULL,
    data_calculo TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela do rastreamento do tempo
CREATE TABLE tempo_rastreamento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tarefa_id INT NOT NULL,
    tempo_horas INT DEFAULT 0,
    tempo_minutos INT DEFAULT 0,
    segundos_totais INT DEFAULT 0,
    data_hora_inicio DATETIME,
    data_hora_fim DATETIME NULL,
    usuario_id INT NOT NULL,
    observacoes TEXT,
    FOREIGN KEY (tarefa_id) REFERENCES tarefas(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

--ALTER TABLE tempo_rastreamento ADD COLUMN segundos_totais INT NOT NULL DEFAULT 0 AFTER tempo_minutos;

-- Inserir grupos iniciais
INSERT INTO grupos (nome, descricao) VALUES 
('admin', 'Administradores do sistema'),
('gerente', 'Gerentes com acesso elevado'),
('usuario', 'Usuários comuns');

-- Inserir permissões iniciais
INSERT INTO permissoes (nome, descricao) VALUES 
('admin_panel', 'Acesso ao painel administrativo'),
('gerenciar_usuarios', 'Gerenciar usuários do sistema'),
('relatorios', 'Visualizar relatórios'),
('dashboard', 'Visualizar dashboard'),
('perfil', 'Editar próprio perfil');

-- Relacionar permissões aos grupos
INSERT INTO grupo_permissao (grupo_id, permissao_id) VALUES 
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), -- Admin tem todas as permissões
(2, 3), (2, 4), (2, 5), -- Gerente tem acesso a relatórios, dashboard e perfil
(3, 4), (3, 5); -- Usuário comum só acessa dashboard e perfil

-- Criar um usuário admin inicial (senha: admin123)
INSERT INTO usuarios (nome, email, senha, ativo) VALUES 
('Administrador', 'admin@sistema.com', '$2y$10$dPL61K.L1TnGYpQZgXzVB.M.XVTViUBEaB0OjgvZ5E5C1MJ3BfkGe', 1);

-- Atribuir grupo admin ao usuário admin
INSERT INTO usuario_grupo (usuario_id, grupo_id) VALUES (1, 1);
