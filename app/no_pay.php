<?php
declare(strict_types=1);

/** Structured payment data is forbidden, independently of UI and validation. */
function payment_data_present(array $value): bool
{
    foreach ($value as $key => $v) {
        if (in_array((string) $key, ['methods', 'recipient', 'button', 'button_label', 'pay_purpose', 'req_format', 'payment_checked', 'launch_payment_checked', 'pay_methods', 'req', 'qr_path'], true)
            || ($key === 'type' && $v === 'pay') || (($value['type'] ?? '') === 'service' && $key === 'url')) {
            return true;
        }
        if (is_array($v) && payment_data_present($v)) return true;
    }
    return false;
}

/** Only transfer imports are converted. Saves/previews reject legacy fields. */
function no_pay_import(array $value): array
{
    foreach ($value as $key => $v) {
        if (in_array((string) $key, ['methods', 'recipient', 'button', 'button_label', 'pay_purpose', 'req_format', 'payment_checked', 'launch_payment_checked', 'pay_methods', 'req', 'qr_path'], true)
            || (($value['type'] ?? '') === 'service' && $key === 'url')) {
            unset($value[$key]);
        } elseif ($key === 'blocks' && is_array($v)) {
            $value[$key] = array_values(array_map('no_pay_import', array_filter($v, fn($b) => ($b['type'] ?? '') !== 'pay')));
        } elseif (is_array($v)) {
            $value[$key] = no_pay_import($v);
        }
    }
    return $value;
}
