"""Run against an uninstalled extracted shop ZIP on local Apache only."""
import http.cookiejar,re,secrets,sys,urllib.request,urllib.parse,urllib.error
base=sys.argv[1] if len(sys.argv)>1 else 'http://127.0.0.1:18085'
assert urllib.parse.urlparse(base).hostname in ('localhost','127.0.0.1')
u=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
def req(p,data=None):
 if isinstance(data,dict):data=urllib.parse.urlencode(data).encode()
 try:
  r=u.open(urllib.request.Request(base+p,data=data));return r.status,r.read(),r.url
 except urllib.error.HTTPError as e:return e.code,e.read(),e.url
st,b,_=req('/');h=b.decode();assert st==200 and 'Тестовое вино' in h,(st,h[:400])
img=re.search(r'<img src="([^"]+)"',h)[1];st,b,_=req(img);assert st==200 and b[:2]==b'\xff\xd8'
st,b,_=req('/admin/install');h=b.decode();assert 'name="site_title"' in h,(st,h[:400]);pwd=secrets.token_urlsafe(24)
st,b,_=req('/admin/install',{'csrf':re.search(r'name="csrf" value="([^"]+)"',h)[1],'site_title':'Should not replace','site_lang':'en','ui_lang':'ru','login':'new-owner','email':'owner@example.invalid','password':pwd,'password2':pwd});assert st==200 and b'id="boot"' in b
assert 'Shop QA' in req('/')[1].decode()
assert 'Заявок пока нет' in req('/admin/shop/orders')[1].decode()
for p in ['/data/site.sqlite','/app/bootstrap.php','/content.json','/manifest.json','/.git/config','/bin/build-release.php']:
 assert req(p)[0] in (403,404),p
assert req('/sitemap.xml')[0]==200 and b'/wine/test-wine' in req('/sitemap.xml')[1]
assert req('/admin/install')[2].endswith('/admin')
print('PASS: extracted portable ZIP on clean Apache; catalogue and original photo regenerate; new administrator; transferred title preserved; no orders; private files denied; sitemap; installer closed')
