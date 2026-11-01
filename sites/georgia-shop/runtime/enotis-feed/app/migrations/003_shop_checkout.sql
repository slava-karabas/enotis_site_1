-- Снимки согласия и ограничение частоты, без хранения исходного IP.
ALTER TABLE shop_orders ADD COLUMN consent_at TEXT;
ALTER TABLE shop_orders ADD COLUMN updated_at TEXT;
CREATE TABLE shop_checkout_limits (token TEXT PRIMARY KEY, started INTEGER NOT NULL, attempts INTEGER NOT NULL);
UPDATE settings SET value='EUR' WHERE key='currency';
