/* jshint esversion: 6 */
/**
 * Lightweight proxy that loads the full slide-out menu only when it is opened.
 */
define('package/quiqqer/menu/bin/SlideOutLazy', [

    'qui/controls/Control'

], function (QUIControl) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/menu/bin/SlideOutLazy',

        options: {
            control: 'package/quiqqer/menu/bin/SlideOut'
        },

        initialize: function (options) {
            this.parent(options);

            this.$Control = null;
            this.$Loading = null;
            this.$loaderNodes = [];
            this.$menuButtonDisabled = false;
        },

        /**
         * Load the original slide-out control and import it into the current element.
         *
         * @return {Promise}
         */
        $load: function () {
            if (this.$Control) {
                return Promise.resolve(this.$Control);
            }

            if (this.$Loading) {
                return this.$Loading;
            }

            this.$Loading = new Promise((resolve, reject) => {
                require([this.getAttribute('control')], (SlideOutControl) => {
                    const Control = new SlideOutControl(this.getAttributes());

                    Control.addEvent('open', () => {
                        this.fireEvent('open');
                    });

                    Control.imports(this.getElm());

                    if (this.$menuButtonDisabled && typeof Control.disableMenuButton === 'function') {
                        Control.disableMenuButton();
                    }

                    this.$Control = Control;
                    resolve(Control);
                }, reject);
            });

            return this.$Loading;
        },

        /**
         * Toggle the slide-out menu.
         *
         * @return {Promise}
         */
        toggle: function () {
            if (this.$Control) {
                this.$Control.toggle();
                return Promise.resolve(this.$Control);
            }

            if (this.$Loading) {
                return this.$Loading;
            }

            this.$showLoader();

            return this.$load().then((Control) => {
                this.$hideLoader();
                Control.toggle();

                return Control;
            }).catch((error) => {
                this.$Loading = null;
                this.$hideLoader();
                throw error;
            });
        },

        /**
         * Show a lightweight loader on configured menu buttons while SlideOut is loading.
         */
        $showLoader: function () {
            const buttons = this.$getMenuButtons();

            buttons.forEach((Button) => {
                if (Button.get('data-slideout-lazy-loading') === '1') {
                    return;
                }

                const Loader = new Element('span', {
                    'class': 'fa fa-spinner fa-spin quiqqer-menu-slideoutLazy-loader',
                    'aria-hidden': 'true',
                    styles: {
                        marginLeft: 8
                    }
                }).inject(Button);

                Button.set('data-slideout-lazy-loading', '1');
                Button.set('aria-busy', 'true');
                Button.addClass('quiqqer-menu-slideoutLazy-loading');

                this.$loaderNodes.push({
                    Button: Button,
                    Loader: Loader
                });
            });
        },

        /**
         * Remove loader nodes from menu buttons.
         */
        $hideLoader: function () {
            this.$loaderNodes.forEach((Entry) => {
                Entry.Button.erase('data-slideout-lazy-loading');
                Entry.Button.erase('aria-busy');
                Entry.Button.removeClass('quiqqer-menu-slideoutLazy-loading');
                Entry.Loader.destroy();
            });

            this.$loaderNodes = [];
        },

        /**
         * Return configured menu button elements.
         *
         * @return {Array}
         */
        $getMenuButtons: function () {
            const buttonIds = this.getAttribute('buttonids');

            if (!buttonIds) {
                return [];
            }

            return buttonIds.split(',')
                .map((buttonId) => document.id(buttonId.trim()))
                .filter((Button) => !!Button);
        },

        /**
         * Do not show the menu button after the original control was loaded.
         */
        disableMenuButton: function () {
            this.$menuButtonDisabled = true;

            if (this.$Control && typeof this.$Control.disableMenuButton === 'function') {
                this.$Control.disableMenuButton();
            }
        },

        /**
         * Show the menu button after the original control was loaded.
         */
        enableMenuButton: function () {
            this.$menuButtonDisabled = false;

            if (this.$Control && typeof this.$Control.enableMenuButton === 'function') {
                this.$Control.enableMenuButton();
            }
        }
    });
});
