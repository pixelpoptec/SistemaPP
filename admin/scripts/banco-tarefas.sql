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
