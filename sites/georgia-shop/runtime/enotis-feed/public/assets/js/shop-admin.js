'use strict';
const form=document.querySelector('[data-edit]');
if(form){
 const upload=form.querySelector('[data-photo-upload]'), select=form.querySelector('[name="photo_id"]'), preview=form.querySelector('[data-photo-preview]');
 const photoSource=form.querySelector('[name="photo_source"]');
 const show=()=>{if(preview){preview.hidden=!select.value;if(select.value)preview.src='/admin/api/original?id='+encodeURIComponent(select.value)+(photoSource?.value?'&src='+(photoSource.value==='processed'?'proc':'orig'):'')+'&r='+Date.now();}};
 select?.addEventListener('change',()=>{if(photoSource)photoSource.value='';show();});
 let dirty=false;const changed=()=>{dirty=true;const state=document.querySelector('[data-shop-status]');if(state){state.textContent='Есть несохранённые изменения';state.className='top__status is-dirty';}const cap=document.querySelector('[data-preview-caption]');if(cap)cap.textContent='Сохраните изменения для обновления предпросмотра.';};form.addEventListener('input',changed);form.addEventListener('change',changed);
 form.addEventListener('submit',()=>dirty=false);
 window.addEventListener('beforeunload',e=>{if(dirty){e.preventDefault();e.returnValue='';}});
 form.querySelector('[data-photo-original]')?.addEventListener('click',()=>{if(!select?.value)return;photoSource.value='original';show();changed();form.querySelector('[data-photo-message]').textContent='Выбран оригинал. Сохраните карточку.';});
 form.querySelector('[data-photo-process]')?.addEventListener('click',async e=>{
  const button=e.currentTarget,msg=form.querySelector('[data-photo-message]');if(!select?.value){msg.textContent='Сначала загрузите или выберите фото.';return;}
  const id=select.value;button.disabled=true;msg.textContent='ИИ обрабатывает фото…';
  try{
   const mode=form.querySelector('[data-photo-mode]').value;
   const response=await fetch('/admin/api/photo_process',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF':form.elements.csrf.value},body:JSON.stringify({id:Number(id),mode,aspect:'4:5',again:true,copy:true})});
   const result=await response.json();if(!response.ok||!result.ok)throw Error();
   const dialog=document.createElement('dialog');dialog.className='shop-photo-compare';dialog.setAttribute('aria-label','Сравнение фотографии');
   dialog.innerHTML='<h2>Сравните результат</h2><div class="shop-photo-pair"><figure><img alt="Оригинал"><figcaption>Оригинал</figcaption></figure><figure><img alt="После ИИ-обработки"><figcaption>ИИ-обработка</figcaption></figure></div><div class="actions"><button type="button" data-use class="button primary">Использовать результат</button><button type="button" data-cancel class="button">Оставить без изменений</button></div>';
   const images=dialog.querySelectorAll('img');images[0].src='/admin/api/original?id='+encodeURIComponent(id)+'&src=orig';images[1].src='/admin/api/original?id='+encodeURIComponent(result.copy_id)+'&src=proc&r='+Date.now();
   const close=()=>{dialog.close();dialog.remove();button.focus();};dialog.querySelector('[data-cancel]').onclick=close;dialog.addEventListener('cancel',()=>{dialog.remove();button.focus();});
   dialog.querySelector('[data-use]').onclick=()=>{if(select.value===id){select.add(new Option('#'+result.copy_id+' · ИИ-обработка',result.copy_id,true,true));photoSource.value='processed';show();changed();msg.textContent='Выбран результат ИИ. Сохраните карточку; оригинал остаётся доступен.';}close();};
   document.body.appendChild(dialog);dialog.showModal();msg.textContent='Обработка завершена. Сравните результат.';
  }catch{msg.textContent='Обработка не удалась. Проверьте настройки сервиса и попробуйте ещё раз. Оригинал сохранён.';}
  finally{button.disabled=false;}
 });
 upload?.addEventListener('change',async()=>{
  const file=upload.files[0],msg=form.querySelector('[data-photo-message]'),save=form.querySelector('[type="submit"]');if(!file)return;
  if(file.size>20*1024*1024){msg.textContent='Файл больше 20 МБ.';upload.value='';return;}
  save.disabled=true;const topSave=document.querySelector('[data-top-save]');if(topSave)topSave.disabled=true;msg.textContent='Загрузка фотографии…';
  try{const data=new FormData();data.append('file',file);data.append('aspect','4:5');const r=await fetch('/admin/api/photo',{method:'POST',headers:{'X-CSRF':form.elements.csrf.value},body:data});const j=await r.json();if(!r.ok||!j.photo?.id)throw Error();const o=new Option('#'+j.photo.id+' · '+file.name,j.photo.id,true,true);select.add(o);if(photoSource)photoSource.value='';show();changed();msg.textContent='Фото загружено. Сохраните запись, чтобы привязать его.';}
  catch{msg.textContent='Не удалось загрузить фото. Проверьте формат и попробуйте ещё раз.';}
  finally{save.disabled=false;if(topSave)topSave.disabled=false;upload.value='';}
 });
}

let previewSize=1280;
const fitPreview=()=>{const frame=document.querySelector('[data-shop-preview]'),stage=frame?.parentElement;if(!frame||!stage)return;const scale=Math.min(1,(stage.clientWidth-20)/previewSize);frame.style.position='absolute';frame.style.width=previewSize+'px';frame.style.height=Math.max(600,(stage.clientHeight-20)/scale)+'px';frame.style.left='50%';frame.style.top='10px';frame.style.transformOrigin='top center';frame.style.transform='translateX(-50%) scale('+scale+')';stage.style.position='relative';stage.style.overflow='hidden';};
document.querySelectorAll('[data-preview-size]').forEach(button=>button.addEventListener('click',()=>{document.querySelectorAll('[data-preview-size]').forEach(b=>b.classList.toggle('is-on',b===button));previewSize={wide:1280,tablet:768,phone:390}[button.dataset.previewSize];fitPreview();}));
if(document.querySelector('[data-shop-preview]')){new ResizeObserver(fitPreview).observe(document.querySelector('.shop-preview-stage'));fitPreview();}
document.addEventListener('keydown',e=>{if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='s'&&form){e.preventDefault();form.requestSubmit();}});
