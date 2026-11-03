-- Adopt the approved presentation only for the existing missionary installation.
-- Existing editorial content, photos, accounts, contacts and explicit settings stay intact.
INSERT OR IGNORE INTO settings (key, value)
SELECT 'presentation', '{"menu_home":"Главная","menu_about":"О проекте","menu_sections":"1","page_layout":"editorial","description":"Три волны проповеди в Грузии: апостол Андрей, равноапостольная Нина и тринадцать сирийских отцов.","social_heading":"Смотрите и подписывайтесь","contact_heading":"Для связи с проектом","copyright":"© {year} Паломник по Грузии","social_networks":"youtube,instagram,tiktok,telegram","messenger_networks":"whatsapp,viber,telegram"}'
WHERE EXISTS (SELECT 1 FROM settings_i18n WHERE key = 'site_title' AND lang = 'ru' AND value = 'Паломник по Грузии')
  AND (SELECT COUNT(DISTINCT slug) FROM elements WHERE type = 'tile' AND slug IN ('apostol-andrey','svyataya-nina','sirijskie-otcy')) = 3;

UPDATE settings SET value = 'on'
WHERE key = 'header_burger' AND value = 'auto'
  AND EXISTS (SELECT 1 FROM settings_i18n WHERE key = 'site_title' AND lang = 'ru' AND value = 'Паломник по Грузии')
  AND (SELECT COUNT(DISTINCT slug) FROM elements WHERE type = 'tile' AND slug IN ('apostol-andrey','svyataya-nina','sirijskie-otcy')) = 3;
