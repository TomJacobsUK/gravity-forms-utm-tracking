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
    }

    function populateFormFields() {
        const cookies = document.cookie.split('; ').reduce((acc, cookie) => {
            const [name, value] = cookie.split('=');
            acc[name] = value;
            return acc;
        }, {});

        document.querySelectorAll('form input[name^="utm_"]').forEach(input => {
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