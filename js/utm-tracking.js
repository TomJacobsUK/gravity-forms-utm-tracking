(function () {
    const COOKIE_MAX_AGE = 86400; // 1 day
    const UTM_PARAMS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];
    const DEBUG_COOKIE = 'gf_utm_debug';

    // Debug logging is opt-in: the gf_utm_debug cookie enables it, and
    // visiting with ?utm_debug=1 is the quickest way to set that cookie
    let DEBUG = enableDebugLogging();

    // Fields added by the plugin carry the gf-utm-field CSS class (rendered on the
    // li.gfield wrapper); the name-based selectors cover older installs and
    // manually added fields
    const FIELD_SELECTOR = '.gfield.gf-utm-field input, input[name^="utm_"], input[name="landing_page"]';

    function debug(message, data) {
        if (!DEBUG) {
            return;
        }
        if (typeof data !== 'undefined') {
            console.log(`[GF UTM Tracking] ${message}`, data);
        } else {
            console.log(`[GF UTM Tracking] ${message}`);
        }
    }

    function setCookie(name, value) {
        // URI-encode the value so "=", "&" and ";" inside URLs cannot break the cookie
        document.cookie = `${name}=${encodeURIComponent(value)}; path=/; max-age=${COOKIE_MAX_AGE}; samesite=Lax`;
    }

    function getCookie(name) {
        for (const row of document.cookie.split('; ')) {
            const separator = row.indexOf('=');
            if (separator === -1) {
                continue;
            }
            if (row.slice(0, separator) === name) {
                return decodeURIComponent(row.slice(separator + 1));
            }
        }
        return null;
    }

    function enableDebugLogging() {
        const param = new URLSearchParams(window.location.search).get('utm_debug');
        if (param === '1') {
            setCookie(DEBUG_COOKIE, '1');
        } else if (param === '0') {
            // max-age=0 deletes the cookie
            document.cookie = `${DEBUG_COOKIE}=; path=/; max-age=0`;
        }
        return getCookie(DEBUG_COOKIE) !== null;
    }

    function storeQueryParams() {
        const params = new URLSearchParams(window.location.search);
        let stored = 0;

        UTM_PARAMS.forEach(param => {
            if (params.has(param)) {
                const value = params.get(param);
                setCookie(param, value);
                stored++;
                debug(`Stored UTM from URL: ${param} = "${value}"`);
            }
        });

        if (stored === 0) {
            debug('No UTM parameters found in URL');
        }

        // Landing page is first-touch: recorded only on the first visit, then left alone
        const existingLandingPage = getCookie('landing_page');
        if (existingLandingPage === null) {
            setCookie('landing_page', window.location.href);
            debug(`Stored landing page: "${window.location.href}"`);
        } else {
            debug(`Landing page already stored, keeping: "${existingLandingPage}"`);
        }
    }

    function populateFormFields(contextLabel, root) {
        const scope = root || document;
        const inputs = scope.querySelectorAll(FIELD_SELECTOR);
        let populated = 0;

        inputs.forEach(input => {
            const form = input.closest('form');
            if (!form) {
                debug(`Skipping "${input.name || input.id}" — not inside a <form>`);
                return;
            }

            const formId = form.id || '(unnamed form)';
            const value = getCookie(input.name);

            if (value === null) {
                debug(`Field "${input.name}" in form "${formId}": no matching cookie, left empty`);
                return;
            }

            input.value = value;
            populated++;
            debug(`Populated "${input.name}" in form "${formId}" with "${value}"`);
        });

        debug(`populateFormFields (${contextLabel}): ${populated} of ${inputs.length} field(s) populated`);
    }

    // Safety net for cached pages: refresh the values at the last possible moment,
    // before the form data is serialised. Capture phase so it runs before GF's own handlers.
    function onFormSubmit(event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.querySelector(FIELD_SELECTOR)) {
            return;
        }
        debug(`Submit detected on form "${form.id || '(unnamed form)'}" — refreshing UTM values before send`);
        populateFormFields('submit', form);
    }

    function init() {
        debug(`Running (readyState: ${document.readyState}, url: ${window.location.href})`);
        storeQueryParams();
        populateFormFields('initial');
        document.addEventListener('submit', onFormSubmit, true);

        // Gravity Forms AJAX submissions re-render the form; repopulate so hidden fields stay filled
        if (window.jQuery) {
            window.jQuery(document).on('gform_post_render', function (event, formId) {
                debug(`gform_post_render fired for form ${formId}, repopulating fields`);
                populateFormFields(`gform_post_render form ${formId}`);
            });
        } else {
            debug('jQuery not available — AJAX repopulation hook not attached');
        }
    }

    if (document.readyState === 'loading') {
        debug('Script loaded, waiting for DOMContentLoaded…');
        document.addEventListener('DOMContentLoaded', init);
    } else {
        debug('Script loaded after DOM ready, running immediately');
        init();
    }
})();
