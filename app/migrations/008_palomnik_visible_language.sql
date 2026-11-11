-- Only published, reachable content determines whether a language is complete.
-- Legacy payment blocks are unavailable in Admin 1 and must not block the public language.
WITH RECURSIVE published(id) AS (
 SELECT id FROM elements WHERE parent_id IS NULL AND hidden=0 AND type<>'pay'
 UNION ALL
 SELECT e.id FROM elements e JOIN published p ON e.parent_id=p.id WHERE e.hidden=0 AND e.type<>'pay'
)
UPDATE languages SET is_active=1
WHERE code='en' AND EXISTS(SELECT 1 FROM settings WHERE key='palomnik_unified' AND value='1')
AND NOT EXISTS (
 SELECT 1 FROM element_i18n r JOIN published p ON p.id=r.element_id
 LEFT JOIN element_i18n e ON e.element_id=r.element_id AND e.lang='en'
 WHERE r.lang='ru' AND (
  (COALESCE(r.title,'')<>'' AND COALESCE(e.title,'')='') OR
  (COALESCE(r.subtitle,'')<>'' AND COALESCE(e.subtitle,'')='') OR
  (COALESCE(r.body,'')<>'' AND COALESCE(e.body,'')='') OR
  (COALESCE(r.eyebrow,'')<>'' AND COALESCE(e.eyebrow,'')='') OR
  (COALESCE(r.place,'')<>'' AND COALESCE(e.place,'')='') OR
  (COALESCE(r.seo_title,'')<>'' AND COALESCE(e.seo_title,'')='') OR
  (COALESCE(r.seo_description,'')<>'' AND COALESCE(e.seo_description,'')='')
 )
);

UPDATE element_i18n SET body=replace(body,'pat ronage','patronage')
WHERE lang='en' AND body LIKE '%pat ronage%'
AND EXISTS(SELECT 1 FROM settings WHERE key='palomnik_unified' AND value='1');
