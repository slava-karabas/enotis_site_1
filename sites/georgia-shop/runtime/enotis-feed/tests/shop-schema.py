"""Проверки новой схемы магазина на SQLite, без реального наполнения."""
import sqlite3
from pathlib import Path
root=Path(__file__).resolve().parents[1]
c=sqlite3.connect(':memory:');c.execute('PRAGMA foreign_keys=ON')
for p in sorted((root/'app/migrations').glob('*.sql')): c.executescript(p.read_text())
def rejects(sql):
 try: c.execute(sql)
 except sqlite3.IntegrityError: return
 raise AssertionError(sql)
c.execute("INSERT INTO shop_countries(id,code) VALUES(1,'GE')")
c.execute("INSERT INTO shop_monasteries(id,country_id,slug) VALUES(1,1,'test')")
c.execute("INSERT INTO shop_products(id,monastery_id,sku,slug,price_minor) VALUES(1,1,'TEST','test',40000)")
rejects("INSERT INTO shop_products(monastery_id,sku,slug,price_minor) VALUES(1,'BAD','bad',-1)")
rejects("INSERT INTO shop_products(monastery_id,sku,slug,price_minor) VALUES(999,'BAD','bad',10)")
rejects("DELETE FROM shop_monasteries WHERE id=1")
c.execute("INSERT INTO shop_orders(id,public_token,idempotency_key,total_minor,customer_name,customer_contact) VALUES(1,'random','once',40000,'Test','test')")
c.execute("INSERT INTO shop_order_items(order_id,product_id,kind,sku_snapshot,name_snapshot,quantity,unit_price_minor) VALUES(1,1,'product','TEST','Original',1,40000)")
c.execute("UPDATE shop_products SET price_minor=50000 WHERE id=1")
assert c.execute('SELECT unit_price_minor FROM shop_order_items').fetchone()[0]==40000
rejects("INSERT INTO shop_order_items(order_id,kind,name_snapshot,quantity,unit_price_minor) VALUES(1,'product','Bad',0,10)")
c.execute('DELETE FROM shop_products WHERE id=1')
assert c.execute('SELECT product_id,name_snapshot FROM shop_order_items').fetchone()==(None,'Original')
assert not c.execute('PRAGMA foreign_key_check').fetchall()
assert c.execute('PRAGMA integrity_check').fetchone()[0]=='ok'
print('PASS: clean installation, references, price/quantity constraints, immutable price snapshots, retained order history')
