-- Restore payment capabilities for the approved product №1.2.
DROP TRIGGER IF EXISTS no_pay_element_insert;
DROP TRIGGER IF EXISTS no_pay_element_update;
DROP TRIGGER IF EXISTS no_pay_text_insert;
DROP TRIGGER IF EXISTS no_pay_text_update;
DROP TRIGGER IF EXISTS no_pay_setting_insert;
DROP TRIGGER IF EXISTS no_pay_setting_update;
CREATE TABLE pay_methods (
  id INTEGER PRIMARY KEY,
  element_id INTEGER NOT NULL REFERENCES elements(id) ON DELETE CASCADE,
  position INTEGER NOT NULL,
  kind TEXT NOT NULL CHECK (kind IN ('link','qr','requisites')),
  provider TEXT, enabled INTEGER NOT NULL DEFAULT 1,
  url TEXT, qr_path TEXT, req_json TEXT
);
CREATE TABLE pay_method_i18n (
  method_id INTEGER NOT NULL REFERENCES pay_methods(id) ON DELETE CASCADE,
  lang TEXT NOT NULL REFERENCES languages(code), label TEXT,
  PRIMARY KEY (method_id, lang)
);

