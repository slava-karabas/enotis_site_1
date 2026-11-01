<?php
declare(strict_types=1);
$dir=sys_get_temp_dir().'/enotis-contacts-'.bin2hex(random_bytes(8)); mkdir($dir,0700,true); putenv('ENOTIS_DATA='.$dir);
require dirname(__DIR__).'/app/bootstrap.php';
function check(bool $ok,string $why):void { if(!$ok)throw new RuntimeException($why); }
foreach (['javascript:alert(1)','data:text/html,x','//example.com','https://good.com\\@evil.com','https://u:p@example.com',"https://example.com\nfoo",'mailto:a@example.com?subject=x','https://example.com/%0aX','https://instagram.com.evil.test/\"'] as $u) {
 $bad=false;try{panel_contact($u);}catch(InvalidArgumentException $e){$bad=true;}check($bad,'unsafe destination: '.$u);
}
check(panel_contact('https://instagram.com.evil.test/a')['type']==='site','domain suffix spoof');
check(panel_contact('https://www.instagram.com/place/')['type']==='instagram','social type');
check(panel_contact('+995 (555) 12-34-56')['href']==='tel:+995555123456','telephone');
check(panel_contact('info@example.com')['href']==='mailto:info@example.com','mail');
check(panel_contact('https://example.com/contact')['type']==='site','unknown public site');
$cfg=['kind'=>'contacts','items'=>[['value'=>'https://instagram.com/place/','label'=>'Епархия <script>'],['value'=>'+995 555 12 34 56','label'=>''],['value'=>'info@example.com','label'=>'Почта партнёра']]];
check(panel_extension(json_decode(json_encode($cfg),true))===$cfg,'JSON config roundtrip');
foreach ([['kind'=>'contacts','items'=>[['value'=>'','label'=>'orphan']]],['kind'=>'contacts','items'=>array_fill(0,51,['value'=>'info@example.com'])]] as $badcfg){$bad=false;try{panel_extension($badcfg);}catch(InvalidArgumentException $e){$bad=true;}check($bad,'invalid config accepted');}
$bad=false;try{panel_extension($cfg,'tile');}catch(InvalidArgumentException $e){$bad=true;}check($bad,'contact extension on a tile');
db();$site=site_export();$site['blocks']=[['id'=>'new-c','type'=>'text','hidden'=>false,'title'=>['ru'=>'Контакты'],'extension'=>$cfg,'text'=>[]]];
check(!site_validate($site)['errors'],'contacts validate');
$site['footer']['telegram']='https://t.me/enotis_example';
$pdo=db();$pdo->beginTransaction();site_write($site);$pdo->commit();
$back=site_export();check($back['blocks'][0]['extension']===$cfg,'database save/export');
check($back['footer']['telegram']==='https://t.me/enotis_example','separate messenger Telegram save/export');
$invalid=$back;$invalid['footer']['telegram']='javascript:alert(1)';check((bool)site_validate($invalid)['errors'],'unsafe messenger Telegram rejected');
$invalid['footer']['telegram']='https://t.me.evil.test/example';check((bool)site_validate($invalid)['errors'],'spoofed messenger Telegram rejected');
$cfg['items']=array_reverse($cfg['items']);$cfg['items'][0]['label']='Изменено';array_pop($cfg['items']);
$back['blocks'][0]['extension']=$cfg;$pdo->beginTransaction();site_write($back);$pdo->commit();
check(site_export()['blocks'][0]['extension']===$cfg,'edit/reorder/delete');
$zip=export_zip(false,null);check(is_string($zip),'ZIP export');
$result=import_zip_site($zip);check(!empty($result['ok']),'ZIP import: '.json_encode($result));
$back=site_export();check($back['blocks'][0]['extension']===$cfg,'ZIP import preserves contacts');
$block=$back['blocks'][0];$block['title']='Контакты';$config=$cfg;
$html=view('blocks/contacts',['b'=>$block,'config'=>$config]);check(str_contains($html,'tel:+995555123456')&&str_contains($html,'mailto:info@example.com'),'real contact renderer');
$html=view('blocks/contacts',['b'=>$block,'config'=>['items'=>[['value'=>'https://example.com','label'=>'<script>']]]]);check(!str_contains($html,'<script>')&&str_contains($html,'&lt;script&gt;'),'safe label');
check(str_contains(view('blocks/contacts',['b'=>$block,'config'=>['items'=>[]]]),t('contacts.empty')),'empty contacts message');
$n=['hidden'=>0,'type'=>'text','panel_json'=>json_encode(['kind'=>'contacts','items'=>[]]),'title'=>'Контакты'];check(is_visible($n),'empty contacts must remain visible in navigation');
ob_start();panel_description("Описание\n## Святыни\n<script>not markup</script>");$html=ob_get_clean();check(str_contains($html,'<h2 class="tpage__subheading">Святыни</h2>')&&!str_contains($html,'<script>'),'safe shrine subheading');
$site['blocks'][0]['extension']['items'][0]['value']='javascript:alert(1)';check((bool)site_validate($site)['errors'],'server tree validation');
echo 'PASS '.PANEL_NUMBER.': contact security, automatic types, validation, save/edit/order/delete, JSON+ZIP round-trip, actual rendering, empty visibility and safe description'.PHP_EOL;
