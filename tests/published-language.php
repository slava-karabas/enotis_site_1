<?php
$dir=sys_get_temp_dir().'/published-language-'.bin2hex(random_bytes(6));mkdir($dir);putenv('ENOTIS_DATA='.$dir);require dirname(__DIR__).'/app/bootstrap.php';
$p=db();$p->exec("INSERT INTO settings(key,value)VALUES('palomnik_unified','1');UPDATE languages SET is_active=0 WHERE code='en';INSERT INTO elements(id,type,position,hidden)VALUES(1,'text',1,0),(2,'pay',2,0),(3,'text',3,1);INSERT INTO elements(id,type,parent_id,position,hidden)VALUES(4,'text',3,1,0);INSERT INTO element_i18n(element_id,lang,title)VALUES(1,'ru','Открытый'),(1,'en','Published'),(2,'ru','Старый платёж'),(3,'ru','Скрытый'),(4,'ru','В скрытом разделе')");
$sql=file_get_contents(APP_DIR.'/migrations/008_palomnik_visible_language.sql');$p->exec($sql);
if((int)$p->query("SELECT is_active FROM languages WHERE code='en'")->fetchColumn()!==1)throw new Exception('Unpublished legacy content blocked complete translation');
$p->exec("UPDATE languages SET is_active=0 WHERE code='en';INSERT INTO elements(id,type,position,hidden)VALUES(5,'text',5,0);INSERT INTO element_i18n(element_id,lang,title)VALUES(5,'ru','Непереведённый')");$p->exec($sql);
if((int)$p->query("SELECT is_active FROM languages WHERE code='en'")->fetchColumn()!==0)throw new Exception('Incomplete public language enabled');
echo "PASS: language completeness checks published content, excludes hidden descendants and unavailable legacy payment blocks\n";
