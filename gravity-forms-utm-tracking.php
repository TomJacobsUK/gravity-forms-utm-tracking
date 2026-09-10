<?php
/**
 * Plugin Name: Gravity Forms UTM Tracking
 * Plugin URI: https://tomjacobs.co.uk
 * Description: Automatically captures and stores UTM parameters in Gravity Forms submissions.
 * Version: 1.0.5
 * Author: TomJacobsUK
 * Author URI: https://github.com/TomJacobsUK
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

if (!defined('ABSPATH')) exit;

class GF_UTM_Tracking {
    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('gform_after_save_form', [$this, 'ensure_utm_fields']);
        add_filter('gform_pre_render', [$this, 'ensure_utm_fields_at_render']);
        add_filter('gform_field_value', [$this, 'populate_utm_fields'], 10, 3);
        add_action('wpmu_new_blog', [$this, 'activate_for_new_site']);
        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_for_update']);
        add_filter('plugins_api', [$this, 'plugins_api_handler'], 10, 3);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('gf-utm-tracking', plugin_dir_url(__FILE__) . '/js/utm-tracking.js', [], '1.0.5', true);
    }

    public function ensure_utm_fields_at_render($form) {
        // Normalise fields on every render without persisting, so manually
        // added UTM fields work immediately; the admin save persists them
        return $this->ensure_utm_fields($form, false);
    }

    public function ensure_utm_fields($form, $persist = true) {
        $utm_fields = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'landing_page'];
        $updated = false;

        foreach ($utm_fields as $utm) {
            $field = $this->find_utm_field($form, $utm);

            if ($field === null) {
                $new_field_id = 0;
                foreach ($form['fields'] as $existing) {
                    if ($existing->id > $new_field_id) {
                        $new_field_id = $existing->id;
                    }
                }
                $new_field_id++;

                $form['fields'][] = GF_Fields::create([
                    'id' => $new_field_id,
                    'type' => 'hidden',
                    'inputName' => $utm,
                    'label' => ucfirst(str_replace('_', ' ', $utm)),
                    'cssClass' => 'gf-utm-field gf-utm-field-' . $utm,
                    'allowsPrepopulate' => true,
                ]);
                $updated = true;
                continue;
            }

            // Adopt and normalise an existing (often manually added) hidden
            // field so the PHP filter and the JS selector can both fill it
            if (($field->inputName ?? '') !== $utm) {
                $field->inputName = $utm;
                $updated = true;
            }
            if (empty($field->allowsPrepopulate)) {
                $field->allowsPrepopulate = true;
                $updated = true;
            }
            // Tag the field with the parameter name so the JS can map it to a
            // cookie even though GF renders hidden inputs as name="input_{id}"
            $new_css_class = $this->utm_css_class($field->cssClass ?? '', $utm);
            if ($new_css_class !== (string) ($field->cssClass ?? '')) {
                $field->cssClass = $new_css_class;
                $updated = true;
            }
            // Remove hard-coded test/default values, e.g. "Linkedin", so they
            // cannot be submitted as tracking data
            if (!empty($field->defaultValue)) {
                $field->defaultValue = '';
                $updated = true;
            }
        }

        if ($persist && $updated) {
            GFAPI::update_form($form);
        }

        return $form;
    }

    private function utm_css_class($css_class, $utm) {
        // Keep any styling classes, replace our own tokens (a field may have
        // been matched to a different parameter before)
        $classes = preg_split('/\s+/', trim((string) $css_class), -1, PREG_SPLIT_NO_EMPTY);
        $classes = array_filter($classes, function ($class) {
            return $class !== 'gf-utm-field' && strpos($class, 'gf-utm-field-') !== 0;
        });
        $classes[] = 'gf-utm-field';
        $classes[] = 'gf-utm-field-' . $utm;
        return implode(' ', array_unique(array_values($classes)));
    }

    private function find_utm_field($form, $utm) {
        foreach ($form['fields'] as $field) {
            $is_hidden = (($field->type ?? '') === 'hidden') || (($field->inputType ?? '') === 'hidden');
            if (!$is_hidden) {
                continue;
            }
            if (($field->inputName ?? '') === $utm) {
                return $field;
            }
            // Match manually added fields by their admin label or label,
            // e.g. "Utm Source" or "landing-page"
            foreach (['adminLabel', 'label'] as $prop) {
                $text = str_replace([' ', '-'], '_', strtolower(trim((string) ($field->$prop ?? ''))));
                if ($text === $utm) {
                    return $field;
                }
            }
        }
        return null;
    }

    public function populate_utm_fields($value, $field, $name) {
        if (in_array($name, ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'landing_page'], true)) {
            // Cookies are URI-encoded by js/utm-tracking.js
            return isset($_COOKIE[$name]) ? sanitize_text_field(urldecode($_COOKIE[$name])) : '';
        }
        return $value;
    }

    public function activate_for_new_site($blog_id) {
        if (is_plugin_active_for_network(plugin_basename(__FILE__))) {
            switch_to_blog($blog_id);

            if (class_exists('GFAPI')) {
                $forms = GFAPI::get_forms();
                if (is_array($forms)) {
                    foreach ($forms as $form) {
                        $this->ensure_utm_fields($form);
                    }
                }
            }

            restore_current_blog();
        }
    }

    public function check_for_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $remote_version = $this->get_remote_version();
        $plugin_data = get_plugin_data(__FILE__);
        $plugin_version = $plugin_data['Version'];

        if (version_compare($plugin_version, $remote_version, '<')) {
            $plugin_slug = plugin_basename(__FILE__);
            $transient->response[$plugin_slug] = (object) [
                'slug' => $plugin_slug,
                'new_version' => $remote_version,
                'url' => 'https://github.com/TomJacobsUK/gravity-forms-utm-tracking',
                'package' => 'https://github.com/TomJacobsUK/gravity-forms-utm-tracking/archive/refs/heads/main.zip',
            ];
        }

        return $transient;
    }

    public function plugins_api_handler($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }

        $plugin_slug = dirname(plugin_basename(__FILE__));

        if (empty($args->slug) || $args->slug !== $plugin_slug) {
            return $result;
        }

        $remote_info = $this->get_remote_info();

        return (object) [
            'name' => 'Gravity Forms UTM Tracking',
            'slug' => $plugin_slug,
            'version' => $remote_info->version,
            'author' => 'Tom Jacobs',
            'author_profile' => 'https://tomjacobs.co.uk',
            'homepage' => 'https://github.com/TomJacobsUK/gravity-forms-utm-tracking',
            'short_description' => 'Automatically captures and stores UTM parameters in Gravity Forms submissions.',
            'sections' => [
                'description' => $remote_info->description,
                'changelog' => $remote_info->changelog,
            ],
            'download_link' => 'https://github.com/TomJacobsUK/gravity-forms-utm-tracking/archive/refs/heads/main.zip',
        ];
    }

    private function get_remote_version() {
        $remote_info = $this->get_remote_info();
        return $remote_info->version;
    }

    private function get_remote_info() {
        $response = wp_remote_get('https://raw.githubusercontent.com/TomJacobsUK/gravity-forms-utm-tracking/main/info.json');
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return (object) [
                'version' => '1.0.0',
                'description' => '',
                'changelog' => '',
            ];
        }

        return json_decode(wp_remote_retrieve_body($response));
    }
}

new GF_UTM_Tracking();
