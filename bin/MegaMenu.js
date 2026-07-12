/**
 * @module package/quiqqer/menu/bin/MegaMenu
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 */
define('package/quiqqer/menu/bin/MegaMenu', [

    'qui/QUI',
    'qui/controls/Control'

], function (QUI, QUIControl) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/menu/bin/MegaMenu',

        Binds: [
            '$onImport'
        ],

        options: {
            enablemobile : true,
            showmenuafter: 250 // show menu after 250ms mouseenter, 0 = show menu immediately
        },

        initialize: function (options) {
            this.parent(options);

            this.addEvents({
                onImport: this.$onImport
            });

            this.$liSize       = 0;
            this.$enter        = false;
            this.$Nav          = null;
            this.showMenuDelay = 250;
            this.isSimpleMenu = false;
        },

        /**
         * event: on import
         */
        $onImport: function () {
            this.$Nav = this.getElm().getElement('nav');

            if (parseInt(this.getAttribute('showmenuafter')) >= 0) {
                this.showMenuDelay = parseInt(this.getAttribute('showmenuafter'));
            }

            this.$Menu = new Element('div', {
                'class': 'quiqqer-menu-megaMenu-list-item-menu',
                styles : {
                    opacity: 0,
                    top    : 0
                },
                events : {
                    mouseenter: function () {
                        this.$enter = true;
                    }.bind(this),
                    mouseleave: function () {
                        this.$enter = false;
                        this.$hideCheck();
                    }.bind(this)
                }
            }).inject(this.getElm());

            this.$liSize = this.getElm().getSize().y;

            let self    = this,
                timeout = null;

            // fix for iPad, onclick doesn't work
            this.$Nav.querySelectorAll('[data-name="custom-event"]').forEach((CustomEventElm) => {
                const func = CustomEventElm.getAttribute('onclick');

                CustomEventElm.addEventListener('touchend', function(e) {
                    e.preventDefault();
                    eval(func);
                });
            });

            this.$Nav.getElements('.quiqqer-menu-megaMenu-list-item').addEvents({
                click   : function (event) {
                    event.stop();

                    var Target = event.target;

                    if (Target.nodeName !== 'LI') {
                        Target = Target.getParent('li');
                    }

                    let Link = Target.getElement('a');

                    if (Link) {
                        let TargetElm = self.$getAnchorTarget(Link);

                        if (TargetElm) {
                            self.$scrollToElement(TargetElm);
                            return;
                        }

                        window.location = Target.getElement('a').get('href');
                    }
                },
                touchend: function (event) {
                    event.stop();

                    var self   = this,
                        Target = event.target;

                    if (Target.nodeName !== 'LI') {
                        Target = Target.getParent('li');
                    }

                    let Link = Target.getElement('a');
                    var Menu = Target.getElement('.quiqqer-menu-megaMenu-list-item-menu');

                    if (!Menu) {
                        if (Link) {

                            let TargetElm = self.$getAnchorTarget(Link);

                            if (TargetElm) {
                                self.$scrollToElement(TargetElm);
                                return;
                            }

                            window.location = Target.getElement('a').get('href');
                            return;
                        }
                    }

                    if (this.$Menu.getStyle('display') === 'flex') {
                        window.location = Link.get('href');
                        return;
                    }

                    this.$Menu.set('tabindex', -1);
                    this.$Menu.setStyle('outline', 0);

                    this.$Menu.addEvent('blur', function () {
                        self.$enter = false;
                        self.$hideCheck();
                        self.$Menu.removeEvent('blur', this);
                    });

                    this.$enter = true;
                    this.showMenuFor(Target).then(function () {
                        this.$Menu.focus();
                    }.bind(this));
                }.bind(this),

                mouseenter: function (event) {
                    if (timeout !== null) {
                        clearTimeout(timeout);
                    }

                    timeout = setTimeout(() => {
                        var Target = event.target;

                        if (Target.nodeName != 'LI') {
                            Target = Target.getParent('li');
                        }

                        this.$enter = true;
                        this.showMenuFor(Target);
                    }, this.showMenuDelay);
                }.bind(this),

                mouseleave: function () {
                    if (timeout != null) {
                        clearTimeout(timeout);

                        timeout = null;
                    }

                    this.$enter = false;
                    this.$hideCheck();
                }.bind(this)
            });

            if (!this.getAttribute('enablemobile')) {
                return;
            }

            var SlideNode  = document.getElement('[data-slideOut="mobileMenu-SlideOut"]'),
                SlideOut   = QUI.Controls.getById(SlideNode.get('data-quiid')),
                MobileMenu = this.getElm().getElement('[data-name="quiqqer-menu-megaMenu-mobile-openerBtn"]');

            if (!MobileMenu) {
                return;
            }

            MobileMenu.addEvents({
                click: function () {
                    if (!SlideOut) {
                        SlideOut = QUI.Controls.getById(SlideNode.get('data-quiid'));
                    }

                    if (SlideOut) {
                        SlideOut.toggle();
                    }
                }
            });
        },

        /**
         * Shows the menu for the li element
         *
         * @param {HTMLLIElement} liElement
         * @return {Promise}
         */
        showMenuFor: function (liElement) {
            var Menu         = liElement.getElement('.quiqqer-menu-megaMenu-list-item-menu'),
                leftOffset   = 0,
                self         = this;

            this.isSimpleMenu = false;

            if (!Menu) {
                return this.$hide();
            }

            // only for simple menu
            if (Menu.getElement('.quiqqer-menu-megaMenu-children-simple')) {
                var megaMenu = this.getElm();
                leftOffset   = parseInt(liElement.getPosition(megaMenu).x);
                this.isSimpleMenu = true;
            }

            this.$Menu.set('html', Menu.get('html'));

            this.$Menu.getElements('a').addEvent('click', function (event) {
                var Link = event.target;

                if (Link.nodeName !== 'A') {
                    Link = Link.getParent('a');
                }

                event.stop();

                let TargetElm = self.$getAnchorTarget(Link);

                if (TargetElm) {
                    self.$scrollToElement(TargetElm);
                    return;
                }

                window.location = Link.get('href');
            });

            this.$Menu.getElements('li').addEvent('click', function (event) {
                var List = event.target;
                if (List.nodeName != 'LI') {
                    List = List.getParent('LI');
                }

                var children = List.getChildren('a');

                if (children.length) {
                    event.stop();
                    window.location = children[0].get('href');
                }
            });

            return this.$show(leftOffset);
        },

        /**
         * Show the menu
         * @returns {Promise}
         */
        $show: function (leftOffset) {
            this.$liSize = this.getElm().getSize().y;

            return new Promise(function (resolve) {

                const initStyles = {
                    display: 'flex',
                        top    : this.$liSize,
                        left   : leftOffset,
                        width  : '',
                    transform: null,
                    transformOrigin: null,
                };

                const animatedStyles = {
                    opacity: 1
                };

                if (this.isSimpleMenu) {
                    initStyles.width = 'auto';
                    initStyles.transform = 'scale(0.8)';
                    initStyles.transformOrigin = 'top left';
                    animatedStyles.transform = 'scale(1)';
                }

                this.$Menu.setStyles(initStyles);

                moofx(this.$Menu).animate(animatedStyles, {
                    duration: 200,
                    callback: resolve
                });
            }.bind(this));
        },

        /**
         * Show the menu
         * @returns {Promise}
         */
        $hide: function () {
            const animatedStyles = {
                opacity: 0
            };

            if (this.isSimpleMenu) {
                animatedStyles.transform = 'scale(0.8)';
            }

            return new Promise(function (resolve) {
                moofx(this.$Menu).animate(animatedStyles, {
                    duration: 200,
                    callback: function () {
                        this.$Menu.setStyles({
                            display: 'none'
                        });

                        resolve();
                    }.bind(this)
                });
            }.bind(this));
        },

        /**
         * Checks, if the menu must closed or not
         * the check is after 500ms
         */
        $hideCheck: function () {
            (function () {
                if (this.$enter === false) {
                    this.$hide();
                }
            }).delay(500, this);
        },

        /**
         * Get anchor target
         *
         * @param {HTMLElement} Link - <a> HTML node
         * @return {boolean|HTMLElement}
         */
        $getAnchorTarget: function (Link) {
            let href = Link.href;

            if (href.indexOf('#') === -1) {
                return false;
            }

            let targetString = href.substring(href.indexOf('#') + 1);

            if (targetString.length < 1) {
                return false;
            }

            let TargetElm = document.getElementById(targetString);

            if (!TargetElm) {
                return false;
            }

            return TargetElm;
        },

        /**
         * Scroll to given element (Target)
         *
         * @param {HTMLElement} Target
         */
        $scrollToElement: function (Target) {
            let offset = Target.get('data-qui-offset');

            if (!offset) {
                offset = window.SCROLL_OFFSET ? window.SCROLL_OFFSET : 0;
            }

            new Fx.Scroll(window, {
                offset: {
                    y: -offset
                }
            }).toElement(Target);
        }
    });
});
