/**
 * Sidebar menu control
 *
 * @module package/quiqqer/menu/bin/SidebarDropDownMenu
 * @author www.pcsg.de (Michael Danielczok, Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 */
define('package/quiqqer/menu/bin/SidebarDropDownMenu', [

    'qui/QUI',
    'qui/controls/Control'

], function (QUI, QUIControl)
{
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/menu/bin/SidebarDropDownMenu',

        Binds: [
            '$onImport'
        ],

        initialize: function (options)
        {
            this.parent(options);

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event : on insert
         */
        $onImport: function ()
        {
            var self         = this,
                Parent       = this.getElm(),
                ToggleButton = Parent.getElements(".quiqqer-fa-levels-icon");

            var runs = false;

            ToggleButton.addEvent("click", function ()
            {
                if (runs) {
                    return;
                }

                runs = true;

                var LiLeft = this.getParent('li');
                var NavSubLeft = LiLeft.getElement("div.quiqqer-sub-nav-div");
                var Prom;

                if (!NavSubLeft.getSize().y.toInt()) {
                    Prom = self.openMenu(NavSubLeft);
                } else {
                    Prom = self.closeMenu(NavSubLeft);
                }

                Prom.then(function ()
                {
                    runs = false;
                });
            });
        },

        /**
         * open the next level of sub menu
         *
         * @param {HTMLLIElement} NavSubLeft
         *
         * @return Promise
         */
        openMenu: function (NavSubLeft)
        {
            var Prev = NavSubLeft.getPrevious('.quiqqer-navigation-entry'),
                Icon = Prev.getChildren('.quiqqer-fa-levels-icon');

            if (Icon.hasClass('fa-angle-double-right')) {
                Icon.addClass("fa-nav-levels-rotate");
            }

            return new Promise(function (resolve)
            {
                NavSubLeft.setStyles({
                    height  : 0,
                    opacity : 0,
                    overflow: "hidden",
                    display : "block"
                });

                moofx(NavSubLeft).animate({
                    height : NavSubLeft.getElement("ul").getSize().y.toInt(),
                    opacity: 1
                }, {
                    duration: 200,
                    callback: function ()
                    {
                        NavSubLeft.setStyle('height', null);

                        resolve();
                    }
                });
            });
        },

        /**
         * close the next level of sub menu
         *
         * @param {HTMLLIElement} NavSubLeft
         *
         * @return Promise
         */
        closeMenu: function (NavSubLeft)
        {
            var Prev = NavSubLeft.getPrevious('.quiqqer-navigation-entry'),
                Icon = Prev.getChildren('.quiqqer-fa-levels-icon');

            Icon.removeClass("fa-nav-levels-rotate");

            return new Promise(function (resolve)
            {
                NavSubLeft.setStyle("overflow", "hidden");
                NavSubLeft.setStyle("height", NavSubLeft.getSize().y);

                moofx(NavSubLeft).animate({
                    height : 0,
                    opacity: 0
                }, {
                    duration: 200,
                    callback: function ()
                    {
                        resolve();
                    }
                });
            });
        }
    });
});
