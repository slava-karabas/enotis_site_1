"""Run AFTER shop-admin-smoke.py --ui against a disposable localhost only."""
import http.cookiejar,json,re,sys,urllib.request,urllib.parse,urllib.error,zipfile,io,sqlite3,tempfile
from pathlib import Path
base=sys.argv[1] if len(sys.argv)>1 else 'http://127.0.0.1:18084'
assert urllib.parse.urlparse(base).hostname in ('localhost','127.0.0.1')
def client(): return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
a=client();c=client();stranger=client()
def req(op,path,data=None,headers=None,binary=False):
 if isinstance(data,dict):data=urllib.parse.urlencode(data).encode()
 try:
  r=op.open(urllib.request.Request(base+path,data=data,headers=headers or {}));return r.status,r.read() if binary else r.read().decode(),r.url
 except urllib.error.HTTPError as e:return e.code,e.read().decode(),e.url
def field(h,n):return re.search(r'name="'+n+r'" value="([^"]*)"',h)[1]
creds=json.loads(Path('/tmp/enotis-shop-ui.json').read_text());_,h,_=req(a,'/admin');st,h,_=req(a,'/admin/login',dict(creds,csrf=field(h,'csrf')));assert st==200
_,boot,_=req(a,'/admin');admin_csrf=json.loads(re.search(r'<script[^>]*id="boot"[^>]*>(.*?)</script>',boot,re.S)[1])['csrf']
_,body,_=req(a,'/admin/api/site');site=json.loads(body)['site'];site['blocks'].insert(0,{'id':'qa-products','type':'text','hidden':False,'title':{'ru':'Каталог'},'extension':{'kind':'products','ids':[],'limit':100,'filter':True}})
st,body,_=req(a,'/admin/api/save',json.dumps({'site':site}).encode(),{'Content-Type':'application/json','X-CSRF':admin_csrf});assert st==200,body
st,h,_=req(c,'/');assert st==200 and 'Тестовое вино' in h and '<script>alert(1)</script>' not in h
assert 'Тестовое вино' not in req(c,'/?q=nonexistent')[1]
assert req(c,'/wine/test-wine')[0]==200
assert req(c,'/monastery/test-monastery')[0]==200
assert req(c,'/gift/test-gift')[0]==200
assert req(c,'/wine/missing')[0]==404
csrf=field(h,'csrf');st,_,_=req(c,'/cart',{'csrf':'wrong','action':'add','key':'p:1','quantity':'1'});assert st==403
st,h,_=req(c,'/cart',{'csrf':csrf,'action':'add','key':'p:1','quantity':'2'});assert st==200 and '801,00' in h
st,h,_=req(c,'/cart',{'csrf':csrf,'action':'add','key':'g:1','quantity':'1'});assert st==200 and '1 712,00' in h
order={'csrf':csrf,'action':'checkout','checkout_key':field(h,'checkout_key'),'quote':field(h,'quote'),'customer_name':'QA buyer <script>','customer_contact':'qa@example.invalid','comment':'QA order','consent':'1'}
assert req(c,'/cart',dict(order,customer_contact='bad'))[0]==422
assert req(c,'/cart',dict(order,quote='wrong'))[0]==422
assert req(c,'/cart',dict(order,consent=''))[0]==422
st,h,url=req(c,'/cart',order);assert st==200 and 'Спасибо за ваш заказ' in h,(st,h[:400])
receipt=urllib.parse.urlparse(url).path
assert req(stranger,receipt)[0]==404
assert req(c,'/cart',order)[2]==url # double submit uses the same receipt
_,h,_=req(a,'/admin/shop/orders');assert 'QA buyer &lt;script&gt;' in h and '№1' in h
_,h,_=req(a,'/admin/shop/orders?id=1');assert '1 712,00' in h and 'QA-01' in h
token=field(h,'csrf')
assert req(a,'/admin/shop/orders',{'csrf':token,'id':'1','status':'completed'})[0]==422
assert req(a,'/admin/shop/orders',{'csrf':token,'id':'1','status':'confirmed'})[0]==200
assert req(a,'/admin/shop/orders',{'csrf':token,'id':'1','status':'completed'})[0]==200
assert req(a,'/admin/shop/orders',{'csrf':token,'id':'1','status':'new'})[0]==422
st,package,_=req(a,'/admin/shop/transfer',{'csrf':token,'action':'export'},binary=True);assert st==200,(st,package[:400])
z=zipfile.ZipFile(io.BytesIO(package));assert 'data/site.sqlite' in z.namelist() and 'app/shop_store.php' in z.namelist()
manifest=json.loads(z.read('manifest.json'));assert manifest['engine']=='enotis-shop'
content=json.loads(z.read('content.json'));assert 'admins' not in content and 'shop_orders' not in content
f=tempfile.NamedTemporaryFile();f.write(z.read('data/site.sqlite'));f.flush();db=sqlite3.connect(f.name)
assert db.execute('select count(*) from admins').fetchone()[0]==0
assert db.execute('select count(*) from shop_orders').fetchone()[0]==0
assert db.execute("select count(*) from settings where key='shop_abuse_key'").fetchone()[0]==0
assert db.execute('pragma integrity_check').fetchone()[0]=='ok'
Path('/tmp/enotis-shop-portable.zip').write_bytes(package)
# Existing source site: replacement must keep credentials and orders, preserving immutable totals.
def upload(payload):
 boundary='qa-shop-boundary';data=(f'--{boundary}\r\nContent-Disposition: form-data; name="csrf"\r\n\r\n{token}\r\n--{boundary}\r\nContent-Disposition: form-data; name="action"\r\n\r\nimport\r\n--{boundary}\r\nContent-Disposition: form-data; name="confirm"\r\n\r\n1\r\n--{boundary}\r\nContent-Disposition: form-data; name="file"; filename="shop.zip"\r\nContent-Type: application/zip\r\n\r\n').encode()+payload+(f'\r\n--{boundary}--\r\n').encode()
 return req(a,'/admin/shop/transfer',data,{'Content-Type':'multipart/form-data; boundary='+boundary})
st,h,_=upload(package);assert st==200 and 'Контент загружен' in h,(st,h[:1500])
assert '1 712,00' in req(a,'/admin/shop/orders?id=1')[1]
assert 'Тестовое вино' in req(c,'/')[1]
assert 'Выйти' in req(a,'/admin/shop')[1]
bad=io.BytesIO()
with zipfile.ZipFile(bad,'w') as out:
 for n in z.namelist():out.writestr(n,z.read(n) if n!='content.json' else b'{}')
assert upload(bad.getvalue())[0]==422
assert 'Тестовое вино' in req(c,'/')[1]
print('PASS: storefront, filters, cart products/gifts, server totals, CSRF, validation, immutable order, idempotency, private receipt, status transitions, portable ZIP without credentials/orders, content restore preserving orders/login, corrupt archive rejected')
