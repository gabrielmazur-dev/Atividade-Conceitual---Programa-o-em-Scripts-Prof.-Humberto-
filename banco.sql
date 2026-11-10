CREATE DATABASE IF NOT EXISTS produtos_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

USE produtos_db;

CREATE TABLE IF NOT EXISTS produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    descricao VARCHAR(100) NOT NULL,
    categoria VARCHAR(50) NOT NULL,
    valor_compra DECIMAL(10,2) NOT NULL,
    valor_venda DECIMAL(10,2) NOT NULL,
    estoque INT NOT NULL DEFAULT 0
);
