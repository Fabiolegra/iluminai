-- ========================================
-- Banco de dados: IluminAI
-- ========================================

CREATE DATABASE IF NOT EXISTS iluminai;
USE iluminai;

-- ========================================
-- Tabela: users
-- ========================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('usuario','admin') NOT NULL DEFAULT 'usuario',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ========================================
-- Tabela: ocorrencias
-- ========================================
CREATE TABLE IF NOT EXISTS ocorrencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tipo ENUM('falta de energia','poste tombado','iluminacao apagada','fio solto') NOT NULL,
    descricao TEXT NOT NULL,
    latitude DECIMAL(10,7) NOT NULL,
    longitude DECIMAL(10,7) NOT NULL,
    foto VARCHAR(255),
    status ENUM('pendente','em andamento','resolvido') NOT NULL DEFAULT 'pendente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========================================
-- Tabela: ocorrencias_log (opcional para histórico)
-- ========================================
CREATE TABLE IF NOT EXISTS ocorrencias_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ocorrencia_id INT NOT NULL,
    status_anterior ENUM('pendente','em andamento','resolvido') DEFAULT NULL,
    status_novo ENUM('pendente','em andamento','resolvido') NOT NULL,
    alterado_por INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ocorrencia_id) REFERENCES ocorrencias(id) ON DELETE CASCADE,
    FOREIGN KEY (alterado_por) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ========================================
-- Tabela: comentarios
-- ========================================
CREATE TABLE IF NOT EXISTS comentarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ocorrencia_id INT NOT NULL,
    user_id INT NOT NULL,
    comentario TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ocorrencia_id) REFERENCES ocorrencias(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========================================
-- Tabela: comentarios_visualizacao
-- ========================================
CREATE TABLE IF NOT EXISTS comentarios_visualizacao (
    user_id INT NOT NULL,
    ocorrencia_id INT NOT NULL,
    last_seen_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, ocorrencia_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (ocorrencia_id) REFERENCES ocorrencias(id) ON DELETE CASCADE
) ENGINE=InnoDB;
