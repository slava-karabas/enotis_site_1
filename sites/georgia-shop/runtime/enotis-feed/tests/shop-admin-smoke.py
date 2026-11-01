"""Catalogue regression: only use an empty disposable localhost installation."""
import http.cookiejar,json,re,secrets,sys,urllib.request,urllib.parse,urllib.error
from pathlib import Path
base=sys.argv[1] if len(sys.argv)>1 else 'http://127.0.0.1:18082'
assert urllib.parse.urlparse(base).hostname in ('127.0.0.1','localhost')
def client():return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
u=client();a=client()
def req(op,path,data=None,headers=None):
 if isinstance(data,dict):data=urllib.parse.urlencode(data).encode()
 try:
  r=op.open(urllib.request.Request(base+path,data=data,headers=headers or {}));return r.status,r.read().decode(),r.url
 except urllib.error.HTTPError as e:return e.code,e.read().decode(),e.url
def csrf(html):return re.search(r'name="csrf" value="([^"]+)"',html)[1]
status,html,_=req(u,'/admin/install');assert 'name="site_title"' in html,'Use an EMPTY local database'
pwd=secrets.token_urlsafe(24)
status,html,_=req(u,'/admin/install',{'csrf':csrf(html),'site_title':'Shop QA','site_lang':'ru','ui_lang':'ru','login':'qa-admin','email':'qa@example.invalid','password':pwd,'password2':pwd})
assert status==200 and 'id="boot"' in html
# Optional local UI handoff file contains disposable test credentials, never committed.
if '--ui' in sys.argv:
 import os
 f='/tmp/enotis-shop-ui.json';fd=os.open(f,os.O_WRONLY|os.O_CREAT|os.O_TRUNC,0o600)
 with os.fdopen(fd,'w') as out:json.dump({'login':'qa-admin','password':pwd},out)

def save(kind,values,id='',lang='ru',expected=200):
 path='/admin/shop/'+kind+'?lang='+lang
 _,h,_=req(u,path+('&id='+str(id) if id else '&new=1'))
 st,body,url=req(u,path,dict(values,csrf=csrf(h),id=id,form_complete='1'))
 assert st==expected,(kind,st,body[:300])
 if expected==200:assert 'Изменения сохранены' in body
 return (int(urllib.parse.parse_qs(urllib.parse.urlparse(url).query)['id'][0]),body) if expected==200 else (None,body)
country,_=save('countries',{'name':'Грузия','code':'GE','position':'0'})
mon={'name':'Тестовая обитель','country_id':country,'slug':'test-monastery','status':'published','position':'0','photo_id':''}
mid,_=save('monasteries',mon)
p={'name':'Тестовое вино','monastery_id':mid,'slug':'test-wine','sku':'QA-01','beverage_type':'wine','color':'red','sweetness':'dry','vintage':'2019','alcohol':'12,70','volume_ml':'750','price':'400,50','status':'published','available':'1','position':'0','grape':'Саперави','description':'<script>alert(1)</script>','photo_id':''}
pid,h=save('products',p);assert '&lt;script&gt;' in h and '<script>alert(1)</script>' not in h
save('products',dict(p,price='-1'),pid,expected=422)
save('products',dict(p,monastery_id='9999'),pid,expected=422)
save('products',dict(p,slug='../admin'),pid,expected=422)
save('products',dict(p,price='1.234'),pid,expected=422)
_,h,_=req(u,'/admin/shop/products?id='+str(pid));assert '400.50' in h
# A failed duplicate must not replace or partially edit an existing row.
save('products',dict(p,name='Duplicate'),expected=422)
save('products',dict(p,name='Vin de test',grape='Saperavi'),pid,lang='fr')
_,h,_=req(u,'/admin/shop/products?id='+str(pid)+'&lang=ru');assert 'Тестовое вино' in h
pack,_=save('options',{'name':'Кофр','kind':'packaging','price':'80','active':'1'})
extra,_=save('options',{'name':'Открытка','kind':'extra','price':'30','active':'1'})
g={'name':'Тестовый набор','slug':'test-gift','status':'published','packaging_id':pack,'photo_id':'','items['+str(pid)+']':'2','extras['+str(extra)+']':'1'}
gid,h=save('gifts',g);assert '911,00' in h
save('gifts',dict(g,**{'items['+str(pid)+']':'-1'}),gid,expected=422)
_,h,_=req(u,'/admin/shop/gifts?id='+str(gid));assert '911,00' in h
save('products',dict(p,price='450.00'),pid)
_,h,_=req(u,'/admin/shop/gifts?id='+str(gid));assert '1 010,00' in h
save('products',dict(p,status='archived'),pid)
save('gifts',g,gid,expected=422)
save('products',p,pid)
# Actual photo upload through the existing protected API and shared photo library.
_,h,_=req(u,'/admin/shop/products?id='+str(pid));t=csrf(h)
photo=(Path(__file__).parent/'fixtures/sample/photos/wine-alaverdi.jpg').read_bytes()
boundary='qa-'+secrets.token_hex(12)
data=('--'+boundary+'\r\nContent-Disposition: form-data; name="file"; filename="qa.jpg"\r\nContent-Type: image/jpeg\r\n\r\n').encode()+photo+('\r\n--'+boundary+'--\r\n').encode()
st,b,_=req(u,'/admin/api/photo',data,{'Content-Type':'multipart/form-data; boundary='+boundary,'X-CSRF':t});assert st==200,b
photoid=json.loads(b)['photo']['id'];save('products',dict(p,photo_id=photoid),pid)
_,h,_=req(u,'/admin/shop/products?id='+str(pid));assert '/admin/api/original?id='+str(photoid) in h
# Normal page-editor save must preserve the new catalogue and photo records.
_,h,_=req(u,'/admin');t=json.loads(re.search(r'<script[^>]*id="boot"[^>]*>(.*?)</script>',h,re.S)[1])['csrf']
_,b,_=req(u,'/admin/api/site');site=json.loads(b)['site']
st,b,_=req(u,'/admin/api/save',json.dumps({'site':site}).encode(),{'Content-Type':'application/json','X-CSRF':t});assert st==200,b
_,h,_=req(u,'/admin/shop/products?id='+str(pid));assert '/admin/api/original?id='+str(photoid) in h
# Search and boundary protection.
_,h,_=req(u,'/admin/shop/products?q=QA-01');assert 'Тестовое вино' in h
assert req(u,'/admin/shop/products',dict(p,id=pid,csrf='bad'))[0]==403
assert 'id="boot"' not in req(a,'/admin/shop/products')[1]
assert req(u,'/admin/shop/unknown')[0]==404
assert req(u,'/admin/shop/products?id=99999')[0]==404
print('PASS: countries, monasteries, wines, gift options, gift composition/total, translations, edits, archives, duplicate rollback, validation, XSS escaping, shared photos, editor-save preservation, search, auth and CSRF')
