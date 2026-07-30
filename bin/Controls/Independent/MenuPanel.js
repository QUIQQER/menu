/**
 * @module package/quiqqer/menu/bin/Controls/Independent/MenuPanel
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/menu/bin/Controls/Independent/MenuPanel', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'qui/controls/sitemap/Map',
    'qui/controls/sitemap/Item',
    'qui/controls/windows/Confirm',
    'qui/controls/contextmenu/Menu',
    'qui/controls/contextmenu/Item',
    'qui/controls/contextmenu/Separator',
    'controls/lang/InputMultiLang',
    'Ajax',
    'Locale',
    'package/quiqqer/menu/bin/classes/IndependentHandler',

    'Mustache',
    'text!package/quiqqer/menu/bin/Controls/Independent/MenuPanel.ItemCard.html',
    'text!package/quiqqer/menu/bin/Controls/Independent/MenuPanel.Settings.html',
    'css!package/quiqqer/menu/bin/Controls/Independent/MenuPanel.css'

], function (QUI, QUIPanel, QUIMap, QUIMapItem, QUIConfirm,
             QUIContextMenu, QUIContextMenuItem, QUIContextSeparator, InputMultiLang,
             QUIAjax, QUILocale, IndependentHandler, Mustache, templateItemCard, templateSettings) {
    "use strict";

    const lg = 'quiqqer/menu';

    return new Class({

        Extends: QUIPanel,
        Type   : 'package/quiqqer/menu/bin/Controls/Independent/MenuPanel',

        Binds: [
            '$onShow',
            '$onCreate',
            '$onInject',
            '$openItem',
            '$onContextMenu',
            '$toggleItemStatus',
            '$startDeSelect',
            '$openMenuSettings',
            'addItem',
            'save',
        ],

        options: {
            menuId: false
        },

        initialize: function (options) {
            this.parent(options);

            this.$ActiveItem = null;
            this.$ActiveMapItem = null;

            this.$title = null;
            this.$workingTitle = null;
            this.$data = null;

            this.$Map = null;
            this.setAttribute('#id', this.getAttribute('menuId'));

            this.addEvents({
                onShow  : this.$onShow,
                onInject: this.$onInject,
                onCreate: this.$onCreate
            });
        },

        $onCreate: function () {
            this.getContent().addClass('quiqqer-menu-menuPanel');

            // buttons
            this.addButton({
                name  : 'save',
                text  : QUILocale.get('quiqqer/core', 'save'),
                events: {
                    click: this.save
                }
            });

            /*
            this.addButton({
                name  : 'add',
                text  : QUILocale.get('quiqqer/core', 'add'),
                events: {
                    click: () => {
                        this.addItem();
                    }
                }
            });
            */

            this.addButton({
                textimage: 'fa fa-paint-brush',
                name     : 'clearcache',
                text     : QUILocale.get(lg, 'cache.clear.button.title'),
                styles   : {
                    'float': 'right'
                },
                events   : {
                    click: (Btn) => {
                        Btn.disable();
                        Btn.setAttribute('textimage', 'fa fa-spinner fa-spin');
                        QUIAjax.post('package_quiqqer_menu_ajax_backend_independent_clearCache', () => {
                            Btn.setAttribute('textimage', 'fa fa-bars');
                            Btn.enable();

                            QUI.getMessageHandler(function (QUI) {
                                QUI.addSuccess(
                                    QUILocale.get('quiqqer/menu', 'message.menu.publish.successful')
                                );
                            });
                        }, {
                            'package': 'quiqqer/menu',
                            menuId   : this.getAttribute('menuId')
                        });
                    }
                }
            });

            /*
            this.addButton({
                name    : 'delete',
                disabled: true,
                text    : QUILocale.get('quiqqer/core', 'remove'),
                events  : {
                    click: this.addItem
                }
            });
            */

            // content
            this.$MapContainer = new Element('div', {
                'class': 'quiqqer-menu-menuPanel-mapContainer'
            }).inject(this.getContent());

            this.$InnerContainer = new Element('div', {
                'class': 'quiqqer-menu-menuPanel-innerContainer'
            }).inject(this.getContent());

            this.$Map = new QUIMap({}).inject(this.$MapContainer);
        },

        $onInject: function () {
            this.Loader.show();

            Promise.all([
                IndependentHandler.getMenu(this.getAttribute('menuId')),
                this.$getMenuData()
            ]).then((result) => {
                const menuData = result[0];
                const data = result[1];

                this.$data = menuData;
                this.$title = data.title;
                this.$workingTitle = data.workingTitle;

                this.setAttribute('title', menuData.title);
                this.setAttribute('icon', 'fa fa-bars');
                this.refresh();

                const Start = new QUIMapItem({
                    value : 'start',
                    icon  : 'fa fa-home',
                    text  : menuData.title,
                    events: {
                        click   : this.$openMenuSettings,
                        deSelect: this.$startDeSelect,

                        contextMenu: (Item, event) => {
                            event.stop();

                            const pos = Item.getElm().getPosition();
                            const Menu = new QUIContextMenu({
                                events: {
                                    onBlur: function (Instance) {
                                        Instance.hide();
                                        Instance.destroy();
                                    }
                                }
                            });

                            Menu.appendChild(new QUIContextMenuItem({
                                icon  : 'fa fa-level-down',
                                text  : QUILocale.get(lg, 'context.menu.insertChild'),
                                events: {
                                    click: () => {
                                        this.addItem(Item);
                                    }
                                }
                            }));

                            if (Item.getChildren().length > 1) {
                                Menu.appendChild(new QUIContextMenuItem({
                                    icon  : 'fa fa-sort',
                                    text  : QUILocale.get(lg, 'context.menu.sort'),
                                    events: {
                                        click: () => {
                                            this.sortChildren(Item);
                                        }
                                    }
                                }));
                            }

                            Menu.inject(document.body);
                            Menu.setPosition(pos.x, pos.y + 30);
                            Menu.setTitle(Item.getAttribute('text'));
                            Menu.show();
                            Menu.focus();
                        }
                    }
                }).inject(this.$Map);

                Start.click();

                // build sitemap
                if (menuData.data !== null &&
                    typeof menuData.data.children !== 'undefined' &&
                    menuData.data.children.length
                ) {
                    const buildChildren = (Parent, children) => {
                        let i, len, data, Item, text;

                        for (i = 0, len = children.length; i < len; i++) {
                            data = children[i];
                            text = data.titleFrontend;

                            if (text === '' || text === '###') {
                                text = '?';
                            }

                            Item = new QUIMapItem({
                                text     : text,
                                icon     : data.typeIcon,
                                itemTitle: data.title,
                                itemType : data.type,
                                itemIcon : data.icon,
                                itemData : data.data,
                                events   : {
                                    click      : this.$openItem,
                                    contextMenu: this.$onContextMenu
                                }
                            });

                            Parent.appendChild(Item);

                            if (typeof data.data.status !== 'undefined' && data.data.status === 0) {
                                Item.deactivate();
                            }

                            if (typeof data.children !== 'undefined' && data.children.length) {
                                buildChildren(Item, data.children);
                            }
                        }
                    };

                    buildChildren(Start, menuData.data.children);
                }

                Start.open();
                this.Loader.hide();
            });
        },

        $onShow: function () {

        },

        save: function () {
            this.Loader.show();
            this.$unloadCurrentItem();

            // map to array
            const toArray = function (Item) {
                let items = Item.getChildren();

                if (Item.getType() === 'qui/controls/sitemap/Map') {
                    items = Item.$items;
                }

                let children = [];

                for (let i = 0, len = items.length; i < len; i++) {
                    children.push(toArray(items[i]));
                }

                if (Item.getType() === 'qui/controls/sitemap/Map') {
                    return {
                        children: children
                    };
                }

                return {
                    icon    : Item.getAttribute('itemIcon'),
                    title   : Item.getAttribute('itemTitle'),
                    type    : Item.getAttribute('itemType'),
                    data    : Item.getAttribute('itemData'),
                    children: children
                };
            };

            this.setMenuTitle();

            let result = toArray(this.$Map.firstChild());

            return this.$refreshItemDisplay().then(() => {
                return IndependentHandler.saveMenu(
                    this.getAttribute('menuId'),
                    JSON.encode(this.$title),
                    JSON.encode(this.$workingTitle),
                    result
                );
            }).then(() => {
                this.Loader.hide();
            });
        },

        /**
         * Set the title of this.$Map, so the title changes in the left panel.
         * Also set menu title and working title to the variables:
         *   this.$title
         *   this.$workingTitle
         *
         * This function is a small workaround for this user flow:
         *   "User change the menu title (and working title) and save directly the menu by clicking save-button".
         */
        setMenuTitle: function() {
            const selected = this.$Map.getSelectedChildren();

            if (selected.length === 1 && selected[0].getAttribute('value') !== 'start') {
                return;
            }

            const Title = this.$InnerContainer.getElement('[name="title"]');
            const WorkingTitle = this.$InnerContainer.getElement('[name="workingTitle"]');

            if (!Title) {
                return;
            }

            selected[0].setAttribute('itemTitle', Title.value);

            this.$title = JSON.decode(Title.value);
            this.$workingTitle = JSON.decode(WorkingTitle.value);

            let current = QUILocale.getCurrent();

            if (current in this.$title) {
                selected[0].setAttribute('text', this.$title[current]);
                selected[0].setAttribute('title', this.$title[current]);
            }
        },

        addItem: function (Parent, where) {
            if (typeof where === 'undefined') {
                where = 'bottom';
            }

            if (typeof Parent === 'undefined') {
                const items = this.$Map.getSelectedChildren();

                if (items.length) {
                    Parent = items[0];
                } else {
                    Parent = this.$Map;
                }
            }

            const submitAddItem = (Win) => {
                const Content = Win.getContent();
                const Form = Content.getElement('form');
                const type = Form.elements.itemType.value;

                if (!type) {
                    return;
                }

                const Option = Form.getElement(
                    '[value="' + CSS.escape(type) + '"]'
                );

                if (!Option) {
                    return;
                }

                let itemAttributes = {
                    text     : '',
                    icon     : Option.get('data-icon'),
                    itemTitle: '',
                    itemType : Form.elements.itemType.value,
                    itemIcon : '',
                    events   : {
                        click      : this.$openItem,
                        contextMenu: this.$onContextMenu
                    }
                };

                let Child;

                if (where === 'bottom') {
                    Child = new QUIMapItem(itemAttributes);
                    Parent.appendChild(Child);

                    if (typeof Parent === 'function') {
                        Parent.open();
                    }
                } else {
                    Child = new QUIMapItem(itemAttributes);
                    Parent.appendChild(Child, where);
                }

                Win.close();

                this.save().then(() => {
                    Child.click();
                    this.$ActiveMapItem = Child;

                    return this.$refreshItemDisplay();
                });
            };

            new QUIConfirm({
                icon     : 'fa fa-plus',
                title    : QUILocale.get(lg, 'quiqqer.menu.independent.addItem.title'),
                maxHeight: 550,
                maxWidth : 700,
                autoclose: false,
                events   : {
                    onOpen: (Win) => {
                        const Content = Win.getContent();

                        Win.Loader.show();

                        Content.set(
                            'html',

                            `<p>`+ QUILocale.get(lg, 'create.window.text') +
                            '</p>' +
                            '<form style="width: 100%;">' +
                            '   <div class="qui-menuPanel-optionList"></div>' +
                            '</form>'
                        );

                        IndependentHandler.getItemTypes().then((list) => {
                            this.$createOptionList(list, Content, false, () => {
                                Win.submit();
                            });

                            return QUI.parse(Win.getContent());
                        }).then(function () {
                            Win.Loader.hide();
                        });
                    },

                    onSubmit: submitAddItem
                }
            }).open();
        },

        /**
         * opens the item type change confirm window
         *
         * @param Item
         */
        changeItemType: function (Item) {
            if (typeof Item === 'undefined') {
                return;
            }

            const itemType = Item.getAttribute('itemType');

            new QUIConfirm({
                icon     : 'fa fa-edit',
                title    : QUILocale.get(lg, 'quiqqer.menu.independent.changeItemType.title'),
                maxHeight: 550,
                maxWidth : 700,
                autoclose: false,
                events   : {
                    onOpen: (Win) => {
                        const Content = Win.getContent();

                        Content.set(
                            'html',

                            `<p>`+ QUILocale.get(lg, 'quiqqer.menu.independent.changeItemType.message') +
                            '</p>' +
                            '<form style="width: 100%;">' +
                            '   <div class="qui-menuPanel-optionList"></div>' +
                            '</form>'
                        );

                        Win.Loader.show();

                        IndependentHandler.getItemTypes().then((list) => {
                            this.$createOptionList(list, Content, itemType);

                            return QUI.parse(Win.getContent());
                        }).then(function () {
                            Win.Loader.hide();
                        });
                    },

                    onSubmit: (Win) => {
                        Win.Loader.show();

                        const Content = Win.getContent();
                        const Form = Content.getElement('form');

                        const type = Form.elements.itemType.value;
                        const Option = Form.getElement(
                            '[value="' + CSS.escape(type) + '"]'
                        );

                        Item.setAttribute('itemType', type);
                        Item.setAttribute('icon', Option.get('data-icon'));

                        if (this.$ActiveMapItem && this.$ActiveMapItem === Item) {
                            Item.click();
                            this.$unloadCurrentItem();
                            this.$refreshItemDisplay().catch(console.error);
                        }

                        Win.close();
                    }
                }
            }).open();
        },

        /**
         * Create custom option list based on passed list of menu types
         *
         * @param list
         * @param Content
         * @param selected
         * @param onDoubleClick
         */
        $createOptionList: function(list, Content, selected = false, onDoubleClick = null) {
            const OptionsList = Content.getElement('.qui-menuPanel-optionList'),
                selectedItem = selected ? selected : 'QUI\\Menu\\Independent\\Items\\Site';

            const toggleSelected = function(event) {
                OptionsList.getElements('label').forEach(function(Label) {
                    Label.classList.remove('checked');
                });

                event.target.getParent('label').classList.add('checked');
            };

            for (let i = 0, len = list.length; i < len; i++) {
                const Item = list[i];
                const Label = new Element('label', {
                    'class': 'qui-menuPanel-optionList__option',
                    html: Mustache.render(templateItemCard, {
                        title: Item.title,
                        desc: Item.desc,
                        icon: Item.icon,
                        value: Item.class
                    })
                });

                if (Item.class === selectedItem) {
                    Label.querySelector('input').checked = true;
                    Label.classList.add('checked');
                }

                Label.inject(OptionsList);
                Label.getElement('input').addEventListener('change', toggleSelected);

                if (typeof onDoubleClick === 'function') {
                    Label.addEventListener('dblclick', () => {
                        const Input = Label.querySelector('input');

                        if (!Input) {
                            return;
                        }

                        Input.checked = true;
                        Label.classList.add('checked');
                        OptionsList.getElements('label').forEach(function(CurrentLabel) {
                            if (CurrentLabel !== Label) {
                                CurrentLabel.classList.remove('checked');
                            }
                        });

                        onDoubleClick();
                    });
                }
            }
        },

        /**
         * @param Item
         */
        sortChildren: function (Item) {
            if (typeof Item === 'undefined') {
                return;
            }

            require([
                'package/quiqqer/menu/bin/Controls/Independent/MenuItemsSorting'
            ], function (MenuItemSorting) {
                new MenuItemSorting({
                    Item  : Item,
                    events: {
                        onSubmit: function (Instance, list) {
                            Item.$items = list;
                            Item.refresh();
                        }
                    }
                }).open();
            });
        },

        /**
         * opens the item - display the item data
         *
         * @param Item
         */
        $openItem: function (Item) {
            this.Loader.show();
            this.$unloadCurrentItem();

            return this.$refreshItemDisplay().then(() => {
                this.$InnerContainer.set('html', '');
                this.$ActiveItem = null;
                this.$ActiveMapItem = null;

                return IndependentHandler.getItemTypes();
            }).then((list) => {
                let control = '';
                let type = Item.getAttribute('itemType');

                for (let i = 0, len = list.length; i < len; i++) {
                    if (list[i].class === type) {
                        control = list[i].jsControl;
                        break;
                    }
                }

                if (control === '') {
                    this.Loader.hide();
                    return;
                }

                require([control], (cls) => {
                    const SitemapItem = this.$getActiveSitemapItem();
                    this.$ActiveMapItem = this.$getActiveSitemapItem();

                    this.$ActiveItem = new cls({
                        title : SitemapItem.getAttribute('itemTitle'),
                        type  : SitemapItem.getAttribute('itemType'),
                        icon  : SitemapItem.getAttribute('itemIcon'),
                        data  : SitemapItem.getAttribute('itemData'),
                        events: {
                            load: () => {
                                this.Loader.hide();
                            }
                        }
                    }).inject(this.$InnerContainer);
                }, (err) => {
                    console.error(err);
                    this.Loader.hide();
                });
            });
        },

        $openMenuSettings: function () {
            this.Loader.show();
            this.$unloadCurrentItem();

            return this.$refreshItemDisplay().then(() => {
                this.$ActiveItem = null;
                this.$ActiveMapItem = null;
                this.$InnerContainer.set('html', Mustache.render(templateSettings, {
                    title        : QUILocale.get('quiqqer/menu', 'menu.title'),
                    workingTitle: QUILocale.get('quiqqer/menu', 'menu.workingTitle')
                }));

                const TitleNode = this.$InnerContainer.getElement('[name="title"]'),
                WorkingTitleNode = this.$InnerContainer.getElement('[name="workingTitle"]');

                TitleNode.set('value', JSON.stringify(this.$title));
                WorkingTitleNode.set('value', JSON.stringify(this.$workingTitle));
            }).then(() => {
                return QUI.parse(this.$InnerContainer);
            }).then(() => {
                // set values

                this.Loader.hide();
            });
        },

        $startDeSelect: function () {
            const Title = this.$InnerContainer.getElement('[name="title"]');
            const WorkingTitle = this.$InnerContainer.getElement('[name="workingTitle"]');

            if (!Title) {
                return;
            }

            // save first item
            const selected = this.$Map.getSelectedChildren();

            if (selected.length === 1 && selected[0].getAttribute('value') === 'start' && Title) {
                selected[0].setAttribute('itemTitle', Title.value);

                this.$title = JSON.decode(Title.value);
                this.$workingTitle = JSON.decode(WorkingTitle.value);

                let current = QUILocale.getCurrent();

                if (current in this.$title) {
                    selected[0].setAttribute('text', this.$title[current]);
                    selected[0].setAttribute('title', this.$title[current]);
                }
            }
        },

        /**
         * Unloads the current sitemap item
         * The save method of the current active control is executed
         */
        $unloadCurrentItem: function () {
            if (!this.$ActiveItem) {
                return;
            }

            if (typeof this.$ActiveItem.save !== 'function') {
                return;
            }

            const data = this.$ActiveItem.save();

            if (!this.$ActiveMapItem) {
                return;
            }

            const ActiveItem = this.$ActiveMapItem;

            ActiveItem.setAttribute('itemTitle', data.title);
            ActiveItem.setAttribute('itemIcon', data.icon);
            ActiveItem.setAttribute('itemData', data.data);
        },

        /**
         * Refresh the name of the active map item
         *
         * @returns {Promise}
         */
        $refreshItemDisplay: function () {
            const Item = this.$ActiveMapItem;

            if (Item === null) {
                return Promise.resolve();
            }

            // check status
            const data = Item.getAttribute('itemData');

            if (typeof data.status !== 'undefined' && data.status === 0) {
                Item.deactivate();
            } else {
                Item.activate();
            }

            // check name
            return new Promise(function (resolve, reject) {
                QUIAjax.get('package_quiqqer_menu_ajax_backend_independent_getItemTitle', (name) => {
                    if (name === '') {
                        name = '?';
                    }

                    Item.setAttribute('text', name);
                    resolve();
                }, {
                    'package': 'quiqqer/menu',
                    item     : JSON.encode({
                        icon : Item.getAttribute('itemIcon'),
                        title: Item.getAttribute('itemTitle'),
                        type : Item.getAttribute('itemType'),
                        data : Item.getAttribute('itemData'),
                    }),
                    onError  : reject
                });
            });
        },

        /**
         * Return the active item of the sitemap
         *
         * @returns {null|*}
         */
        $getActiveSitemapItem: function () {
            const item = this.$Map.getSelectedChildren();

            if (item.length) {
                return item[0];
            }

            return null;
        },

        /**
         * Toggle the active status of a sitemap item and save immediately
         *
         * @param Item
         */
        $toggleItemStatus: function (Item) {
            let data = Item.getAttribute('itemData');

            if (typeof data !== 'object' || data === null) {
                data = {};
            }

            const newStatus = (typeof data.status !== 'undefined' && data.status === 0) ? 1 : 0;
            data.status = newStatus;

            Item.setAttribute('itemData', data);

            if (newStatus === 0) {
                Item.deactivate();
            } else {
                Item.activate();
            }

            // if the item is currently open in the form, reload it so the
            // status checkbox stays in sync and does not overwrite the new
            // status on the next unload
            if (this.$ActiveMapItem && this.$ActiveMapItem === Item) {
                this.$ActiveItem = null;
                this.$openItem(Item).then(() => {
                    return this.save();
                });

                return;
            }

            this.save();
        },

        /**
         * Context menu für a sitemap item
         *
         * @param Item
         * @param event
         */
        $onContextMenu: function (Item, event) {
            event.stop();

            const pos = Item.getElm().getPosition();

            const Menu = new QUIContextMenu({
                events: {
                    onBlur: function (Instance) {
                        Instance.hide();
                        Instance.destroy();
                    }
                }
            });

            Menu.appendChild(new QUIContextMenuItem({
                icon  : 'fa fa-arrow-up',
                text  : QUILocale.get(lg, 'context.menu.insertBefore'),
                events: {
                    click: () => {
                        this.addItem(Item, 'before');
                    }
                }
            }));

            Menu.appendChild(new QUIContextMenuItem({
                icon  : 'fa fa-arrow-down',
                text  : QUILocale.get(lg, 'context.menu.insertAfter'),
                events: {
                    click: () => {
                        this.addItem(Item, 'after');
                    }
                }
            }));

            Menu.appendChild(new QUIContextMenuItem({
                icon  : 'fa fa-level-down',
                text  : QUILocale.get(lg, 'context.menu.insertChild'),
                events: {
                    click: () => {
                        this.addItem(Item);
                    }
                }
            }));

            Menu.appendChild(new QUIContextSeparator({}));

            Menu.appendChild(new QUIContextMenuItem({
                icon  : 'fa fa-edit',
                text  : QUILocale.get(lg, 'context.menu.changeType'),
                events: {
                    click: () => {
                        this.changeItemType(Item);
                    }
                }
            }));

            const itemData = Item.getAttribute('itemData');
            const isActive = !itemData || typeof itemData.status === 'undefined' || itemData.status !== 0;

            Menu.appendChild(new QUIContextMenuItem({
                icon  : isActive ? 'fa fa-toggle-off' : 'fa fa-toggle-on',
                text  : QUILocale.get(lg, isActive ? 'context.menu.deactivate' : 'context.menu.activate'),
                events: {
                    click: () => {
                        this.$toggleItemStatus(Item);
                    }
                }
            }));

            if (Item.getChildren().length > 1) {
                Menu.appendChild(new QUIContextMenuItem({
                    icon  : 'fa fa-sort',
                    text  : QUILocale.get(lg, 'context.menu.sort'),
                    events: {
                        click: () => {
                            this.sortChildren(Item);
                        }
                    }
                }));
            }

            Menu.appendChild(new QUIContextSeparator({}));

            Menu.appendChild(new QUIContextMenuItem({
                icon  : 'fa fa-trash',
                text  : QUILocale.get(lg, 'context.menu.deleteChild'),
                events: {
                    click: () => {
                        new QUIConfirm({
                            icon       : 'fa fa-trash',
                            texticon   : 'fa fa-trash',
                            title      : QUILocale.get(lg, 'window.deleteItem.title'),
                            information: QUILocale.get(lg, 'window.deleteItem.information', {
                                entry: Item.getAttribute('text')
                            }),
                            text       : QUILocale.get(lg, 'window.deleteItem.text'),
                            maxHeight  : 300,
                            maxWidth   : 500,
                            events     : {
                                submit: () => {
                                    Item.destroy();
                                    this.save();
                                }
                            }
                        }).open();
                    }
                }
            }));

            Menu.inject(document.body);
            Menu.setPosition(pos.x, pos.y + 30);
            Menu.setTitle(Item.getAttribute('text'));
            Menu.show();
            Menu.focus();
        },

        $getMenuData: function () {
            return new Promise((resolve, reject) => {
                QUIAjax.get('package_quiqqer_menu_ajax_backend_independent_getData', resolve, {
                    'package': 'quiqqer/menu',
                    id       : this.getAttribute('menuId'),
                    onError  : reject
                });
            });
        }
    });
});
