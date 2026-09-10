# Gravity Forms UTM Tracking

Automatically captures and stores UTM parameters in Gravity Forms submissions.

## Description

This plugin automatically captures UTM parameters from the URL and stores them in Gravity Forms submissions. It ensures that the UTM fields are present in the form and populates them with the appropriate values.

## Installation

1. Upload the plugin files to the `/wp-content/plugins/gravity-forms-utm-tracking` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Ensure that your Gravity Forms have the necessary UTM fields.

## Frequently Asked Questions

### How do I enable the debug logging?

Debug logging is off by default. Set the `gf_utm_debug` cookie to `1` — the quickest way is to visit the site with `?utm_debug=1` in the URL, which sets the cookie for 24 hours — then open the browser console and look for messages prefixed `[GF UTM Tracking]`. Visit with `?utm_debug=0` or delete the cookie to switch logging off again.

### How do I ensure UTM fields are present in my forms?

The plugin automatically adds the UTM fields (`utm_source`, `utm_medium`, `utm_campaign`, `utm_term`, `utm_content`) to your forms if they are not already present. You may need to save each form once after installing.

### How are UTM parameters captured?

The plugin captures UTM parameters from the URL and stores them in cookies. These values are then populated into the Gravity Forms fields.

### Does this work with caching plugins?

Yes. The hidden fields added to your forms are tagged with the `gf-utm-field` CSS class, and their values are filled in with JavaScript when the page loads and again just before the form is submitted. This means cached pages that were generated with empty values still capture the correct UTM data.

If you add the hidden fields manually in the form editor, the plugin will detect them by their label or admin label (e.g. "UTM Source" or "landing-page"), set the parameter name and `gf-utm-field` CSS class for you, and clear any hard-coded default values. Matching happens within each form, so no form or field IDs need to be configured and it works across any number of forms. Alternatively, set the parameter name under Advanced &gt; Allow field to be populated dynamically and add the `gf-utm-field` CSS class yourself.

## Changelog

### 1.0.5
- Tracked fields now carry a per-parameter class (`gf-utm-field-utm_source` etc.) so the JavaScript can map each hidden field to the right cookie — Gravity Forms renders hidden inputs as `name="input_{id}"`, so the name attribute alone is not enough.

### 1.0.4
- Manually added hidden UTM fields are now detected by their label or admin label and configured automatically; stale hard-coded default values in them are cleared.

### 1.0.3
- Debug console logging is now opt-in: enabled by the `gf_utm_debug` cookie, or by visiting with `?utm_debug=1`.

### 1.0.2
- Added the `gf-utm-field` CSS class to tracked fields so values can be filled via JavaScript on cached pages.
- Field values are refreshed in JavaScript before submission and on AJAX form re-renders.
- Fixed landing page URL truncation and cookie value encoding.

### 1.0.1
- Added functionality to track and store the landing page URL.

### 1.0.0
- Initial release.

## Upgrade Notice

### 1.0.5
- Fields are now tagged with a per-parameter class so JavaScript filling works on cached pages even though hidden inputs are named `input_{id}`.

### 1.0.4
- Hidden UTM fields you added manually are now adopted and configured automatically — including clearing any hard-coded test values in them.

### 1.0.3
- Debug logging is now off by default; enable it with the `gf_utm_debug` cookie or `?utm_debug=1`.

### 1.0.2
- Adds JavaScript-based field filling so UTM tracking works on cached pages. Saving each form once in the admin tags older fields with the new CSS class.

### 1.0.1
- Added functionality to track and store the landing page URL.

### 1.0.0
- Initial release.

## License

This plugin is licensed under the GPL-2.0+ license. See the [LICENSE](http://www.gnu.org/licenses/gpl-2.0.txt) file for more information.
