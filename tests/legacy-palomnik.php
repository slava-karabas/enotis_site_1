<?php
declare(strict_types=1);
// A real schema-1 database is upgraded in place; no import/replacement of content.
$dir = sys_get_temp_dir().'/palomnik-legacy-'.bin2hex(random_bytes(6));
mkdir($dir,0700,true);putenv('ENOTIS_DATA='.$dir);
$old = new PDO('sqlite:'.$dir.'/site.sqlite');
$old->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$old->exec(file_get_contents(dirname(__DIR__).'/app/migrations/001_init.sql'));
$old->exec("CREATE TABLE schema_version(version INTEGER NOT NULL, applied_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP); INSERT INTO schema_version(version) VALUES(1)");
$old->exec("UPDATE languages SET is_active = CASE WHEN code='ru' THEN 1 ELSE 0 END");
$old->exec("INSERT INTO settings_i18n VALUES('site_title','ru','Паломник по Грузии')");
$old->exec("INSERT OR REPLACE INTO settings VALUES('header_burger','auto'),('footer_show','1'),('maps','google'),('custom-preserved','untouched')");
$old->exec("INSERT INTO admins(id,login,password_hash,email) VALUES(1,'fixture-admin','fixture-hash','fixture@example.test')");
$old->exec("INSERT INTO photos(id,original_path,aspect,crop_x,crop_y,crop_w,crop_h) VALUES(44,'originals/fixture.jpg','3:2',0,0,1200,800)");
$old->exec("INSERT INTO elements(id,type,position,hidden,tiles_size,frame) VALUES(10,'tiles',1,0,'large','3:2')");
foreach (['apostol-andrey'=>'Апостол Андрей','svyataya-nina'=>'Святая Нина','sirijskie-otcy'=>'Тринадцать отцов'] as $slug=>$title) {
    $id=20+count($old->query("SELECT id FROM elements WHERE type='tile'")->fetchAll());
    $old->prepare("INSERT INTO elements(id,type,parent_id,position,hidden,slug,as_page,photo_id) VALUES(?,'tile',10,?,0,?,1,44)")->execute([$id,$id,$slug]);
    $old->prepare('INSERT INTO element_i18n(element_id,lang,title,subtitle,body) VALUES(?,\'ru\',?,\'Подпись\',\'Полный текст сохранён.\')')->execute([$id,$title]);
}
function snapshot(PDO $p): array {
    $out=[];
    foreach(['admins','photos'] as $t)$out[$t]=$p->query('SELECT * FROM '.$t)->fetchAll(PDO::FETCH_ASSOC);
    $out['element_i18n']=$p->query("SELECT * FROM element_i18n WHERE lang<>'en' ORDER BY element_id,lang")->fetchAll(PDO::FETCH_ASSOC);
    $out['elements']=$p->query('SELECT id,type,parent_id,position,hidden,slug,as_page,photo_id FROM elements ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    return $out;
}
$before=snapshot($old);$old=null;
require dirname(__DIR__).'/app/bootstrap.php';
function verify(bool $ok,string $why):void {if(!$ok)throw new RuntimeException($why);}
$p=db();verify(schema_version($p)===7,'schema 1 to 7');verify(snapshot($p)===$before,'legacy rows changed');
verify((bool)glob($dir.'/backups/*v001.sqlite'),'pre-migration full database backup');
verify(setting('custom-preserved')==='untouched','private/unrelated settings preserved');
verify(setting('header_burger')==='on','missionary menu enabled');
verify(presentation('menu_home')==='Главная' && presentation('page_layout')==='editorial','missionary defaults');
verify(count(presentation_navigation())===7,'home, three sections, about, social and contact');
$footer=view('footer');
verify(!str_contains($footer,'tel:'),'no invented phone');
verify(substr_count($footer,'class="ftr__brand"')===1 && str_contains($footer,'class="ftr__title"'),'brand is not duplicated');
foreach(['apostol-andrey','svyataya-nina','sirijskie-otcy']as $slug)verify(str_contains($footer,'/'.$slug),'missing section link');
$tile=node(20);$tile['photo']=null;
$page=view('page_tile',['tile'=>$tile,'tab'=>null,'blocks'=>[],'popup'=>null]);
verify(str_contains($page,'tpage--editorial')&&!str_contains($page,'Об обители'),'legacy layout retained');
verify(!site_validate(site_export())['errors'],'editable export after migration');
verify(resolve_page(['about'])['view']==='page_about','real about route');
verify(site_brand_src()==='/assets/brand/logo-af54c0ac9460.svg','canonical fallback brand');
$current=json_decode(json_encode(site_export()),true);verify(!empty($current['settings']['about_body']),'about exported');
$ruRows=$p->query("SELECT * FROM element_i18n WHERE lang='ru' ORDER BY element_id")->fetchAll();
$p->beginTransaction();site_write($current);$p->commit();
verify((int)$p->query("SELECT COUNT(*) FROM settings_i18n WHERE key='about_body'")->fetchColumn()===3,'about survives editor save');
verify(setting('palomnik_unified')==='1','style survives editor save');
$again=snapshot($p);migrate($p);verify(snapshot($p)===$again,'migration is idempotent');
verify(!$p->query('PRAGMA foreign_key_check')->fetchAll(),'foreign key integrity');
echo "PASS: schema-1 upgrade, full backup, immutable content/photos/admin, missionary layout/menu/footer, valid export, idempotence\n";
