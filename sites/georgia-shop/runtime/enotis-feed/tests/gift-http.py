"""Run after shop-admin-smoke.py and shop-checkout-smoke.py on disposable localhost."""
import http.cookiejar,json,re,sys,urllib.request,urllib.parse,urllib.error
from pathlib import Path
base=sys.argv[1];assert urllib.parse.urlparse(base).hostname in ('localhost','127.0.0.1')
def client():return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
a=client();c=client()
def req(op,path,data=None,headers=None):
 if isinstance(data,dict):data=urllib.parse.urlencode(data,doseq=True).encode()
 try:
  r=op.open(urllib.request.Request(base+path,data=data,headers=headers or {}));return r.status,r.read().decode(),r.url
 except urllib.error.HTTPError as e:return e.code,e.read().decode(),e.url
def field(h,n):return re.search(r'name="'+n+r'" value="([^"]*)"',h)[1]
_,h,_=req(a,'/admin');creds=json.loads(Path('/tmp/enotis-shop-ui.json').read_text());_,h,_=req(a,'/admin/login',dict(creds,csrf=field(h,'csrf')))
csrf=json.loads(re.search(r'<script[^>]*id="boot"[^>]*>(.*?)</script>',h,re.S)[1])['csrf']
_,h,_=req(a,'/admin/api/site');site=json.loads(h)['site'];site['blocks']=[b for b in site['blocks'] if b.get('extension',{}).get('kind')!='gift'];site['blocks'].append({'id':'qa-constructor','type':'text','hidden':False,'title':{'ru':'Подарочный конструктор'},'extension':{'kind':'gift','ids':[1],'box_minor':500,'card_minor':100}})
st,h,_=req(a,'/admin/api/save',json.dumps({'site':site}).encode(),{'Content-Type':'application/json','X-CSRF':csrf});assert st==200,h
_,h,_=req(c,'/');gift_id=field(h,'block_id');public_csrf=field(h,'csrf');assert 'data-gift-total' in h
st,h,_=req(c,'/catalog#catalog');assert st==200 and 'name="sort"' in h and 'name="country"' in h
assert 'Тестовое вино' not in req(c,'/catalog?q=nonexistent')[1]
assert req(c,'/cart',{'csrf':public_csrf,'action':'custom_gift','block_id':gift_id,'products[]':['999999']})[0]==422
st,h,_=req(c,'/cart',{'csrf':public_csrf,'action':'custom_gift','block_id':gift_id,'products[]':['1'],'box':'1','card':'1','price':'0.01'});assert st==200 and '406,50' in h,(st,h[-1000:])
st,h,url=req(c,'/cart',{'csrf':public_csrf,'action':'checkout','checkout_key':field(h,'checkout_key'),'quote':field(h,'quote'),'customer_name':'Gift QA','customer_contact':'gift@example.invalid','comment':'Constructor test','consent':'1'});assert st==200 and 'Спасибо за ваш заказ' in h
_,h,_=req(a,'/admin/shop/orders');assert 'Gift QA' in h
print('PASS: full catalogue route with filters, constructor block, invalid product denied, client price ignored, server total, real checkout and order')
