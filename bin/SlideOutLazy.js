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

            this.$Loading = new Promise((resolve) => {
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
                });
            });

            return this.$Loading;
        },

        /**
         * Toggle the slide-out menu.
         *
         * @return {Promise}
         */
        toggle: function () {
            return this.$load().then((Control) => {
                Control.toggle();

                return Control;
            });
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
