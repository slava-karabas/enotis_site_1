"""After shop fixtures, only on a disposable localhost installation."""
import http.cookiejar,json,os,re,sys,urllib.request,urllib.parse
from pathlib import Path
base=sys.argv[1];assert urllib.parse.urlparse(base).hostname in ('127.0.0.1','localhost')
def client():return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
a=client()
def req(op,path,data=None,headers=None):
 if isinstance(data,dict):data=urllib.parse.urlencode(data).encode()
 r=op.open(urllib.request.Request(base+path,data=data,headers=headers or {}));return r.read().decode(),r.url
field=lambda h,k:re.search(r'name="'+k+r'" value="([^"]*)"',h)[1]
h,_=req(a,'/admin');creds=json.loads(Path(os.environ.get('ENOTIS_TEST_UI','/tmp/enotis-shop-ui.json')).read_text());h,_=req(a,'/admin/login',dict(creds,csrf=field(h,'csrf')))
csrf=json.loads(re.search(r'<script[^>]*id="boot"[^>]*>(.*?)</script>',h,re.S)[1])['csrf'];h,_=req(a,'/admin/api/site');site=json.loads(h)['site']
selection={'id':'qa-products-cards','type':'text','title':{'ru':'Подборка товаров'},'extension':{'kind':'products','ids':[1],'limit':8}}
legacy={'id':'qa-legacy-cards','type':'tiles','kind':'wine','size':'large','title':{'ru':'Товары'},'tiles':[{'id':'qa-product-card','title':{'ru':'Карточка товара'},'price':15,'text':{'ru':'Описание'}}]}
page={'id':'qa-cards-pages','type':'tiles','kind':'normal','size':'large','tiles':[{'id':'qa-cards-producer','title':{'ru':'Производитель'},'slug':'qa-card-producer','as_page':True,'blocks':[selection,legacy]}]}
site['blocks']=[page,dict(selection,id='qa-home-products')]
h,_=req(a,'/admin/api/save',json.dumps({'site':site}).encode(),{'Content-Type':'application/json','X-CSRF':csrf});assert json.loads(h)['ok'],h
c=client();h,_=req(c,'/');link=re.search(r'href="([^"]*qa-card-producer[^"]*)"',h);assert link,h[:1000]
h,_=req(c,link[1]);assert 'cards product-grid' in h and 'card--wine product-card' in h and 'tiles--compact tiles--wine' in h
assert 'height:220px' not in h
csrf=field(h,'csrf');assert len(csrf)>=32
h,_=req(c,'/cart',{'csrf':csrf,'action':'add','key':'p:1','quantity':1});assert 'Корзина' in h and 'name="checkout_key"' in h
h,_=req(c,'/monastery/test-monastery');assert 'product-list' in h and 'class="filters"' not in h
h,_=req(c,'/catalog');assert 'class="filters"' in h and 'product-grid' in h
print('PASS: one shop card renderer, fixed informational-product size, nested page cart/CSRF, producer full-width products, catalogue filters retained')
