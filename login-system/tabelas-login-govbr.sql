-- Tabela de usuários
CREATE TABLE gov_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    google_id VARCHAR(100) NULL,
    govbr_id VARCHAR(100) NULL,
    status ENUM('active', 'inactive', 'pending') DEFAULT 'pending',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    last_login DATETIME NULL
);

-- Tabela de histórico de login
CREATE TABLE gov_login_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    login_time DATETIME NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255) NOT NULL,
    login_method VARCHAR(20) DEFAULT 'password',
    FOREIGN KEY (user_id) REFERENCES gov_users(id)
);

-- Tabela de tentativas de login
CREATE TABLE gov_login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    attempt_time DATETIME NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    INDEX (email, attempt_time)
);

-- Tabela de tokens "lembrar-me"
CREATE TABLE gov_remember_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(100) NOT NULL,
    expiry DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES gov_users(id),
    INDEX (token, expiry)
);

-- Tabela de redefinição de senha
CREATE TABLE gov_password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(100) NOT NULL,
    expiry DATETIME NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES gov_users(id),
    INDEX (token, expiry)
);
