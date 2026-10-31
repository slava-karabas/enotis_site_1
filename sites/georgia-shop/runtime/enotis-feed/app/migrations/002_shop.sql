-- Модель магазина №2. Цены в евроцентах, без округления REAL.
CREATE TABLE shop_countries (
 id INTEGER PRIMARY KEY, code TEXT NOT NULL UNIQUE CHECK(length(code)=2), position INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE shop_country_i18n (
 country_id INTEGER NOT NULL REFERENCES shop_countries(id) ON DELETE CASCADE,
 lang TEXT NOT NULL REFERENCES languages(code), name TEXT NOT NULL,
 PRIMARY KEY(country_id,lang)
);
CREATE TABLE shop_monasteries (
 id INTEGER PRIMARY KEY, country_id INTEGER NOT NULL REFERENCES shop_countries(id),
 slug TEXT NOT NULL UNIQUE, photo_id INTEGER REFERENCES photos(id) ON DELETE SET NULL,
 status TEXT NOT NULL DEFAULT 'draft' CHECK(status IN ('draft','published','archived')),
 position INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE shop_monastery_i18n (
 monastery_id INTEGER NOT NULL REFERENCES shop_monasteries(id) ON DELETE CASCADE,
 lang TEXT NOT NULL REFERENCES languages(code), name TEXT NOT NULL, description TEXT NOT NULL DEFAULT '',
 PRIMARY KEY(monastery_id,lang)
);
CREATE TABLE shop_products (
 id INTEGER PRIMARY KEY, monastery_id INTEGER NOT NULL REFERENCES shop_monasteries(id),
 sku TEXT NOT NULL UNIQUE, slug TEXT NOT NULL UNIQUE,
 beverage_type TEXT NOT NULL DEFAULT 'wine' CHECK(beverage_type IN ('wine','fortified','spirit')),
 color TEXT CHECK(color IN ('red','white','rose','amber')),
 sweetness TEXT CHECK(sweetness IN ('dry','semi_dry','semi_sweet','sweet')),
 vintage INTEGER CHECK(vintage BETWEEN 1800 AND 2200),
 alcohol_basis_points INTEGER CHECK(alcohol_basis_points BETWEEN 0 AND 10000),
 volume_ml INTEGER CHECK(volume_ml>0), price_minor INTEGER NOT NULL CHECK(price_minor>=0),
 currency TEXT NOT NULL DEFAULT 'EUR' CHECK(currency='EUR'),
 photo_id INTEGER REFERENCES photos(id) ON DELETE SET NULL,
 status TEXT NOT NULL DEFAULT 'draft' CHECK(status IN ('draft','published','archived')),
 available INTEGER NOT NULL DEFAULT 1 CHECK(available IN (0,1)),
 collectible INTEGER NOT NULL DEFAULT 0 CHECK(collectible IN (0,1)),
 position INTEGER NOT NULL DEFAULT 0
);
CREATE INDEX shop_products_monastery ON shop_products(monastery_id);
CREATE INDEX shop_products_catalog ON shop_products(status,available,price_minor);
CREATE TABLE shop_product_i18n (
 product_id INTEGER NOT NULL REFERENCES shop_products(id) ON DELETE CASCADE,
 lang TEXT NOT NULL REFERENCES languages(code), name TEXT NOT NULL,
 grape TEXT NOT NULL DEFAULT '', description TEXT NOT NULL DEFAULT '',
 PRIMARY KEY(product_id,lang)
);
CREATE TABLE shop_collections (
 id INTEGER PRIMARY KEY, slug TEXT NOT NULL UNIQUE,
 status TEXT NOT NULL DEFAULT 'draft' CHECK(status IN ('draft','published','archived'))
);
CREATE TABLE shop_collection_i18n (
 collection_id INTEGER NOT NULL REFERENCES shop_collections(id) ON DELETE CASCADE,
 lang TEXT NOT NULL REFERENCES languages(code), name TEXT NOT NULL,
 PRIMARY KEY(collection_id,lang)
);
CREATE TABLE shop_collection_products (
 collection_id INTEGER NOT NULL REFERENCES shop_collections(id) ON DELETE CASCADE,
 product_id INTEGER NOT NULL REFERENCES shop_products(id), position INTEGER NOT NULL DEFAULT 0,
 PRIMARY KEY(collection_id,product_id)
);
CREATE TABLE shop_gift_options (
 id INTEGER PRIMARY KEY, kind TEXT NOT NULL CHECK(kind IN ('packaging','extra')),
 price_minor INTEGER NOT NULL CHECK(price_minor>=0),
 active INTEGER NOT NULL DEFAULT 1 CHECK(active IN (0,1))
);
CREATE TABLE shop_gift_option_i18n (
 option_id INTEGER NOT NULL REFERENCES shop_gift_options(id) ON DELETE CASCADE,
 lang TEXT NOT NULL REFERENCES languages(code), name TEXT NOT NULL,
 PRIMARY KEY(option_id,lang)
);
CREATE TABLE shop_gifts (
 id INTEGER PRIMARY KEY, slug TEXT NOT NULL UNIQUE,
 packaging_id INTEGER REFERENCES shop_gift_options(id),
 photo_id INTEGER REFERENCES photos(id) ON DELETE SET NULL,
 status TEXT NOT NULL DEFAULT 'draft' CHECK(status IN ('draft','published','archived'))
);
CREATE TABLE shop_gift_i18n (
 gift_id INTEGER NOT NULL REFERENCES shop_gifts(id) ON DELETE CASCADE,
 lang TEXT NOT NULL REFERENCES languages(code), name TEXT NOT NULL, description TEXT NOT NULL DEFAULT '',
 PRIMARY KEY(gift_id,lang)
);
CREATE TABLE shop_gift_products (
 gift_id INTEGER NOT NULL REFERENCES shop_gifts(id) ON DELETE CASCADE,
 product_id INTEGER NOT NULL REFERENCES shop_products(id), quantity INTEGER NOT NULL CHECK(quantity>0),
 PRIMARY KEY(gift_id,product_id)
);
CREATE TABLE shop_gift_extras (
 gift_id INTEGER NOT NULL REFERENCES shop_gifts(id) ON DELETE CASCADE,
 option_id INTEGER NOT NULL REFERENCES shop_gift_options(id), PRIMARY KEY(gift_id,option_id)
);
CREATE TABLE shop_orders (
 id INTEGER PRIMARY KEY, public_token TEXT NOT NULL UNIQUE, idempotency_key TEXT NOT NULL UNIQUE,
 status TEXT NOT NULL DEFAULT 'new' CHECK(status IN ('new','confirmed','completed','cancelled')),
 currency TEXT NOT NULL DEFAULT 'EUR' CHECK(currency='EUR'),
 total_minor INTEGER NOT NULL CHECK(total_minor>=0), customer_name TEXT NOT NULL,
 customer_contact TEXT NOT NULL, greeting TEXT NOT NULL DEFAULT '',
 created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE shop_order_items (
 id INTEGER PRIMARY KEY, order_id INTEGER NOT NULL REFERENCES shop_orders(id) ON DELETE CASCADE,
 product_id INTEGER REFERENCES shop_products(id) ON DELETE SET NULL,
 option_id INTEGER REFERENCES shop_gift_options(id) ON DELETE SET NULL,
 kind TEXT NOT NULL CHECK(kind IN ('product','packaging','extra')),
 group_key TEXT, sku_snapshot TEXT NOT NULL DEFAULT '', name_snapshot TEXT NOT NULL,
 quantity INTEGER NOT NULL CHECK(quantity>0), unit_price_minor INTEGER NOT NULL CHECK(unit_price_minor>=0)
);
CREATE INDEX shop_order_items_order ON shop_order_items(order_id);
