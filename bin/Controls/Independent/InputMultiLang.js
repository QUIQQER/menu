/**
 * @module package/quiqqer/menu/bin/Controls/Independent/InputMultiLang
 * @author www.pcsg.de
 *
 * Menu select with one independent menu per language.
 * Stores a JSON object in the bound input: {"de": "5", "en": "7"}
 * A plain numeric value (old single-menu format) is applied to all languages.
 *
 * The placeholder for empty languages ("same as default language") needs the
 * project's default language. It is resolved in this order:
 *  - option "defaultLang" (e.g. data-qui-options-defaultlang)
 *  - option "project" (project name, e.g. data-qui-options-project)
 *  - the surrounding project settings panel
 * If none is available, the placeholder is simply omitted.
 */
define('package/quiqqer/menu/bin/Controls/Independent/InputMultiLang', [

    'qui/QUI',
    'QUIQQER',
    'Projects',
    'qui/controls/Control',
    'qui/controls/windows/Confirm',
    'package/quiqqer/menu/bin/classes/IndependentHandler',
    'Locale',

    'css!package/quiqqer/menu/bin/Controls/Independent/InputMultiLang.css'

], function (QUI, QUIQQER, Projects, QUIControl, QUIConfirm, IndependentHandler, QUILocale) {
    "use strict";

    const lg = 'quiqqer/menu';

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/menu/bin/Controls/Independent/InputMultiLang',

        Binds: [
            '$onImport'
        ],

        initialize: function (options, Input) {
            this.parent(options);

            this.$Input = Input || null;
            this.$menus = [];
            this.$values = {};
            this.$defaultLang = null;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        $onImport: function () {
            this.$Input = this.getElm();
            this.create();
        },

        create: function () {
            const Elm = document.createElement('div');

            Elm.className = 'quiqqer-menu-independent-inputmultilang';
            Elm.setAttribute('data-quiid', this.getId());
            Elm.setAttribute('data-qui', 'package/quiqqer/menu/bin/Controls/Independent/InputMultiLang');

            if (!this.$Input) {
                this.$Input = document.createElement('input');
                this.$Input.name = this.getAttribute('name') || '';
                Elm.appendChild(this.$Input);
            } else {
                this.$Input.parentNode.insertBefore(Elm, this.$Input);
                Elm.appendChild(this.$Input);
            }

            this.$Elm = Elm;

            if (this.$Input.classList.contains('field-container-field')) {
                Elm.classList.add('field-container-field');
                this.$Input.classList.remove('field-container-field');
            }

            this.$Input.type = 'hidden';

            Promise.all([
                QUIQQER.getAvailableLanguages(),
                IndependentHandler.getList(),
                this.$getDefaultLang()
            ]).then(([languages, menus, defaultLang]) => {
                if (!this.$Elm) {
                    return;
                }

                this.$menus = menus;
                this.$defaultLang = defaultLang;
                this.$values = this.$parseValue(this.$Input.value, languages);

                languages.forEach((lang) => {
                    this.$createEntry(lang);
                });

                this.$refreshValue();
                this.fireEvent('load', [this]);
            });

            return Elm;
        },

        /**
         * Parse the input value.
         * Old format (plain menu id) is applied to all languages,
         * so nothing gets lost when the setting is saved again.
         *
         * @param {String} value
         * @param {Array} languages
         * @returns {Object} - {lang: menuId}
         */
        $parseValue: function (value, languages) {
            if (value === '') {
                return {};
            }

            let data = null;

            try {
                data = JSON.parse(value);
            } catch (e) {
                data = value;
            }

            if (typeof data === 'object' && data !== null && !Array.isArray(data)) {
                return data;
            }

            const menuId = parseInt(value);
            const result = {};

            if (!isNaN(menuId)) {
                languages.forEach((lang) => {
                    result[lang] = String(menuId);
                });
            }

            return result;
        },

        /**
         * Create one language row (flag, menu display, select / remove button)
         *
         * @param {String} lang
         */
        $createEntry: function (lang) {
            const Entry = document.createElement('div');

            Entry.className = 'quiqqer-menu-independent-inputmultilang-entry';
            Entry.setAttribute('data-name', 'entry');
            Entry.setAttribute('data-lang', lang);

            const Flag = document.createElement('img');
            Flag.className = 'quiqqer-menu-independent-inputmultilang-entry-flag';
            Flag.src = URL_BIN_DIR + '16x16/flags/' + lang + '.png';
            Flag.alt = lang.toUpperCase();
            Entry.appendChild(Flag);

            const Display = document.createElement('div');
            Display.className = 'quiqqer-menu-independent-inputmultilang-entry-display';
            Display.setAttribute('data-name', 'display');
            Display.addEventListener('click', () => {
                this.$openSelectDialog(lang);
            });
            Entry.appendChild(Display);

            const SelectButton = document.createElement('button');
            SelectButton.type = 'button';
            SelectButton.className = 'button qui-button--no-icon qui-button qui-utils-noselect';
            SelectButton.innerHTML = '<span class="fa fa-bars" aria-hidden="true"></span>';
            SelectButton.title = QUILocale.get(lg, 'quiqqer.menu.select.win.button');
            SelectButton.setAttribute('aria-label', QUILocale.get(lg, 'quiqqer.menu.select.win.button'));
            SelectButton.addEventListener('click', (event) => {
                event.preventDefault();
                this.$openSelectDialog(lang);
            });
            Entry.appendChild(SelectButton);

            const RemoveButton = document.createElement('button');
            RemoveButton.type = 'button';
            RemoveButton.className = 'button qui-button--no-icon qui-button qui-utils-noselect';
            RemoveButton.innerHTML = '<span class="fa fa-remove" aria-hidden="true"></span>';
            RemoveButton.title = QUILocale.get('quiqqer/core', 'remove');
            RemoveButton.setAttribute('aria-label', QUILocale.get('quiqqer/core', 'remove'));
            RemoveButton.addEventListener('click', (event) => {
                event.preventDefault();
                this.$values[lang] = '';
                this.$refreshValue();
            });
            Entry.appendChild(RemoveButton);

            this.$Elm.appendChild(Entry);
        },

        /**
         * Resolve the project's default language for the fallback placeholder
         * (see module doc block for the resolution order)
         *
         * @returns {Promise} - resolves with the default language or null
         */
        $getDefaultLang: function () {
            if (this.getAttribute('defaultLang')) {
                return Promise.resolve(this.getAttribute('defaultLang'));
            }

            let Project = null;

            if (this.getAttribute('project')) {
                Project = Projects.get(this.getAttribute('project'));
            } else {
                const PanelNode = this.$Input.closest('.qui-panel');
                const Panel = PanelNode ?
                    QUI.Controls.getById(PanelNode.getAttribute('data-quiid')) :
                    null;

                if (Panel && typeof Panel.getProject === 'function') {
                    Project = Panel.getProject();
                }
            }

            if (!Project) {
                return Promise.resolve(null);
            }

            return Project.getConfig(false, 'default_lang').catch(() => null);
        },

        /**
         * Write the current selection into the input and refresh all rows
         */
        $refreshValue: function () {
            const hasValue = Object.values(this.$values).some((value) => {
                return value !== '';
            });

            this.$Input.value = hasValue ? JSON.stringify(this.$values) : '';

            const fallbackTitle = this.$defaultLang ?
                this.$getMenuTitle(this.$values[this.$defaultLang] || '') :
                '';

            const entries = this.$Elm.querySelectorAll('[data-name="entry"]');

            entries.forEach((Entry) => {
                const lang = Entry.getAttribute('data-lang');
                const Display = Entry.querySelector('[data-name="display"]');
                const value = this.$values[lang] || '';

                Display.classList.remove('quiqqer-menu-independent-inputmultilang-entry-display--fallback');

                if (value !== '') {
                    Display.textContent = this.$getMenuTitle(value);
                    return;
                }

                if (lang !== this.$defaultLang && fallbackTitle !== '') {
                    Display.textContent = QUILocale.get(lg, 'quiqqer.menu.select.fallbackDefaultLang', {
                        menu: fallbackTitle
                    });
                    Display.classList.add('quiqqer-menu-independent-inputmultilang-entry-display--fallback');
                    return;
                }

                Display.textContent = '';
            });
        },

        /**
         * @param {String} menuId
         * @returns {String} - "#id title" or an empty string
         */
        $getMenuTitle: function (menuId) {
            if (menuId === '') {
                return '';
            }

            const menu = this.$menus.find((entry) => {
                return parseInt(entry.id) === parseInt(menuId);
            });

            if (!menu) {
                return '';
            }

            return '#' + menu.id + ' ' + menu.title;
        },

        /**
         * Open the menu select dialog for one language
         *
         * @param {String} lang
         */
        $openSelectDialog: function (lang) {
            new QUIConfirm({
                icon     : 'fa fa-bars',
                title    : QUILocale.get(lg, 'quiqqer.menu.select.win.title'),
                maxHeight: 300,
                maxWidth : 500,
                events   : {
                    onOpen  : (Win) => {
                        const Content = Win.getContent();

                        Content.innerHTML = QUILocale.get(lg, 'quiqqer.menu.select.win.content');
                        Content.style.textAlign = 'center';

                        const Select = document.createElement('select');
                        Select.style.display = 'inline-block';
                        Select.style.margin = '20px 0 0 0';
                        Select.style.width = '50%';

                        const EmptyOption = document.createElement('option');
                        EmptyOption.value = '';
                        Select.appendChild(EmptyOption);

                        this.$menus.forEach((menu) => {
                            const Option = document.createElement('option');
                            Option.value = menu.id;
                            Option.textContent = '#' + menu.id + ' ' + menu.title;
                            Select.appendChild(Option);
                        });

                        Select.value = this.$values[lang] || '';
                        Content.appendChild(Select);
                    },
                    onSubmit: (Win) => {
                        this.$values[lang] = Win.getContent().querySelector('select').value;
                        this.$refreshValue();
                        Win.close();
                    }
                }
            }).open();
        },

        /**
         * @returns {String} - JSON object as string: {lang: menuId}
         */
        getValue: function () {
            return this.$Input.value;
        },

        /**
         * @param {String|Object} data - JSON string or object: {lang: menuId}
         */
        setData: function (data) {
            if (typeof data === 'string') {
                try {
                    data = JSON.parse(data);
                } catch (e) {
                    data = {};
                }
            }

            if (typeof data !== 'object' || data === null || Array.isArray(data)) {
                data = {};
            }

            Object.keys(this.$values).forEach((lang) => {
                this.$values[lang] = data[lang] || '';
            });

            this.$refreshValue();
        }
    });
});
