# Gravity Forms UTM Tracking

Automatically captures and stores UTM parameters in Gravity Forms submissions.

## Description

This plugin automatically captures UTM parameters from the URL and stores them in Gravity Forms submissions. It ensures that the UTM fields are present in the form and populates them with the appropriate values.

## Installation

1. Upload the plugin files to the `/wp-content/plugins/gravity-forms-utm-tracking` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Ensure that your Gravity Forms have the necessary UTM fields.

## Frequently Asked Questions

### How do I ensure UTM fields are present in my forms?

The plugin automatically adds the UTM fields (`utm_source`, `utm_medium`, `utm_campaign`, `utm_term`, `utm_content`) to your forms if they are not already present. You may need to save each form once after installing.

### How are UTM parameters captured?

The plugin captures UTM parameters from the URL and stores them in cookies. These values are then populated into the Gravity Forms fields.

## Changelog

### 1.0.1
- Added functionality to track and store the landing page URL.

### 1.0.0
- Initial release.

## Upgrade Notice

### 1.0.1
- Added functionality to track and store the landing page URL.

### 1.0.0
- Initial release.

## License

This plugin is licensed under the GPL-2.0+ license. See the [LICENSE](http://www.gnu.org/licenses/gpl-2.0.txt) file for more information.
