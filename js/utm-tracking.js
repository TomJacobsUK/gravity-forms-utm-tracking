(function() {
    function getQueryParams() {
        const params = new URLSearchParams(window.location.search);
        const utmParams = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];
        
        utmParams.forEach(param => {
            if (params.has(param)) {
                document.cookie = `${param}=${params.get(param)}; path=/; max-age=86400`; // Store for 1 day
                console.log(`UTM parameter stored: ${param} = ${params.get(param)}`);
            }
        });

        // Store the landing page URL if not already set
        if (!document.cookie.split('; ').find(row => row.startsWith('landing_page='))) {
            document.cookie = `landing_page=${window.location.href}; path=/; max-age=86400`; // Store for 1 day
            console.log(`Landing page stored: ${window.location.href}`);
        }
    }

    function populateFormFields() {
        const cookies = document.cookie.split('; ').reduce((acc, cookie) => {
            const [name, value] = cookie.split('=');
            acc[name] = value;
            return acc;
        }, {});

        document.querySelectorAll('form input[name^="utm_"], form input[name="landing_page"]').forEach(input => {
            if (cookies[input.name]) {
                input.value = cookies[input.name];
                console.log(`Populated ${input.name} with ${cookies[input.name]}`);
            }
        });
    }

    console.log("UTM Tracking Script Loaded");
    getQueryParams();
    document.addEventListener("DOMContentLoaded", populateFormFields);
})();