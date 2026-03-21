-- =============================================================
--  DukaSoft Hardware ERP — MySQL Database Schema
--  Engine : InnoDB | Charset : utf8mb4
--  Run once on a fresh database: hardware_erp
-- =============================================================

CREATE DATABASE IF NOT EXISTS hardware_erp
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE hardware_erp;

-- ── Users ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
  user_id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username   VARCHAR(50)  NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL,             -- bcrypt hash
  full_name  VARCHAR(150),
  phone      VARCHAR(30),
  role       ENUM('admin','user') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Categories ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS categories (
  category_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL UNIQUE,
  description TEXT,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Items ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS items (
  item_id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id    INT UNSIGNED,
  name           VARCHAR(150) NOT NULL,
  description    TEXT,
  unit           VARCHAR(30)  NOT NULL DEFAULT 'piece',
  price          DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  stock_quantity INT NOT NULL DEFAULT 0,
  restock_level  INT NOT NULL DEFAULT 20,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Sales (header) ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sales (
  sale_id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id        INT UNSIGNED,
  customer_name  VARCHAR(150),
  payment_method ENUM('cash','mobile_money','bank','credit') DEFAULT NULL,
  notes          TEXT,
  total_amount   DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  sale_date      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Sale Line Items ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sale_items (
  sale_item_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sale_id      INT UNSIGNED NOT NULL,
  item_id      INT UNSIGNED,
  item_name    VARCHAR(150) NOT NULL,   -- snapshot at time of sale
  category     VARCHAR(100),
  quantity     INT NOT NULL DEFAULT 1,
  unit_price   DECIMAL(12,2) NOT NULL,
  subtotal     DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (sale_id)  REFERENCES sales(sale_id)  ON DELETE CASCADE,
  FOREIGN KEY (item_id)  REFERENCES items(item_id)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Restocks ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS restocks (
  restock_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id      INT UNSIGNED,
  supplier     VARCHAR(150),
  po_number    VARCHAR(100),
  notes        TEXT,
  restock_date DATE NOT NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Restock Line Items ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS restock_items (
  restock_item_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  restock_id      INT UNSIGNED NOT NULL,
  item_id         INT UNSIGNED,
  item_name       VARCHAR(150) NOT NULL,  -- snapshot
  category        VARCHAR(100),           -- snapshot at restock time
  quantity        INT NOT NULL DEFAULT 1,
  new_price       DECIMAL(12,2),          -- NULL = price unchanged
  FOREIGN KEY (restock_id) REFERENCES restocks(restock_id) ON DELETE CASCADE,
  FOREIGN KEY (item_id)    REFERENCES items(item_id)       ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- If upgrading an existing database, run:
-- ALTER TABLE restock_items ADD COLUMN category VARCHAR(100) AFTER item_name;

-- ── Activity Logs ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS logs (
  log_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED,
  action     VARCHAR(50)  NOT NULL,  -- e.g. 'sale_create', 'item_delete'
  details    TEXT,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Indexes for common queries ────────────────────────────────
CREATE INDEX idx_items_category    ON items(category_id);
CREATE INDEX idx_items_stock       ON items(stock_quantity);
CREATE INDEX idx_sales_date        ON sales(sale_date);
CREATE INDEX idx_sale_items_sale   ON sale_items(sale_id);
CREATE INDEX idx_restock_date      ON restocks(restock_date);
CREATE INDEX idx_logs_user         ON logs(user_id);
