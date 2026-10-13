CREATE DATABASE IF NOT EXISTS powervault_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE powervault_db;
CREATE TABLE IF NOT EXISTS users (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 email VARCHAR(190) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('customer','admin') NOT NULL DEFAULT 'customer',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS products (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(180) NOT NULL,
 description TEXT NULL,
 category VARCHAR(100) NULL,
 price DECIMAL(12,2) NULL DEFAULT NULL,
 cost_price DECIMAL(12,2) NULL DEFAULT NULL,
 stock INT UNSIGNED NOT NULL DEFAULT 0,
 image_path VARCHAR(255) NULL,
 active TINYINT(1) NOT NULL DEFAULT 1,
 featured TINYINT(1) NOT NULL DEFAULT 0,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_products_active(active), INDEX idx_products_name(name)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS orders (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_number VARCHAR(40) NOT NULL UNIQUE,
 user_id BIGINT UNSIGNED NULL,
 customer_name VARCHAR(150) NOT NULL,
 phone VARCHAR(40) NOT NULL,
 email VARCHAR(190) NOT NULL,
 address TEXT NOT NULL,
 city VARCHAR(100) NOT NULL,
 postal_code VARCHAR(30) NULL,
 notes TEXT NULL,
 payment_method ENUM('cod','easypaisa','jazzcash') NOT NULL,
 subtotal DECIMAL(12,2) NOT NULL,
 discount DECIMAL(12,2) NOT NULL DEFAULT 0,
 shipping DECIMAL(12,2) NOT NULL DEFAULT 0,
 total DECIMAL(12,2) NOT NULL,
 status ENUM('Pending','Confirmed','Processing','Dispatched','Delivered','Cancelled') NOT NULL DEFAULT 'Pending',
 payment_status VARCHAR(50) NOT NULL DEFAULT 'Unpaid',
 tracking_number VARCHAR(120) NULL,
 tracking_url VARCHAR(500) NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_orders_status(status), INDEX idx_orders_created(created_at),
 CONSTRAINT fk_orders_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS order_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id BIGINT UNSIGNED NOT NULL,
 product_id BIGINT UNSIGNED NULL,
 product_name VARCHAR(180) NOT NULL,
 unit_price DECIMAL(12,2) NOT NULL,
 quantity INT UNSIGNED NOT NULL,
 line_total DECIMAL(12,2) NOT NULL,
 CONSTRAINT fk_orderitems_order FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE,
 CONSTRAINT fk_orderitems_product FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS inventory_movements (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 product_id BIGINT UNSIGNED NOT NULL,
 change_qty INT NOT NULL,
 reason VARCHAR(255) NOT NULL,
 reference_id VARCHAR(80) NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_inventory_product FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS expenses (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 label VARCHAR(180) NOT NULL,
 amount DECIMAL(12,2) NOT NULL,
 expense_date DATE NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS store_settings (
 setting_key VARCHAR(100) PRIMARY KEY,
 setting_value TEXT NULL
) ENGINE=InnoDB;
INSERT INTO products(name,description,category,price,stock,featured) VALUES
('POWERVAULT Smart Bicycle Headlight','Premium bicycle headlight. Add verified specifications in admin.','Bike Lights',NULL,10,1),
('POWERVAULT USB Rechargeable Bike Light','Rechargeable light accessory. Specifications to be confirmed.','Bike Lights',NULL,10,1),
('POWERVAULT Front and Rear Safety Light Set','Front and rear visibility set. Add details in admin.','Bike Lights',NULL,10,1),
('POWERVAULT High-Brightness LED Light','LED lighting accessory. Confirm features before publishing.','Bike Lights',NULL,10,0),
('POWERVAULT Compact Portable Power Bank','Portable power accessory. Add capacity and details in admin.','Power Banks',NULL,10,0),
('POWERVAULT Fast-Charging Power Bank','Power accessory. Add verified charging specifications in admin.','Power Banks',NULL,10,0);
INSERT INTO store_settings(setting_key,setting_value) VALUES ('store_name','POWERVAULT'),('support_email','Supportpowervault@gmail.com'),('shipping_charge','250.00');
