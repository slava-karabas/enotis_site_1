"""Actual authenticated editor save/preview, on a disposable installation after admin-smoke."""
import os,copy,http.cookiejar,json,re,sys,urllib.error,urllib.parse,urllib.request,zipfile,io
base=sys.argv[1]; assert urllib.parse.urlparse(base).hostname in ('127.0.0.1','localhost')
a=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
def req(path,data=None,headers=None):
 if isinstance(data,dict):data=urllib.parse.urlencode(data).encode()
 try:
  r=a.open(urllib.request.Request(base+path,data=data,headers=headers or {}));return r.status,r.read()
 except urllib.error.HTTPError as e:return e.code,e.read()
h=req('/admin')[1].decode();csrf=re.search('name="csrf" value="([^"]+)"',h)[1]
h=req('/admin/login',dict(csrf=csrf,login=os.environ.get('ENOTIS_TEST_LOGIN','admin'),password=os.environ.get('ENOTIS_TEST_PASSWORD','correct-horse')))[1].decode();csrf=json.loads(re.search('<script[^>]*id="boot"[^>]*>(.*?)</script>',h,re.S)[1])['csrf']
site=json.loads(req('/admin/api/site')[1])['site']; original=copy.deepcopy(site)
contacts={'kind':'contacts','items':[{'value':'https://instagram.com/example/','label':'Епархия — пример'},{'value':'info@example.com','label':''}]}
block={'id':'contact-http','type':'text','title':{'ru':'Контакты'},'extension':contacts}
page={'id':'contact-page','title':{'ru':'Проверка контактов'},'slug':'contacts-check','as_page':True,'text':{'ru':'Описание\n## Святыни\nПодтверждённые сведения'},'blocks':[block]}
site['blocks'].insert(0,{'id':'contact-root','type':'tiles','kind':'normal','size':'large','tiles':[page]})
def post(path,site):return req(path,json.dumps({'site':site}).encode(),{'X-CSRF':csrf,'Content-Type':'application/json'})
st,out=post('/admin/api/preview',site);assert st==200 and json.loads(out)['ok']
st,out=req('/admin/preview?path=/contacts-check');h=out.decode();assert st==200 and 'Епархия — пример' in h and 'mailto:info@example.com' in h and 'tpage__subheading' in h
assert json.loads(req('/admin/api/site')[1])['site']==original,'preview leaked into saved database'
st,out=post('/admin/api/save',site);assert st==200 and json.loads(out)['ok'],out
h=req('/contacts-check')[1].decode();assert 'mailto:info@example.com' in h
assert h.index('<h1>')<h.index('id="place-about"') and '#block-' in h,'centred heading and real section anchors'
saved=json.loads(req('/admin/api/site')[1])['site'];assert saved['blocks'][0]['tiles'][0]['blocks'][0]['extension']==contacts
bad=copy.deepcopy(saved);bad['blocks'][0]['tiles'][0]['blocks'][0]['extension']['items'][0]['value']='javascript:alert(1)'
assert post('/admin/api/save',bad)[0]==422
assert json.loads(req('/admin/api/site')[1])['site']==saved,'invalid contact changed database'
st,raw=req('/admin/api/export');assert st==200
with zipfile.ZipFile(io.BytesIO(raw)) as z: portable=json.loads(z.read('site.json'))
assert portable['blocks'][0]['tiles'][0]['blocks'][0]['extension']==contacts
saved['blocks'][0]['tiles'][0]['blocks'][0]['extension']['items']=[]
assert post('/admin/api/save',saved)[0]==200
h=req('/contacts-check')[1].decode();assert 'contacts__surface' not in h
assert not re.search(r'<a href="#block-[^"]+">Контакты</a>',h),'empty contact navigation'
print('PASS: authenticated contact preview without persistence, editor save, export, unsafe-save rejection and empty contact block')
