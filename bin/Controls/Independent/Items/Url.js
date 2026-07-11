/**
 * @module package/quiqqer/menu/bin/Controls/Independent/Items/Url
 * @author www.pcsg.de
 */
define('package/quiqqer/menu/bin/Controls/Independent/Items/Url', [

    'qui/QUI',
    'QUIQQER',
    'qui/controls/Control',
    'Locale',
    'Mustache',
    'package/quiqqer/menu/bin/classes/IndependentHandler',

    'text!package/quiqqer/menu/bin/Controls/Independent/Items/Url.html'

], function (QUI, QUIQQER, QUIControl, QUILocale, Mustache, IndependentHandler, template) {
    "use strict";

    const lg = 'quiqqer/menu';

    return new Class({

        Extends: QUIControl,
        Types  : 'package/quiqqer/menu/bin/Controls/Independent/Items/Url',

        Binds: [
            '$onInject'
        ],

        initialize: function (options) {
            this.parent(options);

            this.addEvents({
                onInject: this.$onInject
            });
        },

        $onInject: function () {
            this.getElm().set('data-qui', this.getType());
            this.getElm().set('html', Mustache.render(template, {
                status           : QUILocale.get('quiqqer/core', 'status'),
                statusDescription: QUILocale.get(lg, 'tpl.statusDescription'),

                title         : QUILocale.get('quiqqer/core', 'title'),
                linkTitle     : QUILocale.get(lg, 'tpl.linkTitle'),
                icon          : QUILocale.get(lg, 'tpl.icon'),
                url           : QUILocale.get(lg, 'tpl.url'),
                rel           : QUILocale.get(lg, 'tpl.rel'),
                relDescription: QUILocale.get(lg, 'tpl.relDescription'),

                alternate : QUILocale.get(lg, 'tpl.rel.alternate'),
                author    : QUILocale.get(lg, 'tpl.rel.author'),
                bookmark  : QUILocale.get(lg, 'tpl.rel.bookmark'),
                external  : QUILocale.get(lg, 'tpl.rel.external'),
                help      : QUILocale.get(lg, 'tpl.rel.help'),
                license   : QUILocale.get(lg, 'tpl.rel.license'),
                next      : QUILocale.get(lg, 'tpl.rel.next'),
                nofollow  : QUILocale.get(lg, 'tpl.rel.nofollow'),
                noopener  : QUILocale.get(lg, 'tpl.rel.noopener'),
                noreferrer: QUILocale.get(lg, 'tpl.rel.noreferrer'),
                prev      : QUILocale.get(lg, 'tpl.rel.prev'),
                search    : QUILocale.get(lg, 'tpl.rel.search'),
                tag       : QUILocale.get(lg, 'tpl.rel.tag'),

                target      : QUILocale.get(lg, 'tpl.target'),
                targetNone  : QUILocale.get(lg, 'tpl.target.none'),
                targetSelf  : QUILocale.get(lg, 'tpl.target.self'),
                targetFrame : QUILocale.get(lg, 'tpl.target.frame'),
                targetPopup : QUILocale.get(lg, 'tpl.target.popup'),
                targetBlank : QUILocale.get(lg, 'tpl.target.blank'),
                targetTop   : QUILocale.get(lg, 'tpl.target.top'),
                targetParent: QUILocale.get(lg, 'tpl.target.parent'),

                menuType                : QUILocale.get(lg, 'menu.settings.type'),
                menuTypeStandard        : QUILocale.get(lg, 'menu.settings.Standard'),
                menuTypeIcons           : QUILocale.get(lg, 'menu.settings.Icons'),
                menuTypeIconsDescription: QUILocale.get(lg, 'menu.settings.IconsDescription'),
                menuTypeImage           : QUILocale.get(lg, 'menu.settings.Image'),
                menuTypeSimple          : QUILocale.get(lg, 'menu.settings.Simple'),
                menuTypeNoMenu          : QUILocale.get(lg, 'menu.settings.noMenu'),
            }));

            let title = this.getAttribute('title');
            let icon = this.getAttribute('icon');
            let data = this.getAttribute('data');

            try {
                title = JSON.decode(title);
            } catch (e) {
            }

            try {
                if (typeof data === 'string') {
                    data = JSON.decode(data);
                }
            } catch (e) {
            }

            if (!data) {
                data = {};
            }

            if (typeof data.url === 'undefined') {
                data.url = '';
            }

            if (typeof data.name === 'undefined') {
                data.name = '';
            }

            if (typeof data.target !== 'undefined') {
                this.getElm().getElement('[name="target"]').set('value', data.target);
            }

            if (typeof data.status === 'undefined' || data.status) {
                this.getElm().getElement('[name="status"]').set('checked', true);
            }

            IndependentHandler.getTypeName(this.getAttribute('type')).then((name) => {
                const TH = this.getElm().getElement('th');

                if (TH) {
                    TH.set('html', name);
                }
            });

            this.getElm().getElement('[name="icon"]').set('value', icon);
            this.getElm().getElement('[name="rel"]').set('value', data.rel);
            this.getElm().getElement('[name="menuType"]').set('value', data.menuType);

            Promise.all([
                QUI.parse(this.getElm()),
                QUIQQER.getAvailableLanguages()
            ]).then(([, languages]) => {
                this.$Title = QUI.Controls.getById(
                    this.getElm().getElement('[name="title"]').get('data-quiid')
                );

                this.$Name = QUI.Controls.getById(
                    this.getElm().getElement('[name="name"]').get('data-quiid')
                );

                this.$Url = QUI.Controls.getById(
                    this.getElm().querySelector('[name="url"]').getAttribute('data-quiid')
                );

                this.$Title.setData(title);
                this.$Name.setData(data.name);
                this.$Url.setData(this.$normalizeUrlData(data.url, languages));

                if (this.$Title.isLoaded()) {
                    this.$Title.open();
                } else {
                    this.$Title.addEvent('load', () => {
                        this.$Title.open();
                    });
                }

                if (this.$Name.isLoaded()) {
                    this.$Name.open();
                } else {
                    this.$Name.addEvent('load', () => {
                        this.$Name.open();
                    });
                }

                if (this.$Url.isLoaded()) {
                    this.$Url.open();
                } else {
                    this.$Url.addEvent('load', () => {
                        this.$Url.open();
                    });
                }

                this.fireEvent('load');
            });
        },

        /**
         * Keep old single-language urls working:
         * a plain string is applied to every language.
         *
         * @param {String|Object} url
         * @param {Array} languages
         * @returns {Object} - {lang: url}
         */
        $normalizeUrlData: function (url, languages) {
            if (typeof url === 'object' && url !== null && !Array.isArray(url)) {
                return url;
            }

            if (typeof url !== 'string' || url === '') {
                return {};
            }

            let decoded = null;

            try {
                decoded = JSON.parse(url);
            } catch (e) {
            }

            if (typeof decoded === 'object' && decoded !== null && !Array.isArray(decoded)) {
                return decoded;
            }

            const result = {};

            languages.forEach((lang) => {
                result[lang] = url;
            });

            return result;
        },

        save: function () {
            const Form = this.getElm().getElement('form');

            return {
                title: this.$Title.getValue(),
                icon : Form.elements.icon.value,
                data : {
                    url     : this.$Url.getValue(),
                    target  : Form.elements.target.value,
                    menuType: Form.elements.menuType.value,
                    status  : Form.elements.status.checked ? 1 : 0,
                    name    : this.$Name.getValue(),
                    rel     : Form.elements.rel.value
                }
            };
        }
    });
});