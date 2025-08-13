-- Database setup for Photographer Portfolio
-- Run this script to create the database and tables

CREATE DATABASE IF NOT EXISTS photographer_portfolio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE photographer_portfolio;

-- Photos table
CREATE TABLE IF NOT EXISTS photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category ENUM('wedding', 'portrait', 'landscape', 'event') NOT NULL,
    image_url VARCHAR(500) NOT NULL,
    alt_text VARCHAR(255),
    featured BOOLEAN DEFAULT FALSE,
    views INT DEFAULT 0,
    likes INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_featured (featured),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- Contact messages table
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    service ENUM('wedding', 'portrait', 'event', 'other'),
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'replied') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- Settings table
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB;

-- Gallery collections table (optional feature)
CREATE TABLE IF NOT EXISTS collections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    cover_image VARCHAR(500),
    slug VARCHAR(255) UNIQUE,
    is_featured BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_featured (is_featured)
) ENGINE=InnoDB;

-- Photo collections relationship table
CREATE TABLE IF NOT EXISTS photo_collections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    photo_id INT NOT NULL,
    collection_id INT NOT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (photo_id) REFERENCES photos(id) ON DELETE CASCADE,
    FOREIGN KEY (collection_id) REFERENCES collections(id) ON DELETE CASCADE,
    UNIQUE KEY unique_photo_collection (photo_id, collection_id)
) ENGINE=InnoDB;

-- Admin users table (for future expansion)
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255),
    role ENUM('admin', 'editor') DEFAULT 'editor',
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- Activity log table (for tracking admin actions)
CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(100),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, description) VALUES
('site_name', 'Fotógrafo Portfólio', 'Nome do site exibido no cabeçalho'),
('site_description', 'Capturando momentos únicos através das lentes', 'Descrição principal do site'),
('contact_email', 'contato@fotografo.com', 'Email principal para contato'),
('contact_phone', '+55 (11) 99999-9999', 'Telefone para contato'),
('contact_address', 'São Paulo, SP', 'Localização do fotógrafo'),
('instagram_url', 'https://instagram.com/fotografo', 'Link do Instagram'),
('facebook_url', 'https://facebook.com/fotografo', 'Link do Facebook'),
('whatsapp_number', '5511999999999', 'Número do WhatsApp'),
('admin_password_hash', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Hash da senha do administrador'),
('photos_per_page', '9', 'Número de fotos por página no portfólio'),
('enable_comments', '0', 'Habilitar comentários nas fotos'),
('enable_likes', '1', 'Habilitar curtidas nas fotos'),
('google_analytics_id', '', 'ID do Google Analytics'),
('maintenance_mode', '0', 'Modo de manutenção ativado')
ON DUPLICATE KEY UPDATE 
setting_value = VALUES(setting_value),
updated_at = CURRENT_TIMESTAMP;

-- Insert sample photos (optional - for demo purposes)
INSERT INTO photos (title, description, category, image_url, alt_text, featured) VALUES
('Casamento Romântico', 'Uma cerimônia íntima em jardim florido, capturando momentos únicos de amor.', 'wedding', 'assets/portfolio/wedding1.jpg', 'Casal se beijando em cerimônia de casamento', TRUE),
('Retrato Profissional', 'Sessão executiva com iluminação natural e composição elegante.', 'portrait', 'assets/portfolio/portrait1.jpg', 'Retrato profissional de executivo', FALSE),
('Paisagem ao Pôr do Sol', 'Cores douradas do entardecer em uma vista deslumbrante da montanha.', 'landscape', 'assets/portfolio/landscape1.jpg', 'Paisagem montanhosa ao pôr do sol', TRUE),
('Evento Corporativo', 'Conferência de tecnologia com cobertura completa dos principais momentos.', 'event', 'assets/portfolio/event1.jpg', 'Palestra em evento corporativo', FALSE),
('Noivos na Praia', 'Ensaio pós-wedding em cenário paradisíaco ao final do dia.', 'wedding', 'assets/portfolio/wedding2.jpg', 'Casal de noivos caminhando na praia', FALSE),
('Família Feliz', 'Retrato familiar em ambiente descontraído, mostrando conexões genuínas.', 'portrait', 'assets/portfolio/portrait2.jpg', 'Família sorrindo em parque', FALSE),
('Floresta Mística', 'Jogos de luz e sombra entre as árvores criando atmosfera mágica.', 'landscape', 'assets/portfolio/landscape2.jpg', 'Raios de sol através das árvores na floresta', FALSE),
('Festa de Aniversário', 'Celebração especial com momentos de alegria e espontaneidade.', 'event', 'assets/portfolio/event2.jpg', 'Crianças brincando em festa de aniversário', FALSE),
('Cerimônia Tradicional', 'Ritos sagrados capturados com sensibilidade e discrição.', 'wedding', 'assets/portfolio/wedding3.jpg', 'Cerimônia de casamento tradicional', FALSE);

-- Insert sample collections
INSERT INTO collections (name, description, cover_image, slug, is_featured) VALUES
('Melhores Casamentos 2024', 'Uma seleção dos casamentos mais marcantes deste ano.', 'assets/portfolio/wedding1.jpg', 'melhores-casamentos-2024', TRUE),
('Retratos Corporativos', 'Coleção de fotos profissionais para empresas e executivos.', 'assets/portfolio/portrait1.jpg', 'retratos-corporativos', FALSE),
('Natureza Brasileira', 'Paisagens deslumbrantes de diferentes regiões do Brasil.', 'assets/portfolio/landscape1.jpg', 'natureza-brasileira', TRUE);

-- Associate photos with collections
INSERT INTO photo_collections (photo_id, collection_id, sort_order) VALUES
(1, 1, 1), (5, 1, 2), (9, 1, 3),  -- Wedding collection
(2, 2, 1), (6, 2, 2),             -- Corporate portraits
(3, 3, 1), (7, 3, 2);             -- Nature landscapes

-- Create indexes for better performance
CREATE INDEX idx_photos_category_created ON photos(category, created_at DESC);
CREATE INDEX idx_photos_featured_created ON photos(featured, created_at DESC);
CREATE INDEX idx_contact_messages_status_created ON contact_messages(status, created_at DESC);

-- Create a view for featured photos
CREATE VIEW featured_photos AS
SELECT * FROM photos 
WHERE featured = TRUE 
ORDER BY created_at DESC;

-- Create a view for latest contact messages
CREATE VIEW latest_contacts AS
SELECT * FROM contact_messages 
ORDER BY created_at DESC 
LIMIT 10;

-- Create a stored procedure to get portfolio statistics
DELIMITER $$
CREATE PROCEDURE GetPortfolioStats()
BEGIN
    SELECT 
        COUNT(*) as total_photos,
        COUNT(CASE WHEN featured = TRUE THEN 1 END) as featured_photos,
        COUNT(CASE WHEN category = 'wedding' THEN 1 END) as wedding_photos,
        COUNT(CASE WHEN category = 'portrait' THEN 1 END) as portrait_photos,
        COUNT(CASE WHEN category = 'landscape' THEN 1 END) as landscape_photos,
        COUNT(CASE WHEN category = 'event' THEN 1 END) as event_photos,
        SUM(views) as total_views,
        SUM(likes) as total_likes,
        (SELECT COUNT(*) FROM contact_messages) as total_messages,
        (SELECT COUNT(*) FROM contact_messages WHERE status = 'new') as new_messages
    FROM photos;
END$$
DELIMITER ;

-- Create a function to generate photo slugs
DELIMITER $$
CREATE FUNCTION GenerateSlug(input_text VARCHAR(255)) 
RETURNS VARCHAR(255)
DETERMINISTIC
BEGIN
    DECLARE result VARCHAR(255);
    SET result = LOWER(input_text);
    SET result = REPLACE(result, ' ', '-');
    SET result = REPLACE(result, 'ã', 'a');
    SET result = REPLACE(result, 'ç', 'c');
    SET result = REPLACE(result, 'é', 'e');
    SET result = REPLACE(result, 'í', 'i');
    SET result = REPLACE(result, 'ó', 'o');
    SET result = REPLACE(result, 'ú', 'u');
    SET result = REGEXP_REPLACE(result, '[^a-z0-9-]', '');
    RETURN result;
END$$
DELIMITER ;

-- Add slug column to photos table
ALTER TABLE photos ADD COLUMN slug VARCHAR(255) UNIQUE AFTER title;

-- Update existing photos with slugs
UPDATE photos SET slug = GenerateSlug(title) WHERE slug IS NULL;

-- Create trigger to auto-generate slugs for new photos
DELIMITER $$
CREATE TRIGGER photos_before_insert
BEFORE INSERT ON photos
FOR EACH ROW
BEGIN
    IF NEW.slug IS NULL OR NEW.slug = '' THEN
        SET NEW.slug = GenerateSlug(NEW.title);
    END IF;
END$$
DELIMITER ;

-- Grant permissions (adjust as needed for your setup)
-- CREATE USER 'portfolio_user'@'localhost' IDENTIFIED BY 'secure_password_here';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON photographer_portfolio.* TO 'portfolio_user'@'localhost';
-- FLUSH PRIVILEGES;

-- Show tables created
SHOW TABLES;

-- Display setup completion message
SELECT 'Database setup completed successfully!' as Status;
