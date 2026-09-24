ALTER TABLE pos_sales_orders
  ADD COLUMN customer_address VARCHAR(500) NOT NULL DEFAULT '' AFTER customer_phone,
  ADD COLUMN customer_type ENUM('retail','wholesale') NOT NULL DEFAULT 'retail' AFTER customer_address,
  MODIFY COLUMN payment_method ENUM('cash','transfer','cod','cod_transfer','cash_transfer') NULL;

ALTER TABLE pos_sales_order_items
  ADD COLUMN custom_unit_price DECIMAL(18,0) NULL AFTER custom_name,
  ADD COLUMN item_kind ENUM('sale','gift','sample') NOT NULL DEFAULT 'sale' AFTER custom_unit_price,
  ADD COLUMN item_note VARCHAR(500) NOT NULL DEFAULT '' AFTER item_kind;

CREATE TABLE IF NOT EXISTS pos_sales_payments (
  id CHAR(36) PRIMARY KEY,
  order_id CHAR(36) NOT NULL,
  amount DECIMAL(18,0) NOT NULL,
  method ENUM('cash','transfer','cod','cod_transfer','cash_transfer') NOT NULL,
  note VARCHAR(500) NOT NULL DEFAULT '',
  created_by CHAR(36) NOT NULL,
  paid_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX pos_payments_order(order_id,paid_at),
  CONSTRAINT chk_pos_payment_positive CHECK(amount>0),
  CONSTRAINT fk_pos_payment_order FOREIGN KEY(order_id) REFERENCES pos_sales_orders(id),
  CONSTRAINT fk_pos_payment_user FOREIGN KEY(created_by) REFERENCES pos_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
