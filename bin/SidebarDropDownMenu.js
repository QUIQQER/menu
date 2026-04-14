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
            const Parent = this.getElm();
            const ToggleButtons = Parent.querySelectorAll(".quiqqer-fa-levels-icon");
            let runs = false;

            ToggleButtons.forEach((ToggleButton) => {
                ToggleButton.addEventListener("click", () => {
                    if (runs) {
                        return;
                    }

                    const LiLeft = ToggleButton.closest("li");

                    if (!LiLeft) {
                        return;
                    }

                    const NavSubLeft = LiLeft.querySelector("div.quiqqer-sub-nav-div");

                    if (!NavSubLeft) {
                        return;
                    }

                    runs = true;

                    let Prom;

                    if (!NavSubLeft.getBoundingClientRect().height) {
                        Prom = this.openMenu(NavSubLeft);
                    } else {
                        Prom = this.closeMenu(NavSubLeft);
                    }

                    Prom.then(() => {
                        runs = false;
                    });
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
            const Prev = NavSubLeft.previousElementSibling;
            const Icon = Prev ? Prev.querySelector('.quiqqer-fa-levels-icon') : null;
            const List = NavSubLeft.querySelector("ul");

            if (Icon && Icon.classList.contains('fa-angle-double-right')) {
                Icon.classList.add("fa-nav-levels-rotate");
            }

            return new Promise((resolve) => {
                if (List) {
                    List.style.display = "flow-root";
                }

                NavSubLeft.style.height = "auto";
                NavSubLeft.style.opacity = "0";
                NavSubLeft.style.overflow = "hidden";
                NavSubLeft.style.display = "block";

                const targetHeight = NavSubLeft.scrollHeight;

                NavSubLeft.style.height = "0";

                moofx(NavSubLeft).animate({
                    height : targetHeight,
                    opacity: 1
                }, {
                    duration: 200,
                    callback: () => {
                        NavSubLeft.style.height = "";
                        NavSubLeft.style.overflow = "";

                        if (List) {
                            List.style.display = "";
                        }

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
            const Prev = NavSubLeft.previousElementSibling;
            const Icon = Prev ? Prev.querySelector('.quiqqer-fa-levels-icon') : null;

            if (Icon) {
                Icon.classList.remove("fa-nav-levels-rotate");
            }

            return new Promise((resolve) => {
                NavSubLeft.style.overflow = "hidden";
                NavSubLeft.style.height = NavSubLeft.getBoundingClientRect().height + "px";

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
