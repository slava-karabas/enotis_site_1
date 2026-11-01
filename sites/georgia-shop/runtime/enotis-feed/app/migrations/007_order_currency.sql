-- Rebuild currency checks without changing values, identities or dependent rows.
CREATE TABLE shop_products_currency_next (
 id INTEGER PRIMARY KEY, monastery_id INTEGER NOT NULL REFERENCES shop_monasteries(id),
 sku TEXT NOT NULL UNIQUE, slug TEXT NOT NULL UNIQUE,
 beverage_type TEXT NOT NULL DEFAULT 'wine' CHECK(beverage_type IN ('wine','fortified','spirit')),
 color TEXT CHECK(color IN ('red','white','rose','amber')),
 sweetness TEXT CHECK(sweetness IN ('dry','semi_dry','semi_sweet','sweet')),
 vintage INTEGER CHECK(vintage BETWEEN 1800 AND 2200),
 alcohol_basis_points INTEGER CHECK(alcohol_basis_points BETWEEN 0 AND 10000),
 volume_ml INTEGER CHECK(volume_ml>0), price_minor INTEGER NOT NULL CHECK(price_minor>=0),
 currency TEXT NOT NULL DEFAULT 'EUR' CHECK(currency IN ('EUR','GEL','RUB','USD')),
 photo_id INTEGER REFERENCES photos(id) ON DELETE SET NULL,
 status TEXT NOT NULL DEFAULT 'draft' CHECK(status IN ('draft','published','archived')),
 available INTEGER NOT NULL DEFAULT 1 CHECK(available IN (0,1)),
 collectible INTEGER NOT NULL DEFAULT 0 CHECK(collectible IN (0,1)),
 position INTEGER NOT NULL DEFAULT 0,
 category TEXT NOT NULL DEFAULT ''
);
INSERT INTO shop_products_currency_next SELECT * FROM shop_products;
DROP TABLE shop_products;
ALTER TABLE shop_products_currency_next RENAME TO shop_products;
CREATE TABLE shop_orders_currency_next (
 id INTEGER PRIMARY KEY, public_token TEXT NOT NULL UNIQUE, idempotency_key TEXT NOT NULL UNIQUE,
 status TEXT NOT NULL DEFAULT 'new' CHECK(status IN ('new','confirmed','completed','cancelled')),
 currency TEXT NOT NULL DEFAULT 'EUR' CHECK(currency IN ('EUR','GEL','RUB','USD')),
 total_minor INTEGER NOT NULL CHECK(total_minor>=0), customer_name TEXT NOT NULL,
 customer_contact TEXT NOT NULL, greeting TEXT NOT NULL DEFAULT '',
 created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
 consent_at TEXT, updated_at TEXT
);
INSERT INTO shop_orders_currency_next SELECT * FROM shop_orders;
DROP TABLE shop_orders;
ALTER TABLE shop_orders_currency_next RENAME TO shop_orders;
CREATE INDEX shop_products_monastery ON shop_products(monastery_id);
CREATE INDEX shop_products_catalog ON shop_products(status,available,price_minor);
