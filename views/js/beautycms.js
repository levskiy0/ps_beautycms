document.addEventListener('DOMContentLoaded', function() {
    const usePrettyUrlCheckbox = document.querySelector('.js-use-pretty-url-toggle');

    if (!usePrettyUrlCheckbox) {
        return;
    }

    const prettyUrlContainer = document.querySelector('.js-pretty-url-container');
    const friendlyUrlContainer = document.querySelector('#cms_page_seo_friendly_url');

    function toggleUrlFields() {
        const isChecked = usePrettyUrlCheckbox.checked;

        if (prettyUrlContainer) {
            const parentFormGroup = prettyUrlContainer.closest('.form-group');
            if (parentFormGroup) {
                parentFormGroup.style.display = isChecked ? 'block' : 'none';
            }
        }

        if (friendlyUrlContainer) {
            const parentFormGroup = friendlyUrlContainer.closest('.form-group');
            if (parentFormGroup) {
                parentFormGroup.style.display = isChecked ? 'none' : 'block';
            }
        }
    }

    toggleUrlFields();

    usePrettyUrlCheckbox.addEventListener('change', toggleUrlFields);
});
