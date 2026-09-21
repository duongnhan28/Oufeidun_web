CREATE TABLE IF NOT EXISTS pos_users (
  id CHAR(36) PRIMARY KEY, login_name VARCHAR(50) NOT NULL, password_hash VARCHAR(255) NOT NULL,
  display_name VARCHAR(100) NOT NULL, role ENUM('admin','staff') NOT NULL, active BOOLEAN NOT NULL DEFAULT TRUE,
  deleted_at DATETIME NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY pos_users_login_unique(login_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS pos_sessions (
  token_hash CHAR(64) PRIMARY KEY, user_id CHAR(36) NOT NULL, device_id CHAR(36) NOT NULL,
  last_seen_at DATETIME NOT NULL, expires_at DATETIME NOT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY pos_session_user_unique(user_id), INDEX pos_session_expiry(expires_at),
  CONSTRAINT fk_pos_session_user FOREIGN KEY(user_id) REFERENCES pos_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS pos_login_limits (
  ip VARCHAR(45) PRIMARY KEY, failure_count INT UNSIGNED NOT NULL DEFAULT 0,
  first_failure_at DATETIME NULL, blocked_until DATETIME NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS pos_glass_brands (
  id CHAR(36) PRIMARY KEY, name VARCHAR(100) NOT NULL UNIQUE, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS pos_products (
  id CHAR(36) PRIMARY KEY, sku VARCHAR(50) NOT NULL UNIQUE, name VARCHAR(200) NOT NULL,
  glass_type ENUM('clear','privacy','matte','other') NOT NULL, models JSON NOT NULL, brand VARCHAR(100) NOT NULL DEFAULT 'other',
  glass_brand_id CHAR(36) NULL, image_path VARCHAR(500) NULL, sale_price DECIMAL(18,0) NOT NULL DEFAULT 0,
  active BOOLEAN NOT NULL DEFAULT TRUE, version INT UNSIGNED NOT NULL DEFAULT 1,
  price_version INT UNSIGNED NOT NULL DEFAULT 1, deleted_at DATETIME NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pos_product_glass_brand FOREIGN KEY(glass_brand_id) REFERENCES pos_glass_brands(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS pos_product_costs (
  product_id CHAR(36) PRIMARY KEY, fixed_cost DECIMAL(18,0) NOT NULL DEFAULT 0,
  CONSTRAINT fk_pos_cost_product FOREIGN KEY(product_id) REFERENCES pos_products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS pos_inventory_balances (
  product_id CHAR(36) PRIMARY KEY, on_hand INT NOT NULL DEFAULT 0, version INT UNSIGNED NOT NULL DEFAULT 1,
  CONSTRAINT fk_pos_balance_product FOREIGN KEY(product_id) REFERENCES pos_products(id) ON DELETE CASCADE,
  CONSTRAINT chk_pos_balance_nonnegative CHECK(on_hand>=0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS pos_sales_returns (
  id CHAR(36) PRIMARY KEY, return_no BIGINT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE,
  order_id CHAR(36) NULL, reason VARCHAR(1000) NOT NULL, refund_amount DECIMAL(18,0) NOT NULL DEFAULT 0,
  refund_method ENUM('cash','transfer') NOT NULL, created_by CHAR(36) NOT NULL, posted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX pos_returns_order(order_id), CONSTRAINT fk_pos_return_user FOREIGN KEY(created_by) REFERENCES pos_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS pos_sales_orders (
  id CHAR(36) PRIMARY KEY, order_no BIGINT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE,
  status ENUM('draft','confirmed','partially_returned','returned','cancelled','deleted') NOT NULL DEFAULT 'draft',
  created_by CHAR(36) NOT NULL, confirmed_by CHAR(36) NULL, customer_name VARCHAR(200) NOT NULL DEFAULT '',
  customer_phone VARCHAR(30) NOT NULL DEFAULT '', note VARCHAR(1000) NOT NULL DEFAULT '', payment_method ENUM('cash','transfer') NULL,
  total_amount DECIMAL(18,0) NOT NULL DEFAULT 0, version INT UNSIGNED NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  confirmed_at DATETIME NULL, cancelled_at DATETIME NULL, cancel_reason VARCHAR(1000) NULL,
  cancel_refund_method ENUM('cash','transfer') NULL, exchange_return_id CHAR(36) NULL UNIQUE,
  INDEX pos_orders_owner(created_by,status,updated_at), INDEX pos_orders_confirmed(confirmed_at),
  CONSTRAINT fk_pos_order_creator FOREIGN KEY(created_by) REFERENCES pos_users(id),
  CONSTRAINT fk_pos_order_confirmer FOREIGN KEY(confirmed_by) REFERENCES pos_users(id),
  CONSTRAINT fk_pos_order_exchange FOREIGN KEY(exchange_return_id) REFERENCES pos_sales_returns(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS pos_sales_order_items (
  id CHAR(36) PRIMARY KEY, order_id CHAR(36) NOT NULL, product_id CHAR(36) NOT NULL, quantity INT UNSIGNED NOT NULL,
  custom_name VARCHAR(200) NULL, unit_sale_price DECIMAL(18,0) NOT NULL, quoted_price_version INT UNSIGNED NOT NULL,
  sku_snapshot VARCHAR(50) NULL, product_name_snapshot VARCHAR(200) NULL, UNIQUE KEY pos_order_product_unique(order_id,product_id),
  CONSTRAINT fk_pos_item_order FOREIGN KEY(order_id) REFERENCES pos_sales_orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_pos_item_product FOREIGN KEY(product_id) REFERENCES pos_products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS pos_sales_item_costs (
  order_item_id CHAR(36) PRIMARY KEY, unit_cost_snapshot DECIMAL(18,0) NOT NULL,
  CONSTRAINT fk_pos_item_cost FOREIGN KEY(order_item_id) REFERENCES pos_sales_order_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS pos_sales_return_items (
  id CHAR(36) PRIMARY KEY, return_id CHAR(36) NOT NULL, order_item_id CHAR(36) NOT NULL,
  quantity INT UNSIGNED NOT NULL, restock_quantity INT UNSIGNED NOT NULL, refund_amount DECIMAL(18,0) NOT NULL,
  UNIQUE KEY pos_return_order_item_unique(return_id,order_item_id),
  CONSTRAINT fk_pos_return_item_return FOREIGN KEY(return_id) REFERENCES pos_sales_returns(id) ON DELETE CASCADE,
  CONSTRAINT fk_pos_return_item_sale FOREIGN KEY(order_item_id) REFERENCES pos_sales_order_items(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS pos_inventory_documents (
  id CHAR(36) PRIMARY KEY, document_no BIGINT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE,
  type ENUM('opening','receipt','issue','damage','adjustment') NOT NULL, reason VARCHAR(1000) NOT NULL,
  created_by CHAR(36) NOT NULL, posted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pos_document_user FOREIGN KEY(created_by) REFERENCES pos_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS pos_inventory_document_items (
  id CHAR(36) PRIMARY KEY, document_id CHAR(36) NOT NULL, product_id CHAR(36) NOT NULL, quantity_delta INT NOT NULL,
  unit_purchase_price DECIMAL(18,0) NULL, fixed_cost_snapshot DECIMAL(18,0) NOT NULL,
  sku_snapshot VARCHAR(50) NOT NULL, name_snapshot VARCHAR(200) NOT NULL,
  UNIQUE KEY pos_document_product_unique(document_id,product_id),
  CONSTRAINT fk_pos_document_item_document FOREIGN KEY(document_id) REFERENCES pos_inventory_documents(id) ON DELETE CASCADE,
  CONSTRAINT fk_pos_document_item_product FOREIGN KEY(product_id) REFERENCES pos_products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS pos_inventory_movements (
  id CHAR(36) PRIMARY KEY, product_id CHAR(36) NOT NULL,
  movement_type ENUM('opening','receipt','issue','damage','adjustment','sale','cancel','return') NOT NULL,
  quantity_delta INT NOT NULL, balance_after INT UNSIGNED NOT NULL, product_version_after INT UNSIGNED NOT NULL,
  inventory_document_item_id CHAR(36) NULL, sale_order_item_id CHAR(36) NULL, return_item_id CHAR(36) NULL,
  actor_id CHAR(36) NOT NULL, occurred_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY pos_product_version_unique(product_id,product_version_after),
  INDEX pos_movements_product(product_id,occurred_at),
  CONSTRAINT fk_pos_movement_product FOREIGN KEY(product_id) REFERENCES pos_products(id),
  CONSTRAINT fk_pos_movement_document FOREIGN KEY(inventory_document_item_id) REFERENCES pos_inventory_document_items(id),
  CONSTRAINT fk_pos_movement_sale FOREIGN KEY(sale_order_item_id) REFERENCES pos_sales_order_items(id),
  CONSTRAINT fk_pos_movement_return FOREIGN KEY(return_item_id) REFERENCES pos_sales_return_items(id),
  CONSTRAINT fk_pos_movement_actor FOREIGN KEY(actor_id) REFERENCES pos_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS pos_audit_logs (
  id CHAR(36) PRIMARY KEY, actor_id CHAR(36) NOT NULL, action VARCHAR(100) NOT NULL, detail TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX pos_audit_time(created_at),
  CONSTRAINT fk_pos_audit_actor FOREIGN KEY(actor_id) REFERENCES pos_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS pos_mutation_requests (
  actor_id CHAR(36) NOT NULL, request_key CHAR(36) NOT NULL, payload JSON NOT NULL, result JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(actor_id,request_key),
  CONSTRAINT fk_pos_mutation_actor FOREIGN KEY(actor_id) REFERENCES pos_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS pos_product_images (
  path VARCHAR(190) PRIMARY KEY, mime_type VARCHAR(30) NOT NULL, data LONGBLOB NOT NULL,
  created_by CHAR(36) NOT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pos_image_user FOREIGN KEY(created_by) REFERENCES pos_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
