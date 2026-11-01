"""Run only against a disposable, empty local installation."""
import http.cookiejar,json,re,secrets,sys,urllib.request,urllib.parse,urllib.error
base=sys.argv[1] if len(sys.argv)>1 else 'http://127.0.0.1:18082'
assert urllib.parse.urlparse(base).hostname in ('127.0.0.1','localhost'), 'Local disposable installation only'
def client(): return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
user=client();anon=client()
def req(op,path,values=None,body=None,token=None):
 headers={}
 if values is not None: body=urllib.parse.urlencode(values).encode()
 if isinstance(body,dict):body=json.dumps(body).encode();headers['Content-Type']='application/json'
 if token:headers['X-CSRF']=token
 try:
  r=op.open(urllib.request.Request(base+path,data=body,headers=headers));return r.status,r.read().decode(),r.url
 except urllib.error.HTTPError as e:return e.code,e.read().decode(),e.url
def token(html):return re.search(r'name="csrf" value="([^"]+)"',html)[1]
status,html,url=req(user,'/admin/install');assert status==200 and 'name="site_title"' in html
pwd=secrets.token_urlsafe(24)
status,html,url=req(user,'/admin/install',values={'csrf':token(html),'site_title':'Магазин №2 — проверка','site_lang':'ru','ui_lang':'ru','login':'qa-admin','email':'qa@example.invalid','password':pwd,'password2':pwd})
assert status==200 and 'id="boot"' in html,(status,url)
boot=json.loads(re.search(r'<script[^>]*id="boot"[^>]*>(.*?)</script>',html,re.S)[1]);csrf=boot['csrf']
assert req(anon,'/admin/api/site')[0]==401
assert req(user,'/admin/api/save',body={})[0]==403
status,body,_=req(user,'/admin/api/site');assert status==200
site=json.loads(body)['site'];assert not site.get('blocks'), 'Unexpected demo content'
status,body,_=req(user,'/admin/api/save',body={'site':site},token=csrf);assert status==200 and json.loads(body)['ok'],body
assert req(user,'/admin/install')[2].endswith('/admin')
assert req(user,'/')[0]==200
assert req(user,'/missing-qa-page')[0]==404
req(user,'/admin/logout',values={'csrf':csrf});assert req(user,'/admin/api/site')[0]==401
status,html,_=req(user,'/admin');csrf=token(html)
status,html,_=req(user,'/admin/login',values={'csrf':csrf,'login':'qa-admin','password':'intentionally-wrong'})
assert 'role="alert"' in html and req(user,'/admin/api/site')[0]==401
status,html,url=req(user,'/admin/login',values={'csrf':token(html),'login':'qa-admin','password':pwd})
assert status==200 and 'id="boot"' in html
print('PASS: fresh install, automatic migrations, no demo, authenticated editor, valid save, closed installer, public page, 404, anonymous API denial, CSRF rejection, logout, wrong-password denial, successful re-login')
