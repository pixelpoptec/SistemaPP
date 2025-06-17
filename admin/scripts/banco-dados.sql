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
