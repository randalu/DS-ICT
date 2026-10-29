(function () {
    'use strict';
    var privacy = window.ICTPrivacy;
    if (!privacy || typeof privacy.canLoad !== 'function') return;
    var loaded = window.ICTOptionalLoaded = window.ICTOptionalLoaded || Object.create(null);
    document.querySelectorAll('template[data-optional-category][data-optional-id]').forEach(function (template) {
        var category = template.getAttribute('data-optional-category');
        var id = template.getAttribute('data-optional-id');
        if (!privacy.canLoad(category) || loaded[id]) return;
        loaded[id] = true;
        var fragment = template.content.cloneNode(true);
        // Scripts parsed inside templates are inert. Recreate trusted server-rendered
        // scripts after the browser gate, preserving settings-before-loader ordering.
        fragment.querySelectorAll('script').forEach(function (inert) {
            var script = document.createElement('script');
            Array.from(inert.attributes).forEach(function (attribute) { script.setAttribute(attribute.name, attribute.value); });
            script.textContent = inert.textContent;
            inert.replaceWith(script);
        });
        template.after(fragment);
        document.querySelectorAll('[data-optional-fallback-for="' + id + '"]').forEach(function (fallback) { fallback.hidden = true; });
    });
})();
