/**
 * Deep Coast — Vue islands.
 *
 * Classic script on purpose: it loads after vue.global.prod.js (both deferred,
 * document order preserved) and mounts only the elements that declare
 * [data-island]. Everything the island renders was already server-rendered into
 * the page, so crawlers and no-JS visitors never depend on this file.
 *
 * The island template is a string, compiled at runtime by the global build. That
 * requires CSP 'unsafe-eval', which this project already allows (see .htaccess).
 */
(function () {
    'use strict';

    var Vue = window.Vue;

    if (!Vue) {
        return;
    }

    var BUCKETS = [
        { key: 'all', label: 'All lengths', min: 0, max: 999 },
        { key: 'short', label: '3–7 days', min: 1, max: 7 },
        { key: 'medium', label: '8–10 days', min: 8, max: 10 },
        { key: 'long', label: '11+ days', min: 11, max: 999 }
    ];

    var JourneyMatcher = {
        props: {
            items: { type: Array, default: function () { return []; } }
        },
        data: function () {
            return { bucket: 'all' };
        },
        computed: {
            buckets: function () {
                var items = this.items;
                return BUCKETS.map(function (bucket) {
                    return {
                        key: bucket.key,
                        label: bucket.label,
                        count: items.filter(function (item) {
                            var days = Number(item.days) || 0;
                            return days >= bucket.min && days <= bucket.max;
                        }).length
                    };
                });
            },
            filtered: function () {
                var current = BUCKETS.filter(function (b) { return b.key === this.bucket; }, this)[0] || BUCKETS[0];
                return this.items.filter(function (item) {
                    var days = Number(item.days) || 0;
                    return days >= current.min && days <= current.max;
                });
            }
        },
        template: [
            '<div class="ix-matcher">',
            '  <div class="ix-matcher__controls" role="group" aria-label="Filter journeys by length">',
            '    <button v-for="b in buckets" :key="b.key" type="button" class="ix-matcher__chip"',
            '            :aria-pressed="bucket === b.key ? \'true\' : \'false\'"',
            '            @click="bucket = b.key">{{ b.label }}</button>',
            '    <span class="ix-matcher__count">{{ filtered.length }} journeys</span>',
            '  </div>',
            '  <transition-group name="ix-fade" tag="div" class="ix-matcher__grid">',
            '    <article v-for="item in filtered" :key="item.url" class="ix-card">',
            '      <a class="ix-card__link" :href="item.url">',
            '        <span class="ix-card__media">',
            '          <img :src="item.image" :alt="item.alt" loading="lazy" decoding="async">',
            '          <span v-if="item.days" class="ix-card__badge">{{ item.days }} days</span>',
            '        </span>',
            '        <span class="ix-card__body">',
            '          <span class="ix-card__title">{{ item.title }}</span>',
            '          <span class="ix-card__excerpt">{{ item.excerpt }}</span>',
            '          <span class="ix-card__facts">',
            '            <span v-if="item.price" class="ix-card__price">From {{ item.price }}</span>',
            '          </span>',
            '        </span>',
            '      </a>',
            '    </article>',
            '  </transition-group>',
            '  <p v-if="filtered.length === 0" class="ix-matcher__empty">',
            '    No journeys in that length yet — try another range.',
            '  </p>',
            '</div>'
        ].join('\n')
    };

    var registry = {
        'journey-matcher': JourneyMatcher
    };

    document.querySelectorAll('[data-island]').forEach(function (element) {
        var name = element.getAttribute('data-island');
        var component = registry[name];

        if (!component) {
            return;
        }

        var props = {};

        try {
            props = JSON.parse(element.getAttribute('data-props') || '{}');
        } catch (error) {
            props = {};
        }

        try {
            // createApp(rootComponent, rootProps) — the documented way to pass
            // server data into the root component.
            Vue.createApp(component, props).mount(element);
        } catch (error) {
            // Leave the server-rendered markup in place if Vue cannot boot.
            if (window.console && console.warn) {
                console.warn('Island failed to mount:', name, error);
            }
        }
    });
})();
