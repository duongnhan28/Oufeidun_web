CREATE TABLE IF NOT EXISTS app_migrations (
  name VARCHAR(190) PRIMARY KEY,
  applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_sessions (
  token_hash CHAR(64) PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX sessions_expiry_idx(expires_at),
  CONSTRAINT fk_admin_sessions_user FOREIGN KEY(user_id) REFERENCES admin_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_limits (
  ip VARCHAR(45) PRIMARY KEY,
  failure_count INT UNSIGNED NOT NULL DEFAULT 0,
  first_failure_at DATETIME NULL,
  blocked_until DATETIME NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_images (
  id CHAR(36) PRIMARY KEY,
  mime_type VARCHAR(30) NOT NULL,
  data LONGBLOB NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(180) NOT NULL,
  slug VARCHAR(180) NOT NULL UNIQUE,
  category VARCHAR(100) NOT NULL DEFAULT 'Kính cường lực',
  description TEXT NOT NULL,
  badge VARCHAR(30) NOT NULL DEFAULT '',
  image VARCHAR(500) NOT NULL,
  features JSON NOT NULL,
  specifications JSON NOT NULL,
  gallery JSON NOT NULL,
  published BOOLEAN NOT NULL DEFAULT FALSE,
  featured BOOLEAN NOT NULL DEFAULT FALSE,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  show_factory BOOLEAN NOT NULL DEFAULT FALSE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX products_listing_idx(published,sort_order,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contact_messages (
  id CHAR(36) PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  email VARCHAR(180) NOT NULL DEFAULT '',
  message TEXT NOT NULL,
  source ENUM('contact','oem') NOT NULL,
  ip VARCHAR(45) NOT NULL,
  mail_status ENUM('pending','sending','sent','failed') NOT NULL DEFAULT 'pending',
  last_attempt_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX contact_ip_time_idx(ip,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS glass_lookup_products (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  legacy_id CHAR(36) NOT NULL UNIQUE,
  sku VARCHAR(180) NOT NULL UNIQUE,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  description TEXT NOT NULL,
  is_featured BOOLEAN NOT NULL DEFAULT FALSE,
  active BOOLEAN NOT NULL DEFAULT TRUE,
  source ENUM('legacy','admin') NOT NULL DEFAULT 'legacy',
  legacy_created_at DATETIME NULL,
  legacy_updated_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX lookup_product_active_idx(active,sku),
  INDEX lookup_product_source_idx(source,active,updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS glass_lookup_models (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  legacy_id CHAR(36) NOT NULL UNIQUE,
  product_id BIGINT UNSIGNED NOT NULL,
  raw_model_name VARCHAR(255) NOT NULL,
  display_name VARCHAR(255) NOT NULL,
  normalized_name VARCHAR(255) NOT NULL,
  brand VARCHAR(50) NOT NULL DEFAULT 'other',
  glass_type ENUM('standard','privacy','unknown') NOT NULL DEFAULT 'standard',
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  review_status ENUM('clean','needs_review','confirmed') NOT NULL DEFAULT 'clean',
  INDEX lookup_model_product_idx(product_id,sort_order,id),
  INDEX lookup_model_search_idx(normalized_name),
  INDEX lookup_model_filter_idx(brand,glass_type),
  CONSTRAINT fk_lookup_model_product FOREIGN KEY(product_id) REFERENCES glass_lookup_products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS glass_lookup_images (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  legacy_id CHAR(36) NOT NULL UNIQUE,
  product_id BIGINT UNSIGNED NOT NULL,
  image_id CHAR(36) NOT NULL,
  source_url VARCHAR(1000) NOT NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  INDEX lookup_image_product_idx(product_id,sort_order,id),
  CONSTRAINT fk_lookup_image_product FOREIGN KEY(product_id) REFERENCES glass_lookup_products(id) ON DELETE CASCADE,
  CONSTRAINT fk_lookup_image_blob FOREIGN KEY(image_id) REFERENCES product_images(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

