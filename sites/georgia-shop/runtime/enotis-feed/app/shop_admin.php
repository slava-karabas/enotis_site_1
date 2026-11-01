<?php
declare(strict_types=1);

/** Server-rendered catalogue editor. Every SQL identifier comes from this fixed schema. */
function shop_types(): array
{
    return [
        'countries' => ['Страны', 'shop_countries', 'shop_country_i18n', 'country_id', ['code','position'], ['name']],
        'monasteries' => ['Монастыри', 'shop_monasteries', 'shop_monastery_i18n', 'monastery_id', ['country_id','slug','photo_id','status','position'], ['name','description']],
        'products' => ['Каталог', 'shop_products', 'shop_product_i18n', 'product_id', ['category','monastery_id','sku','slug','beverage_type','color','sweetness','vintage','alcohol_basis_points','volume_ml','price_minor','photo_id','status','available','collectible','position'], ['name','grape','description']],
        'gifts' => ['Подарки', 'shop_gifts', 'shop_gift_i18n', 'gift_id', ['slug','packaging_id','photo_id','status'], ['name','description']],
        'options' => ['Упаковка и оформление', 'shop_gift_options', 'shop_gift_option_i18n', 'option_id', ['kind','price_minor','active'], ['name']],
    ];
}
function shop_query(string $sql, array $args = []): array
{
    $st = db()->prepare($sql); $st->execute($args); return $st->fetchAll();
}
function shop_one(string $table, int $id): ?array
{
    return shop_query("SELECT * FROM $table WHERE id = ?", [$id])[0] ?? null;
}
function shop_choices(string $type, string $lang): array
{
    $c = shop_types()[$type];
    $translated=implode(", ",array_map(fn($k)=>$k==='name'?"COALESCE(t.name, f.name, '#' || b.id) AS name":"COALESCE(t.$k, f.$k, '') AS $k",$c[5]));
    return shop_query("SELECT b.*, $translated FROM {$c[1]} b
        LEFT JOIN {$c[2]} t ON t.{$c[3]}=b.id AND t.lang=?
        LEFT JOIN {$c[2]} f ON f.{$c[3]}=b.id AND f.lang=? ORDER BY name, b.id", [$lang, default_lang()]);
}
function shop_scalar(array $in, string $key): string
{
    if (!isset($in[$key])) { return ''; }
    if (!is_scalar($in[$key])) { throw new InvalidArgumentException('Некорректное значение поля «'.$key.'».'); }
    return trim((string) $in[$key]);
}
function shop_integer(string $value, string $label, int $min, int $max): int
{
    if (!preg_match('/^\d{1,10}$/D', $value) || (int)$value < $min || (int)$value > $max) {
        throw new InvalidArgumentException($label.': введите целое число от '.$min.' до '.$max.'.');
    }
    return (int)$value;
}
function shop_decimal(string $value, string $label, int $max): int
{
    $value = str_replace(',', '.', $value);
    if (!preg_match('/^(\d{1,8})(?:\.(\d{1,2}))?$/D', $value, $m)) {
        throw new InvalidArgumentException($label.': введите число с точностью до двух знаков.');
    }
    $n = (int)$m[1]*100 + (int)str_pad($m[2] ?? '', 2, '0');
    if ($n > $max) { throw new InvalidArgumentException($label.': значение слишком велико.'); }
    return $n;
}
function shop_currency(?string $code = null): string { $code ??= (string)setting('currency','EUR'); return in_array($code,['EUR','GEL','RUB','USD'],true) ? $code : 'EUR'; }
function shop_money(int $minor, ?string $currency = null): string { $currency=shop_currency($currency);$symbol=['EUR'=>'€','GEL'=>'₾','RUB'=>'₽','USD'=>'$'][$currency];return number_format($minor/100,2,',',' ').' '.$symbol; }
function shop_decimal_input(int $minor): string { return sprintf('%d.%02d', intdiv($minor,100), $minor%100); }
function shop_validate(string $type, array $in, string $lang): array
{
    if($type==='products'&&array_key_exists('producer_name',$in)) {
        $producer=shop_scalar($in,'producer_name');$country=shop_scalar($in,'country_name');$code=strtoupper(shop_scalar($in,'country_code'));
        if($producer===''||$country===''||mb_strlen($producer)>160||mb_strlen($country)>160||!preg_match('/^[A-Z]{2}$/D',$code))throw new InvalidArgumentException('Заполните производителя, страну и двухбуквенный код страны.');
        $row=shop_query('SELECT id FROM shop_countries WHERE code=?',[$code])[0]??null;
        if($row)$countryId=(int)$row['id'];else{db()->prepare('INSERT INTO shop_countries(code,position) VALUES(?,0)')->execute([$code]);$countryId=(int)db()->lastInsertId();}
        db()->prepare('INSERT INTO shop_country_i18n(country_id,lang,name) VALUES(?,?,?) ON CONFLICT(country_id,lang) DO UPDATE SET name=excluded.name')->execute([$countryId,$lang,$country]);
        $slug='producer-'.substr(hash('sha256',$code.'|'.$producer),0,20);$row=shop_query('SELECT m.id FROM shop_monasteries m JOIN shop_monastery_i18n t ON t.monastery_id=m.id WHERE m.country_id=? AND t.name=? ORDER BY m.id LIMIT 1',[$countryId,$producer])[0]??null;
        $row??=shop_query('SELECT id FROM shop_monasteries WHERE slug=?',[$slug])[0]??null;
        if($row)$producerId=(int)$row['id'];else{db()->prepare("INSERT INTO shop_monasteries(country_id,slug,status,position) VALUES(?,?,'published',0)")->execute([$countryId,$slug]);$producerId=(int)db()->lastInsertId();}
        db()->prepare('INSERT INTO shop_monastery_i18n(monastery_id,lang,name,description) VALUES(?,?,?,?) ON CONFLICT(monastery_id,lang) DO UPDATE SET name=excluded.name')->execute([$producerId,$lang,$producer,'']);$in['monastery_id']=(string)$producerId;
    }
    $c = shop_types()[$type]; $v = [];
    foreach (array_merge($c[4], $c[5]) as $key) {
        $s = shop_scalar($in, $key);
        if (in_array($key, ['name','description','grape','sku','slug','code','category'], true)) {
            $max = $key==='description' ? 6000 : ($key==='slug' ? 80 : 160);
            if (mb_strlen($s)>$max) { throw new InvalidArgumentException('Поле «'.$key.'»: максимум '.$max.' знаков.'); }
            if (in_array($key,['name','sku','slug','code'],true) && $s==='') { throw new InvalidArgumentException('Заполните название, код и обязательные поля.'); }
            if ($key==='slug' && !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',$s)) { throw new InvalidArgumentException('Адрес: только латинские буквы в нижнем регистре, цифры и дефис.'); }
            if ($key==='code') { $s=strtoupper($s); if (!preg_match('/^[A-Z]{2}$/D',$s)) { throw new InvalidArgumentException('Код страны: две латинские буквы, например GE.'); } }
            $v[$key]=$s;
        } elseif (in_array($key,['available','collectible','active'],true)) {
            if ($s!=='' && $s!=='1') { throw new InvalidArgumentException('Некорректный переключатель.'); }
            $v[$key]=$s==='1' ? 1 : 0;
        } elseif ($key==='price_minor') {
            $v[$key]=shop_decimal(shop_scalar($in,'price'),'Цена',999999999);
        } elseif ($key==='alcohol_basis_points') {
            $s=shop_scalar($in,'alcohol'); $v[$key]=$s==='' ? null : shop_decimal($s,'Крепость',10000);
        } elseif (in_array($key,['position','vintage','volume_ml'],true)) {
            $v[$key]=$s==='' && $key!=='position' ? null : shop_integer($s===''?'0':$s, ['position'=>'Порядок','vintage'=>'Год урожая','volume_ml'=>'Объём, мл'][$key],$key==='vintage'?1800:($key==='volume_ml'?1:0),$key==='vintage'?2200:1000000);
        } elseif (in_array($key,['country_id','monastery_id','photo_id','packaging_id'],true)) {
            if ($s==='' && in_array($key,['photo_id','packaging_id'],true)) { $v[$key]=null; continue; }
            $id=shop_integer($s,'Связанный объект',1,2147483647);
            $table=['country_id'=>'shop_countries','monastery_id'=>'shop_monasteries','photo_id'=>'photos','packaging_id'=>'shop_gift_options'][$key];
            $ref=shop_one($table,$id);
            if (!$ref || ($key==='packaging_id' && $ref['kind']!=='packaging')) { throw new InvalidArgumentException('Выберите существующий объект в связанном поле.'); }
            $v[$key]=$id;
        } else {
            $allowed=['status'=>['draft','published','archived'],'beverage_type'=>['wine','fortified','spirit'],'color'=>['','red','white','rose','amber'],'sweetness'=>['','dry','semi_dry','semi_sweet','sweet'],'kind'=>['packaging','extra']][$key];
            if (!in_array($s,$allowed,true)) { throw new InvalidArgumentException('Выберите допустимое значение поля «'.$key.'».'); }
            $v[$key]=$s===''?null:$s;
        }
    }
    if ($type==='options' && !empty($in['id'])) {
        $old=shop_one('shop_gift_options',(int)$in['id']);
        if ($old && $old['kind']!==$v['kind']) { throw new InvalidArgumentException('Тип существующего оформления менять нельзя. Создайте новую запись.'); }
    }
    return $v;
}
function shop_gift_relations(array $in, bool $published): array
{
    $items=$in['items']??[]; $extras=$in['extras']??[];
    if (!is_array($items)||!is_array($extras)||count($items)>500||count($extras)>500) { throw new InvalidArgumentException('Некорректный состав подарка.'); }
    $selected=[];
    foreach ($items as $key=>$qty) {
        if (!is_scalar($qty)) { throw new InvalidArgumentException('Некорректное количество бутылок.'); }
        if (trim((string)$qty)==='' || (string)$qty==='0') { continue; }
        $id=shop_integer((string)$key,'Позиция',1,2147483647); $q=shop_integer((string)$qty,'Количество',1,99);
        $p=shop_one('shop_products',$id);
        if (!$p || ($published && ($p['status']!=='published'||!$p['available']))) { throw new InvalidArgumentException('В опубликованный подарок можно включить только опубликованные доступные вина.'); }
        $selected[$id]=$q;
    }
    if (!$selected) { throw new InvalidArgumentException('Добавьте в подарок хотя бы один товар.'); }
    $chosen=[];
    foreach ($extras as $key=>$flag) {
        if ($flag!=='1') { throw new InvalidArgumentException('Некорректное оформление подарка.'); }
        $id=shop_integer((string)$key,'Дополнение',1,2147483647);$o=shop_one('shop_gift_options',$id);
        if (!$o||$o['kind']!=='extra'||($published&&!$o['active'])) { throw new InvalidArgumentException('Выберите доступное дополнительное оформление.'); }
        $chosen[$id]=true;
    }
    return [$selected,$chosen];
}
function shop_save(string $type, array $in, string $lang, int $id): int
{
    $c=shop_types()[$type]; $pdo=db(); $pdo->beginTransaction();
    try {
        if ($id && !shop_one($c[1],$id)) { throw new InvalidArgumentException('Запись не найдена.'); }
        $v=shop_validate($type,$in,$lang);
        $relations=null;
        if ($type==='gifts') {
            $published=$v['status']==='published';$relations=shop_gift_relations($in,$published);
            if ($published && $v['packaging_id'] && !shop_one('shop_gift_options',$v['packaging_id'])['active']) { throw new InvalidArgumentException('Выбранная упаковка отключена.'); }
        }
        if ($type==='products' && $v['status']==='published' && shop_one('shop_monasteries',$v['monastery_id'])['status']!=='published') { throw new InvalidArgumentException('Сначала опубликуйте монастырь этого вина.'); }
        $base=array_intersect_key($v,array_flip($c[4]));
        if ($type==='products') $base['currency']=shop_currency();
        if ($id) {
            $st=$pdo->prepare('UPDATE '.$c[1].' SET '.implode(', ',array_map(fn($k)=>$k.'=?',array_keys($base))).' WHERE id=?');
            $st->execute([...array_values($base),$id]);
        } else {
            $st=$pdo->prepare('INSERT INTO '.$c[1].' ('.implode(',',array_keys($base)).') VALUES ('.implode(',',array_fill(0,count($base),'?')).')');
            $st->execute(array_values($base));$id=(int)$pdo->lastInsertId();
        }
        $cols=[$c[3],'lang',...$c[5]];
        $st=$pdo->prepare('INSERT INTO '.$c[2].' ('.implode(',',$cols).') VALUES ('.implode(',',array_fill(0,count($cols),'?')).') ON CONFLICT('.$c[3].',lang) DO UPDATE SET '.implode(',',array_map(fn($k)=>$k.'=excluded.'.$k,$c[5])));
        $st->execute([$id,$lang,...array_map(fn($k)=>$v[$k],$c[5])]);
        if ($relations) {
            $pdo->prepare('DELETE FROM shop_gift_products WHERE gift_id=?')->execute([$id]);
            $pdo->prepare('DELETE FROM shop_gift_extras WHERE gift_id=?')->execute([$id]);
            $st=$pdo->prepare('INSERT INTO shop_gift_products(gift_id,product_id,quantity) VALUES(?,?,?)');
            foreach ($relations[0] as $pid=>$qty) { $st->execute([$id,$pid,$qty]); }
            $st=$pdo->prepare('INSERT INTO shop_gift_extras(gift_id,option_id) VALUES(?,?)');
            foreach ($relations[1] as $oid=>$_) { $st->execute([$id,$oid]); }
        }
        // Applying a processed image is part of saving the card; the original remains available.
        if(!empty($v['photo_id'])&&array_key_exists('photo_source',$in)&&shop_scalar($in,'photo_source')!==''){
            $source=shop_scalar($in,'photo_source');if(!in_array($source,['original','processed'],true))throw new InvalidArgumentException('Неверный источник фото.');
            $photo=shop_one('photos',(int)$v['photo_id']);$use=$source==='processed';
            if($use&&!$photo['processed_path'])throw new InvalidArgumentException('Сначала выполните ИИ-обработку.');
            $file=ORIGINALS_DIR.'/'.($use?$photo['processed_path']:$photo['original_path']);$size=@getimagesize($file);
            if(!$size)throw new InvalidArgumentException('Файл фотографии недоступен.');
            [$crop]=suggest_crop($size[0],$size[1],'4:5',$use?($photo['process_mode']?:'enhance'):'enhance');
            write_photo(['id'=>(int)$v['photo_id'],'aspect'=>'4:5','use_processed'=>$use,'crop'=>$crop],'4:5',[]);
        }
        $pdo->commit();return $id;
    } catch (Throwable $e) { if ($pdo->inTransaction()) { $pdo->rollBack(); } throw $e; }
}
function shop_gift_total(int $id): int
{
    $sum=shop_query('SELECT COALESCE(SUM(p.price_minor*i.quantity),0) n FROM shop_gift_products i JOIN shop_products p ON p.id=i.product_id WHERE i.gift_id=?',[$id])[0]['n'];
    $sum+=shop_query('SELECT COALESCE(SUM(o.price_minor),0) n FROM shop_gift_extras e JOIN shop_gift_options o ON o.id=e.option_id WHERE e.gift_id=?',[$id])[0]['n'];
    $sum+=shop_query('SELECT COALESCE(o.price_minor,0) n FROM shop_gifts g LEFT JOIN shop_gift_options o ON o.id=g.packaging_id WHERE g.id=?',[$id])[0]['n']??0;
    return (int)$sum;
}
function shop_admin_route(string $path): void
{
    if (!in_array($_SERVER['REQUEST_METHOD'], ['GET','POST'], true)) { http_response_code(405);header('Allow: GET, POST');return; }
    if($path==='/admin/shop/settings'){panel_shop_settings();return;}
    if($path==='/admin/shop/orders'){shop_orders_route();return;}
    if($path==='/admin/shop/transfer'){shop_transfer_route();return;}
    $type=trim(substr($path,strlen('/admin/shop')),'/') ?: 'products';$types=shop_types();
    if (!isset($types[$type])) { http_response_code(404);echo 'Раздел не найден';return; }
    $c=$types[$type];$lang=is_string($_GET['lang']??null)&&in_array($_GET['lang'],LANGS,true)?$_GET['lang']:default_lang();
    $id=0;$error=null;$row=[];$editing=isset($_GET['new'])||isset($_GET['id'])||$_SERVER['REQUEST_METHOD']==='POST';
    try {
        if ($_SERVER['REQUEST_METHOD']==='POST') {
            admin_check_csrf(is_string($_POST['csrf']??null)?$_POST['csrf']:'');
            if (shop_scalar($_POST,'form_complete') !== '1') { throw new InvalidArgumentException('Форма передана не полностью. Обновите страницу и повторите сохранение.'); }
            $s=shop_scalar($_POST,'id');$id=$s===''?0:shop_integer($s,'Номер записи',1,2147483647);
            $id=shop_save($type,$_POST,$lang,$id);
            admin_redirect('/admin/shop/'.$type.'?id='.$id.'&lang='.$lang.'&saved=1');return;
        }
        if (isset($_GET['id'])) {
            $id=shop_integer(shop_scalar($_GET,'id'),'Номер записи',1,2147483647);
            $row=shop_one($c[1],$id)??[];
            if (!$row) { http_response_code(404);echo 'Запись не найдена';return; }
            $t=shop_query("SELECT * FROM {$c[2]} WHERE {$c[3]}=? AND lang=?",[$id,$lang])[0]??[];
            $row=array_merge($row,$t);
        }
    } catch (InvalidArgumentException $e) { http_response_code(422);$error=$e->getMessage();$row=$_POST; }
      catch (PDOException $e) { http_response_code(422);$error='Не удалось сохранить: такой код или адрес уже существует, либо связанная запись изменилась. Проверьте поля.';$row=$_POST;error_log('[shop-save] '.$e->getMessage()); }
    $q=is_string($_GET['q']??null)?mb_substr(trim($_GET['q']),0,120):'';
    $page=max(1,min(100000,is_scalar($_GET['page']??1)?(int)($_GET['page']??1):1));$offset=($page-1)*50;
    $all=shop_choices($type,$lang);
    if ($q!=='') { $all=array_values(array_filter($all,fn($r)=>mb_stripos($r['name'],$q)!==false||mb_stripos((string)($r['sku']??$r['code']??''),$q)!==false)); }
    $total=count($all);$rows=array_slice($all,$offset,50);
    $photos=shop_query('SELECT id, original_path FROM photos ORDER BY id DESC LIMIT 300');
    if ($editing && !empty($row['photo_id']) && is_scalar($row['photo_id']) && !in_array((int)$row['photo_id'],array_map('intval',array_column($photos,'id')),true)) {
        $photo=shop_one('photos',(int)$row['photo_id']);if ($photo) { $photos[]=$photo; }
    }
    $items=[];$extras=[];
    if ($type==='gifts'&&$id) {
        $items=array_column(shop_query('SELECT product_id,quantity FROM shop_gift_products WHERE gift_id=?',[$id]),'quantity','product_id');
        $extras=array_fill_keys(array_column(shop_query('SELECT option_id FROM shop_gift_extras WHERE gift_id=?',[$id]),'option_id'),1);
    }
    if ($error) {$items=is_array($_POST['items']??null)?$_POST['items']:[];$extras=is_array($_POST['extras']??null)?$_POST['extras']:[];}
    echo view('admin/shop',compact('type','types','c','lang','id','error','row','editing','q','page','total','rows','photos','items','extras')+['csrf'=>$_SESSION['csrf']]);
}

/** Process a detached copy so a retry never replaces an already accepted image. */
function shop_photo_process_copy(int $id,string $mode):array {
    if(!in_array($mode,PHOTO_MODES,true))return ['ok'=>false,'error'=>'photo.svc_failed'];
    $photo=shop_one('photos',$id);if(!$photo)return ['ok'=>false,'error'=>'photo.svc_failed'];
    unset($photo['id']);$photo['processed_path']=null;$photo['process_mode']=null;$photo['use_processed']=0;
    $columns=array_keys($photo);db()->prepare('INSERT INTO photos ('.implode(',',$columns).') VALUES ('.implode(',',array_fill(0,count($columns),'?')).')')->execute(array_values($photo));
    $copy=(int)db()->lastInsertId();$result=photo_process($copy,$mode,'4:5',true);
    if(empty($result['ok'])){db()->prepare('DELETE FROM photos WHERE id=?')->execute([$copy]);return $result;}
    return $result+['copy_id'=>$copy];
}
