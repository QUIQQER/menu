/**
 * @module package/quiqqer/menu/bin/Controls/NavTabsVertical
 * @author Dominik Chrzanowski
 *
 */
define('package/quiqqer/menu/bin/Controls/Tabs.Settings', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/windows/Confirm',
    'qui/controls/buttons/Button',
    'qui/controls/buttons/Switch',
    'Locale',
    'Mustache',
    'controls/grid/Grid',
    'utils/Controls',

    'text!package/quiqqer/menu/bin/Controls/Tabs.Settings.html',
    'css!package/quiqqer/menu/bin/Controls/Tabs.Settings.css'

], function (
    QUI,
    QUIControl,
    QUIConfirm,
    QUIButton,
    QUISwitch,
    QUILocale,
    Mustache,
    Grid,
    ControlsUtils,
    templateEntry
) {
    "use strict";

    var lg = 'quiqqer/menu';

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/menu/bin/Controls/Tabs.Settings',

        Binds: [
            '$onImport',
            '$openAddDialog',
            '$openDeleteDialog',
            '$openEditDialog',
            '$toggleSlideStatus',
            '$onDestroy',
            'resize',
            'update'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$Input = null;
            this.$Grid  = null;
            this.$Desktop = null;

            this.$data = [];

            this.addEvents({
                onImport : this.$onImport,
                onDestroy: this.$onDestroy
            });
        },

        /**
         * event: on import
         */
        $onImport: function () {
            this.$Input = this.getElm();

            this.$Elm = new Element('div', {
                'class': 'quiqqer-menu-navTabsVertival-settings',
                styles : {
                    clear   : 'both',
                    'float' : 'left',
                    height  : '100%',
                    minHeight: 400,
                    overflow: 'hidden',
                    position: 'relative',
                    margin  : 0,
                    width   : '100%'
                }
            }).wraps(this.$Input);

            // grid and sizes
            var size = this.$getAvailableSize();

            this.$Elm.setStyles({
                height: size.y,
                width : size.x
            });

            this.$Desktop = new Element('div', {
                styles: {
                    height: size.y,
                    width: size.x
                }
            }).inject(this.$Elm);

            this.$Grid = new Grid(this.$Desktop, {
                height     : size.y,
                width      : size.x,
                buttons    : [
                    {
                        name    : 'up',
                        icon    : 'fa fa-angle-up',
                        disabled: true,
                        events  : {
                            onClick: function () {
                                this.$Grid.moveup();
                                this.$refreshSorting();
                            }.bind(this)
                        }
                    }, {
                        name    : 'down',
                        icon    : 'fa fa-angle-down',
                        disabled: true,
                        events  : {
                            onClick: function () {
                                this.$Grid.movedown();
                                this.$refreshSorting();
                            }.bind(this)
                        }
                    }, {
                        type: 'separator'
                    }, {
                        name     : 'add',
                        textimage: 'fa fa-plus',
                        text     : QUILocale.get('quiqqer/core', 'add'),
                        events   : {
                            onClick: this.$openAddDialog
                        }
                    }, {
                        type: 'separator'
                    }, {
                        name     : 'edit',
                        textimage: 'fa fa-edit',
                        text     : QUILocale.get('quiqqer/core', 'edit'),
                        disabled : true,
                        events   : {
                            onClick: this.$openEditDialog
                        }
                    }, {
                        name     : 'delete',
                        textimage: 'fa fa-trash',
                        text     : QUILocale.get('quiqqer/core', 'delete'),
                        disabled : true,
                        events   : {
                            onClick: this.$openDeleteDialog
                        }
                    }
                ],
                columnModel: [
                    {
                        header   : QUILocale.get(lg, 'control.tabs.entries.isDisabled'),
                        dataIndex: 'isDisabledDisplay',
                        dataType : 'QUI',
                        width    : 80
                    }, {
                        dataIndex: 'isDisabled',
                        hidden   : true
                    }, {
                        header   : QUILocale.get(lg, 'control.tabs.entries.tabIcon'),
                        dataIndex: 'tabIconPreview',
                        dataType : 'node',
                        width    : 80
                    },
                    {
                        header   : QUILocale.get(lg, 'control.tabs.entries.tabTitle'),
                        dataIndex: 'tabTitle',
                        dataType : 'code',
                        width    : 120
                    },
                    {
                        header   : QUILocale.get(lg, 'control.tabs.entries.accentColor'),
                        dataIndex: 'accentColor',
                        dataType : 'code',
                        width    : 100
                    },
                    {
                        header   : QUILocale.get(lg, 'control.tabs.entries.entryImage'),
                        dataIndex: 'entryImagePreview',
                        dataType : 'node',
                        width    : 80
                    },
                    {
                        header   : QUILocale.get(lg, 'control.tabs.entries.entryImagePos'),
                        dataIndex: 'entryImagePos',
                        dataType : 'code',
                        width    : 100
                    },
                    {
                        header   : QUILocale.get(lg, 'control.tabs.entries.entryTitle'),
                        dataIndex: 'entryTitle',
                        dataType : 'code',
                        width    : 200
                    },
                    {
                        header   : QUILocale.get(lg, 'control.tabs.entries.entryContent'),
                        dataIndex: 'entryContent',
                        dataType : 'code',
                        width    : 300
                    },
                    {
                        dataIndex: 'newTab',
                        hidden   : true
                    }, {
                        dataIndex: 'image',
                        dataType : 'string',
                        hidden   : true
                    }
                ]
            });

            this.$Grid.addEvents({
                onClick: function () {
                    var buttons = this.$Grid.getButtons(),

                        Edit    = buttons.filter(function (Btn) {
                            return Btn.getAttribute('name') === 'edit';
                        })[0],

                        Up      = buttons.filter(function (Btn) {
                            return Btn.getAttribute('name') === 'up';
                        })[0],

                        Down    = buttons.filter(function (Btn) {
                            return Btn.getAttribute('name') === 'down';
                        })[0],

                        Delete  = buttons.filter(function (Btn) {
                            return Btn.getAttribute('name') === 'delete';
                        })[0];

                    Up.enable();
                    Down.enable();
                    Edit.enable();
                    Delete.enable();
                }.bind(this),

                onDblClick: this.$openEditDialog
            });

            this.$Grid.getElm().setStyles({
                position: 'absolute'
            });

            try {
                this.$data = JSON.decode(this.$Input.value);

                if (typeOf(this.$data) !== 'array') {
                    this.$data = [];
                }

                this.refresh();
            } catch (e) {
            }

            QUI.addEvent('resize', this.resize);
        },

        /**
         * event: on destroy
         */
        $onDestroy: function () {
            QUI.removeEvent('resize', this.resize);
        },

        /**
         * Toggles the slide's status between enabled and disabled
         *
         * @param {Object} [Caller] - the object calling this event
         */
        $toggleSlideStatus: function (Caller) {
            if (!Caller) {
                return;
            }

            // get cell number
            var row = Caller.getElm().getParent('li').get('data-row');

            this.$data[row].isDisabled = Caller.getStatus();
            this.update();
        },

        /**
         * Resize the control
         *
         * @return {Promise}
         */
        resize: function () {
            if (!this.$Grid || !this.$Elm || !this.$Desktop) {
                return Promise.resolve();
            }

            var size = this.$getAvailableSize();

            this.$Elm.setStyles({
                height: size.y,
                width : size.x
            });

            this.$Desktop.setStyles({
                height: size.y,
                width : size.x
            });

            this.$Grid.setHeight(size.y);

            return this.$Grid.setWidth(size.x).then(function () {
                this.$Grid.resize();
            }.bind(this));
        },

        /**
         * Return the available size for the settings grid.
         *
         * @return {Object}
         */
        $getAvailableSize: function () {
            var Container = this.$Elm.getParent('.quiqqer-bricks-container'),
                Parent    = this.$Elm.getParent(),
                width     = 0,
                height    = 0;

            if (Container) {
                var containerSize = Container.getSize(),
                    top           = this.$Elm.getPosition(Container).y;

                width  = containerSize.x;
                height = containerSize.y - top;
            }

            if (!width && Parent) {
                width = Parent.getSize().x;
            }

            if (!height && Parent) {
                height = Parent.getSize().y;
            }

            if (!width) {
                width = this.$Elm.getSize().x;
            }

            return {
                x: Math.max(width, 400),
                y: Math.max(height, 400)
            };
        },

        /**
         * refresh the display
         */
        refresh: function () {
            var i, len, entry, insert;
            var data = [];

            for (i = 0, len = this.$data.length; i < len; i++) {
                entry  = this.$data[i];
                insert = {
                    tabIcon        : '',
                    tabTitle : '',
                    accentColor : '',
                    entryImage       : '',
                    entryImagePos: '',
                    entryTitle   : '',
                    entryContent : '',
                    tabIconPreview : new Element('span', {html: '&nbsp;'}),
                    entryImagePreview: new Element('span', {html: '&nbsp;'})
                };

                entry.isDisabled = parseInt(entry.isDisabled);

                insert.isDisabled = entry.isDisabled;

                insert.isDisabledDisplay = new QUISwitch({
                    status: entry.isDisabled,
                    name  : i,
                    uid   : i,
                    events: {
                        onChange: this.$toggleSlideStatus
                    }
                });

                // tab icon
                if ("tabIcon" in entry && entry.tabIcon !== '') {
                    insert.tabIcon = entry.tabIcon;

                    if (entry.tabIcon.includes('fa ')) {
                        insert.tabIconPreview = new Element('span', {
                            'class': insert.tabIcon
                        });
                    } else {
                        insert.tabIconPreview = new Element('img', {
                            src   : URL_DIR + insert.tabIcon + '&maxwidth=50&maxheight=50',
                            width : 50,
                            height: 50
                        });
                    }
                }

                if ("tabTitle" in entry) {
                    insert.tabTitle = entry.tabTitle;
                }

                if ("accentColor" in entry) {
                    insert.accentColor = entry.accentColor;
                }

                // entry image
                if ("entryImage" in entry && entry.entryImage !== '') {
                    insert.entryImage = entry.entryImage;

                    if (entry.entryImage.includes('fa ')) {
                        insert.entryImagePreview = new Element('span', {
                            'class': insert.entryImage
                        });
                    } else {
                        insert.entryImagePreview = new Element('img', {
                            src   : URL_DIR + insert.entryImage + '&maxwidth=50&maxheight=50',
                            width : 50,
                            height: 50
                        });
                    }
                }

                if ("entryImagePos" in entry) {
                    insert.entryImagePos = entry.entryImagePos;
                }

                if ("entryTitle" in entry) {
                    insert.entryTitle = entry.entryTitle;
                }

                if ("entryContent" in entry) {
                    insert.entryContent = entry.entryContent;
                }

                data.push(insert);
            }

            this.$Grid.setData({
                data: data
            });

            var buttons = this.$Grid.getButtons(),

                Edit    = buttons.filter(function (Btn) {
                    return Btn.getAttribute('name') === 'edit';
                })[0],

                Up      = buttons.filter(function (Btn) {
                    return Btn.getAttribute('name') === 'up';
                })[0],

                Down    = buttons.filter(function (Btn) {
                    return Btn.getAttribute('name') === 'down';
                })[0],

                Delete  = buttons.filter(function (Btn) {
                    return Btn.getAttribute('name') === 'delete';
                })[0];

            Up.disable();
            Down.disable();
            Edit.disable();
            Delete.disable();
        },

        /**
         * Update the field
         */
        update: function () {
            this.$Input.value = JSON.encode(this.$data);
        },

        /**
         * Add an entry
         *
         * @param {Object} params
         */
        add: function (params) {
            var entry = {
                isDisabled   : 0,
                tabIcon      : '',
                tabTitle     : '',
                accentColor  : '',
                entryImage   : '',
                entryImagePos: '',
                entryTitle   : '',
                entryContent : ''
            };

            if ("isDisabled" in params) {
                entry.isDisabled = parseInt(params.isDisabled);
            }

            if ("tabIcon" in params && params.tabIcon !== '') {
                entry.tabIcon = params.tabIcon;
            }

            if ("tabTitle" in params) {
                entry.tabTitle = params.tabTitle;
            }

            if ("accentColor" in params) {
                entry.accentColor = params.accentColor;
            }

            if ("entryImage" in params && params.entryImage !== '') {
                entry.entryImage = params.entryImage;
            }

            if ("entryImagePos" in params) {
                entry.entryImagePos = params.entryImagePos;
            }

            if ("entryTitle" in params) {
                entry.entryTitle = params.entryTitle;
            }

            if ("entryContent" in params) {
                entry.entryContent = params.entryContent;
            }

            this.$data.push(entry);
            this.refresh();
            this.update();
        },

        /**
         * Edit an entry
         *
         * @param {number} index
         * @param {object} params
         */
        edit: function (index, params) {

            if (typeof index === 'undefined') {
                return;
            }

            var entry = {
                isDisabled   : 0,
                tabIcon      : '',
                tabTitle     : '',
                accentColor  : '',
                entryImage   : '',
                entryImagePos: '',
                entryTitle   : '',
                entryContent : ''
            };

            if ("isDisabled" in params) {
                entry.isDisabled = parseInt(params.isDisabled);
            }

            if ("tabIcon" in params && params.tabIcon !== '') {
                entry.tabIcon = params.tabIcon;
            }

            if ("tabTitle" in params) {
                entry.tabTitle = params.tabTitle;
            }

            if ("accentColor" in params) {
                entry.accentColor = params.accentColor;
            }

            if ("entryImage" in params && params.entryImage !== '') {
                entry.entryImage = params.entryImage;
            }

            if ("entryImagePos" in params) {
                entry.entryImagePos = params.entryImagePos;
            }

            if ("entryTitle" in params) {
                entry.entryTitle = params.entryTitle;
            }

            if ("entryContent" in params) {
                entry.entryContent = params.entryContent;
            }

            this.$data[index] = entry;

            this.refresh();
            this.update();
        },

        /**
         * Delete one entry or multiple entries
         *
         * @param {number|array} index
         */
        del: function (index) {
            var newList = [];

            if (typeOf(index) !== 'array') {
                index = [index];
            }

            for (var i = 0, len = this.$data.length; i < len; i++) {
                if (!index.contains(i)) {
                    newList.push(this.$data[i]);
                }
            }

            this.$data = newList;
        },

        /**
         * Set the used project
         *
         * @param {string|object} Project
         */
        setProject: function (Project) {
            this.setAttribute('project', Project);

            var controls = QUI.Controls.getControlsInElement(this.getElm());

            controls.each(function (Control) {
                if (Control === this) {
                    return;
                }

                if ("setProject" in Control) {
                    Control.setProject(Project);
                }
            }.bind(this));
        },

        /**
         * Refresh the data sorting in dependence of the grid
         */
        $refreshSorting: function () {
            var gridData = this.$Grid.getData(),
                data     = [];

            for (var i = 0, len = gridData.length; i < len; i++) {
                data.push({
                    isDisabled   : parseInt(gridData[i].isDisabled || 0),
                    tabIcon      : gridData[i].tabIcon || '',
                    tabTitle     : gridData[i].tabTitle || '',
                    accentColor  : gridData[i].accentColor || '',
                    entryImage   : gridData[i].entryImage || gridData[i].image || '',
                    entryImagePos: gridData[i].entryImagePos || '',
                    entryTitle   : gridData[i].entryTitle || '',
                    entryContent : gridData[i].entryContent || ''
                });
            }

            this.$data = data;
            this.update();
        },

        /**
         * Dialogs
         */

        /**
         * opens the delete dialog
         *
         * @return {Promise}
         */
        $openDeleteDialog: function () {
            new QUIConfirm({
                icon       : 'fa fa-icon',
                text       : QUILocale.get(lg, 'control.navTabsVertical.entries.delete.title'),
                information: QUILocale.get(lg, 'control.navTabsVertical.entries.delete.information'),
                texticon   : false,
                maxWidth   : 600,
                maxHeight  : 400,
                ok_button  : {
                    text     : QUILocale.get('quiqqer/core', 'delete'),
                    textimage: 'fa fa-trash'
                },
                events     : {
                    onSubmit: function () {
                        var selected = this.$Grid.getSelectedIndices();

                        this.$Grid.deleteRows(selected);
                        this.del(selected);
                        this.update();
                    }.bind(this)
                }
            }).open();
        },

        /**
         * Open edit dialog
         *
         * @retrun {Promise}
         */
        $openEditDialog: function () {
            var self  = this,
                data  = this.$Grid.getSelectedData(),
                index = this.$Grid.getSelectedIndices();

            if (!data.length) {
                return Promise.resolve();
            }

            data  = data[0];
            index = index[0];

            return this.$createDialog().then(function (Dialog) {

                Dialog.addEvent('onSubmit', function () {
                    Dialog.Loader.show();

                    var Content = Dialog.getContent();
                    var Form    = Content.getElement('form');

                    var isDisabled    = Dialog.IsDisabledSwitch.getStatus();
                    var TabIcon       = Form.elements.tabIcon;
                    var tabTitle      = Form.elements.tabTitle;
                    var accentColor   = Form.elements.accentColor;
                    var entryImage    = Form.elements.entryImage;
                    var entryImagePos = Form.elements.entryImagePos;
                    var entryTitle    = Form.elements.entryTitle;
                    var entryContent  = Form.elements.entryContent;

                    self.edit(index, {
                        isDisabled   : isDisabled,
                        tabIcon      : TabIcon.value,
                        tabTitle     : tabTitle.value,
                        accentColor  : accentColor.value,
                        entryImage   : entryImage.value,
                        entryImagePos: entryImagePos.value,
                        entryTitle   : entryTitle.value,
                        entryContent : entryContent.value,
                    });

                    Dialog.close();
                });


                Dialog.addEvent('onOpen', function () {

                    var Content = Dialog.getContent();
                    var Form    = Content.getElement('form');

                    var TabIcon       = Form.elements.tabIcon;
                    var tabTitle      = Form.elements.tabTitle;
                    var accentColor   = Form.elements.accentColor;
                    var entryImage    = Form.elements.entryImage;
                    var entryImagePos = Form.elements.entryImagePos;
                    var entryTitle    = Form.elements.entryTitle;
                    var entryContent  = Form.elements.entryContent;

                    if (data.isDisabled) {
                        Dialog.IsDisabledSwitch.on();
                    } else {
                        Dialog.IsDisabledSwitch.off();
                    }

                    TabIcon.value       = data.tabIcon;
                    tabTitle.value      = data.tabTitle;
                    accentColor.value   = data.accentColor;
                    entryImage.value    = data.entryImage;
                    entryImagePos.value = data.entryImagePos;
                    entryTitle.value    = data.entryTitle;
                    entryContent.value  = data.entryContent;

                    TabIcon.fireEvent('change');
                    tabTitle.fireEvent('change');
                    accentColor.fireEvent('change');
                    entryImage.fireEvent('change');
                    entryImagePos.fireEvent('change');
                    entryTitle.fireEvent('change');
                    entryContent.fireEvent('change');
                });

                Dialog.setAttribute('title', QUILocale.get(lg, 'control.navTabsVertical.entries.edit.title'));
                Dialog.open();
            });
        },

        /**
         * opens the add dialog
         *
         * @return {Promise}
         */
        $openAddDialog: function () {
            var self = this;

            return this.$createDialog().then(function (Dialog) {
                Dialog.addEvent('onSubmit', function () {
                    Dialog.Loader.show();

                    var Content = Dialog.getContent();
                    var Form    = Content.getElement('form');

                    var isDisabled    = Dialog.IsDisabledSwitch.getStatus();
                    var TabIcon       = Form.elements.tabIcon;
                    var tabTitle      = Form.elements.tabTitle;
                    var accentColor   = Form.elements.accentColor;
                    var entryImage    = Form.elements.entryImage;
                    var entryImagePos = Form.elements.entryImagePos;
                    var entryTitle    = Form.elements.entryTitle;
                    var entryContent  = Form.elements.entryContent;

                    self.add({
                        isDisabled   : isDisabled,
                        tabIcon      : TabIcon.value,
                        tabTitle     : tabTitle.value,
                        accentColor  : accentColor.value,
                        entryImage   : entryImage.value,
                        entryImagePos: entryImagePos.value,
                        entryTitle   : entryTitle.value,
                        entryContent : entryContent.value,

                    });

                    Dialog.close();
                });

                Dialog.open();
            });
        },

        /**
         * Create a edit / add entry dialog
         *
         * @return {Promise}
         */
        $createDialog: function () {
            var self = this;

            return new Promise(function (resolve) {
                var Dialog = new QUIConfirm({
                    title           : QUILocale.get(lg, 'control.navTabsVertical.entries.add.title'),
                    icon            : 'fa fa-edit',
                    maxWidth        : 800,
                    maxHeight       : 600,
                    autoclose       : false,
                    IsDisabledSwitch: false,
                    events          : {
                        onOpen: function (Win) {
                            Win.Loader.show();
                            Win.getContent().set('html', '');


                            var prefix    = 'control.tabs.entries.',
                                Container = new Element('div', {
                                    html   : Mustache.render(templateEntry, {
                                        isDisabled   : QUILocale.get(lg, prefix + 'isDisabled'),
                                        tabIcon      : QUILocale.get(lg, prefix + 'tabIcon'),
                                        tabTitle     : QUILocale.get(lg, prefix + 'tabTitle'),
                                        accentColor  : QUILocale.get(lg, prefix + 'accentColor'),
                                        entryImage   : QUILocale.get(lg, prefix + 'entryImage'),
                                        entryImagePos: QUILocale.get(lg, prefix + 'entryImagePos'),
                                        optionLeft   : QUILocale.get(lg, prefix + 'entryImagePos.left'),
                                        optionRight  : QUILocale.get(lg, prefix + 'entryImagePos.right'),
                                        optionTop    : QUILocale.get(lg, prefix + 'entryImagePos.top'),
                                        optionBottom : QUILocale.get(lg, prefix + 'entryImagePos.bottom'),
                                        entryTitle   : QUILocale.get(lg, prefix + 'entryTitle'),
                                        entryContent : QUILocale.get(lg, prefix + 'entryContent'),
                                    }),
                                    'class': 'quiqqer-menu-navTabsVertival-settings'
                                }).inject(Win.getContent());

                            var Text = Container.getElement('.field-entryContent');

                            Text.getParent().setStyles({
                                height: 100
                            });

                            Win.IsDisabledSwitch = new QUISwitch({
                                name  : 'isDisabled',
                                status: false
                            }).inject(Container.getElement('#isDisabledWrapper'));

                            QUI.parse(Container).then(function () {
                                return ControlsUtils.parse(Container);
                            }).then(function () {
                                var controls = QUI.Controls.getControlsInElement(Container),
                                    project  = self.getAttribute('project');

                                controls.each(function (Control) {
                                    if (Control === self) {
                                        return;
                                    }

                                    if ("setProject" in Control) {
                                        Control.setProject(project);
                                    }
                                });

                                Win.fireEvent('openAfterCreate', [Win]);

                                moofx(Container).animate({
                                    opacity: 1,
                                    top    : 0
                                }, {
                                    duration: 250,
                                    callback: function () {
                                        resolve(Container);
                                        Win.Loader.hide();
                                    }
                                });
                            });
                        }
                    }
                });

                resolve(Dialog);
            });
        }
    });
});
