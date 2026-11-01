-- Independent product: no payment storage or write paths.
DELETE FROM elements WHERE type = 'pay';
DROP TABLE pay_method_i18n;
DROP TABLE pay_methods;
UPDATE elements SET link_url = NULL;
UPDATE element_i18n SET button_label = NULL, recipient = NULL, pay_purpose = NULL;
DELETE FROM settings WHERE key IN ('req_format','launch_payment_checked','update_latest','update_checked_at');
CREATE TRIGGER no_pay_element_insert BEFORE INSERT ON elements WHEN NEW.type = 'pay' OR NEW.link_url IS NOT NULL BEGIN SELECT RAISE(ABORT, 'Payments unavailable'); END;
CREATE TRIGGER no_pay_element_update BEFORE UPDATE ON elements WHEN NEW.type = 'pay' OR NEW.link_url IS NOT NULL BEGIN SELECT RAISE(ABORT, 'Payments unavailable'); END;
CREATE TRIGGER no_pay_text_insert BEFORE INSERT ON element_i18n WHEN NEW.button_label IS NOT NULL OR NEW.recipient IS NOT NULL OR NEW.pay_purpose IS NOT NULL BEGIN SELECT RAISE(ABORT, 'Payments unavailable'); END;
CREATE TRIGGER no_pay_text_update BEFORE UPDATE ON element_i18n WHEN NEW.button_label IS NOT NULL OR NEW.recipient IS NOT NULL OR NEW.pay_purpose IS NOT NULL BEGIN SELECT RAISE(ABORT, 'Payments unavailable'); END;
CREATE TRIGGER no_pay_setting_insert BEFORE INSERT ON settings WHEN NEW.key IN ('req_format','launch_payment_checked') BEGIN SELECT RAISE(ABORT, 'Payments unavailable'); END;
CREATE TRIGGER no_pay_setting_update BEFORE UPDATE ON settings WHEN NEW.key IN ('req_format','launch_payment_checked') BEGIN SELECT RAISE(ABORT, 'Payments unavailable'); END;
