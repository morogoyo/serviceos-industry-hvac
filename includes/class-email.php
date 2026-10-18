<?php
namespace ServiceOS_Industry_Plugin;

if (!defined('ABSPATH')) {
    exit;
}

class Email {

    public static function send($payload) {
        $settings = get_option('hvac_settings', []);
        $recipient = $settings['recipient'] ?? get_option('admin_email');
        $cc = $settings['cc'] ?? '';

        $subject = self::build_subject($payload);
        $body = self::build_body($payload);
        $headers = self::build_headers($cc);

        return wp_mail($recipient, $subject, $body, $headers);
    }

    private static function build_subject($p) {
        $property = sanitize_text_field($p['ji_property'] ?? 'Unknown Property');
        $date = sanitize_text_field($p['ji_date'] ?? date('Y-m-d'));
        $tech = sanitize_text_field($p['ji_tech'] ?? 'Unknown Tech');
        $units = intval($p['unit_count'] ?? 0);
        return "[ServicePro] Service Report — {$property} — {$date} — {$tech} ({$units} units)";
    }

    private static function build_headers($cc = '') {
        $settings = get_option('hvac_settings', []);
        $company = $settings['company'] ?? 'ServicePro';
        $from_email = $settings['from_email'] ?? 'no-reply@servicepro.tools';

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            "From: {$company} Checklist <{$from_email}>",
        ];

        if (!empty($cc)) {
            $headers[] = "Cc: {$cc}";
        }

        return $headers;
    }

    private static function build_body($p) {
        ob_start();
        include SERVICEOS_IP_PATH . 'templates/email-report.php';
        return hvac_render_email($p);
    }
}
