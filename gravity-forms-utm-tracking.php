<?php
/**
 * Plugin Name: Gravity Forms UTM Tracking
 * Plugin URI: https://tomjacobs.co.uk
 * Description: Automatically captures and stores UTM parameters in Gravity Forms submissions.
 * Version: 1.0.0
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
        add_filter('gform_field_value', [$this, 'populate_utm_fields'], 10, 3);
        add_action('wpmu_new_blog', [$this, 'activate_for_new_site']);
        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_for_update']);
        add_filter('plugins_api', [$this, 'plugins_api_handler'], 10, 3);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('gf-utm-tracking', plugin_dir_url(__FILE__) . '/js/utm-tracking.js', [], '1.0.0', true);
    }

    public function ensure_utm_fields($form) {
        $utm_fields = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];
        $updated = false;
        
        foreach ($utm_fields as $utm) {
            $field_exists = false;
            
            foreach ($form['fields'] as $field) {
                if (isset($field->inputName) && $field->inputName === $utm) {
                    $field_exists = true;
                    break;
                }
            }
            
            if (!$field_exists) {
                $new_field_id = 0;
                foreach ($form['fields'] as $field) {
                    if ($field->id > $new_field_id) {
                        $new_field_id = $field->id;
                    }
                }
                $new_field_id++;
                
                $new_field = GF_Fields::create([
                    'id' => $new_field_id,
                    'type' => 'hidden',
                    'inputName' => $utm,
                    'label' => ucfirst(str_replace('_', ' ', $utm)),
                    'allowsPrepopulate' => true,
                ]);
                
                $form['fields'][] = $new_field;
                $updated = true;
            }
        }
        
        if ($updated) {
            GFAPI::update_form($form);
        }
    }

    public function populate_utm_fields($value, $field, $name) {
        if (in_array($name, ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'])) {
            return isset($_COOKIE[$name]) ? sanitize_text_field($_COOKIE[$name]) : '';
        }
        return $value;
    }

    public function activate_for_new_site($blog_id) {
        if (is_plugin_active_for_network(plugin_basename(__FILE__))) {
            switch_to_blog($blog_id);
            $this->ensure_utm_fields();
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

        if ($args->slug !== plugin_basename(__FILE__)) {
            return $result;
        }

        $remote_info = $this->get_remote_info();

        return (object) [
            'name' => 'Gravity Forms UTM Tracking',
            'slug' => plugin_basename(__FILE__),
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
