<?php
declare(strict_types=1);
function panel_nav(string $active=''): void {
    $groups=['Шапка'=>['header'=>'Шапка'],'Содержимое'=>['content'=>'Блоки и страницы']];
    if(PANEL_NUMBER==='2')$groups['Магазин']=['shop/products'=>'Каталог','shop/settings'=>'Категории и фильтры','shop/orders'=>'Заказы'];
    if(PANEL_PAYMENTS)$groups['Оплата']=['payment'=>'Услуги и пожертвования'];
    $groups['Подвал']=['footer'=>'Контакты и подпись','set:operators'=>'Организация и реквизиты','set:social'=>'Соцсети','set:docs'=>'Документы и Cookies'];
    $groups['Интеграции']=['integrations'=>'Уведомления, CRM и API','set:mail'=>'Отправка писем','set:advanced'=>'ИИ-обработка и аналитика'];
    $groups['Настройки сайта']=['set:main'=>'Основное','set:langs'=>'Языки сайта','set:design'=>'Иконка и обложка','set:transfer'=>'Перенос сайта','set:backups'=>'Резервные копии','set:update'=>'Обновление'];
    foreach($groups as $name=>$links){echo '<div class="grp"><div class="grp__h">'.e($name).'</div>';foreach($links as $id=>$label){$href=$id==='integrations'?'/admin/integrations':(str_starts_with($id,'shop/')?'/admin/'.$id:'/admin#'.rawurlencode($id));echo '<a class="row row--fixed '.($id===$active?'is-sel':'').'" href="'.e($href).'">'.e($label).'</a>';}echo '</div>';}
}
function panel_integrations(): void {
    $message='';$token='';$secret='';$error='';
    if(($_SERVER['REQUEST_METHOD']??'GET')==='POST') {
        admin_check_csrf($_POST['csrf']??'');
        try {
            $action=is_string($_POST['action']??null)?$_POST['action']:'';
            if($action==='save') {
                $email=trim(is_string($_POST['email']??null)?$_POST['email']:'');$webhook=trim(is_string($_POST['webhook']??null)?$_POST['webhook']:'');
                if($email&&!filter_var($email,FILTER_VALIDATE_EMAIL))throw new InvalidArgumentException('Проверьте адрес почты.');
                if(strlen($webhook)>2048)throw new InvalidArgumentException('Адрес слишком длинный.');
                if($webhook)integration_target($webhook);
                $pdo=db();$pdo->beginTransaction();
                foreach(['integration_email'=>$email,'integration_webhook'=>$webhook,'integration_api_read'=>isset($_POST['read'])?'1':'0','integration_api_write'=>isset($_POST['write'])?'1':'0'] as $k=>$v)set_setting($k,$v);
                if($webhook&&!setting('integration_webhook_secret')){$secret=bin2hex(random_bytes(32));set_setting('integration_webhook_secret',$secret);}
                $pdo->commit();$message='Настройки сохранены.';
            } elseif($action==='webhook_key') {
                $secret=bin2hex(random_bytes(32));set_setting('integration_webhook_secret',$secret);$message='Ключ подписи заменён. Передайте его получателю; прежний больше не действует.';
            } elseif($action==='token') {
                $token=bin2hex(random_bytes(32));set_setting('integration_token_hash',hash('sha256',$token));$message='Создан новый ключ. Старый отозван. Скопируйте ключ сейчас: повторно он не показывается.';
            } elseif($action==='revoke'){set_setting('integration_token_hash','');$message='Ключ отозван.';}
            elseif($action==='retry'){
                db()->prepare("UPDATE integration_outbox SET state='pending',next_at=0,lease_until=0,attempts=0 WHERE id=? AND state='failed'")->execute([is_string($_POST['id']??null)?$_POST['id']:'']);$message='Отправление поставлено в очередь. Его выполнит задача по расписанию.';
            } else throw new InvalidArgumentException('Неизвестное действие.');
        } catch(InvalidArgumentException $e){if(db()->inTransaction())db()->rollBack();http_response_code(422);$error=$e->getMessage();}
    }
    $cfg=integration_config();$csrf=$_SESSION['csrf'];
    echo view('admin/head',['ui'=>'ru','title'=>'Интеграции']);
    ?>
</head><body class="app"><header class="top"><div class="top__site">Панель №<?=e(PANEL_NUMBER)?></div><div class="top__status">Интеграции</div><div class="top__acts"><a class="btn" href="/admin">Редактор сайта</a><a class="btn" href="/" target="_blank" rel="noopener">Открыть сайт</a></div></header><div></div><main class="cols"><nav class="tree" aria-label="Разделы панели"><?php panel_nav('integrations'); ?></nav><section class="form"><div class="fhead"><div class="fhead__path">Интеграции</div><h2>Уведомления, CRM и API</h2></div>
<?php if($message):?><p class="note note--ok" role="status"><?=e($message)?></p><?php endif ?><?php if($error):?><p class="note note--err" role="alert"><?=e($error)?></p><?php endif ?>
<?php if($token):?><label class="fld"><span class="fld__l">Ключ API — показывается один раз</span><input readonly value="<?=e($token)?>" autocomplete="off"></label><?php endif ?>
<?php if($secret):?><label class="fld"><span class="fld__l">Ключ подписи webhook — скопируйте сейчас</span><input readonly value="<?=e($secret)?>" autocomplete="off"></label><?php endif ?>
<form method="post"><input type="hidden" name="csrf" value="<?=e($csrf)?>"><input type="hidden" name="action" value="save">
<h3 class="fsec">Уведомления</h3><label class="fld"><span class="fld__l">Почта владельца</span><input type="email" name="email" value="<?=e($cfg['email'])?>"></label><p class="hint">Пустое поле отключает уведомления. Сначала настройте отправку писем в соседнем подразделе.</p>
<h3 class="fsec">CRM и автоматизация</h3><label class="fld"><span class="fld__l">HTTPS webhook</span><input type="url" name="webhook" maxlength="2048" value="<?=e($cfg['webhook'])?>"></label><p class="hint">Получатель проверяет HMAC-подпись X-Enotis-Signature и исключает повтор события по event_id. При первом сохранении адреса ключ подписи показывается один раз. Передайте его получателю отдельно; в экспорт сайта он не попадает.</p>
<h3 class="fsec">API и доступы</h3><p class="hint">В этой версии ленты нет заказов: доступы подготовлены для последующих подключений. Рабочий API заказов доступен в панели №2.</p><label class="sw"><input type="checkbox" name="read" <?= $cfg['read']?'checked':'' ?>> Читать заказы</label><label class="sw"><input type="checkbox" name="write" <?= $cfg['write']?'checked':'' ?>> Менять статус обработки</label><p class="hint">API не меняет цены и не подтверждает оплату. Для запроса нужен отдельный ключ и HTTPS.</p><button class="btn btn--action">Сохранить</button></form>
<div class="ffoot"><form method="post"><input type="hidden" name="csrf" value="<?=e($csrf)?>"><button class="btn" name="action" value="webhook_key">Заменить ключ подписи</button> <button class="btn" name="action" value="token">Создать / заменить ключ API</button> <button class="btn btn--danger" name="action" value="revoke">Отозвать ключ</button></form></div>
<h3 class="fsec">Журнал обмена</h3><p class="hint">Отправка выполняется серверной задачей, без ожидания посетителя. До пяти попыток; неудачную доставку можно повторить.</p>
<?php $rows=db()->query('SELECT id,channel,state,attempts,created_at FROM integration_outbox ORDER BY created_at DESC,id LIMIT 50')->fetchAll();if(!$rows):?><p class="hint">Отправлений пока нет.</p><?php endif;foreach($rows as $r):?><div class="card"><b><?=e($r['channel']==='email'?'Почта':'CRM / автоматизация')?></b><p><?=e(['pending'=>'Ожидает отправки','sent'=>'Передано','failed'=>'Ошибка доставки'][$r['state']])?> · попыток: <?=(int)$r['attempts']?></p><small><?=e($r['created_at'])?></small><?php if($r['state']==='failed'):?><form method="post"><input type="hidden" name="csrf" value="<?=e($csrf)?>"><input type="hidden" name="id" value="<?=e($r['id'])?>"><button class="btn" name="action" value="retry">Повторить</button></form><?php endif ?></div><?php endforeach ?>
</section><section class="pv"><div class="pv__bar"><b>Предпросмотр сайта</b><a class="btn btn--small" href="/" target="_blank" rel="noopener">Открыть</a></div><p class="pv__cap">Сохранённый сайт. Интеграции — внутренние настройки.</p><iframe title="Предпросмотр сайта" src="/" style="width:100%;height:100%;border:1px solid #D9E1E6;border-radius:10px;background:white"></iframe></section></main><footer class="foot">Энотис · <?=e(ENGINE_VERSION)?> · подключения действуют только после настройки получателя и задачи по расписанию.</footer></body></html>
<?php }
