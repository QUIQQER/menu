/**
 * Navigation tabs control
 *
 * Every nav tab content has an url conform ID (title, it comes from brick entries).
 * You can use it to target and auto open this element. Simply place `#open_` before your title in the url.
 * The page will be scrolled to the element if it is not in viewport.
 *
 * Example: <a href="www.example.com/subpage#open_myTarget">Open "myTarget" element</a>
 *
 * @module package/quiqqer/menu/bin/Controls/NavTabs
 * @author www.pcsg.de (Michael Danielczok)
 */
define('package/quiqqer/menu/bin/Controls/Tabs', [

    'qui/QUI',
    'qui/controls/Control',
    'Locale',

    URL_OPT_DIR + 'bin/quiqqer-asset/animejs/animejs/lib/anime.min.js',

], function (QUI, QUIControl, QUILocale, animejs) {
    "use strict";

    const lg = 'quiqqer/menu';

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/menu/bin/Controls/Tabs',

        Binds: [
            '$onImport',
            '$resize',
            'toggle',
            '$onVisibilityChange',
            '$onViewportIntersection',
            '$onDestroy',
            '$onMouseEnter',
            '$onMouseLeave',
            '$mouseMoveHandler',
            '$mouseDownHandler',
            '$mouseUpHandler'
        ],

        options: {
            animation         : 'scaleToLargeScaleFromSmall',
            animationscaleout: '0.95',
            animationscalein : '1.05',
            animationmove: '10px',
            enabledragtoscroll: false, // if enabled allows users to drag to scroll nav elements
            scrollduration    : 400,   // ms, duration for nav auto-scroll
            dragThreshold     : 6,     // px, minimal movement to count as drag
            autoplay          : false, // automatically switch tabs
            autoplayinterval  : 5000,  // ms, duration until automatic switch (= progress duration)
            pauseonhover      : false, // pause autoplay while hovering the whole control
            showprogress      : true   // show progress bar below the tabs
        },

        /**
         * Constructor hook called by QUI.
         * Sets up initial state and global event listeners.
         *
         * @param {Object} options - Optional configuration passed from the outside.
         */
        initialize: function (options) {
            this.parent(options);

            this.navTab              = false;
            this.navTabsItems        = false;
            this.navContents         = false;
            this.NavContentContainer = null;
            this.ActiveNavTab        = null;
            this.ActiveContent       = null;
            this.clicked             = false;
            this.progresElms = [];

            // drag to scroll
            this.enableDragToScroll = false;
            this.navPos             = {left: 0, x: 0};
            this.isDragging         = false;
            this.dragStartX         = 0;

            // progress / autoplay state
            this.isPaused     = false;
            this._autoPausedByVisibility = false;
            this._autoPausedByViewport = false;
            this._autoPausedByHover = false;
            this._isInViewport = true;
            this._viewportObserver = null;
            this._progressRef = null; // {bar, container, handler}
            this.SliderBtn    = null;
            this._isSwitching = false; // prevents button flickering during internal auto-switch

            this.addEvents({
                onImport: this.$onImport,
                onDestroy: this.$onDestroy
            });

            QUI.addEvent('resize', this.$resize);
        },

        /**
         * Called when the control is imported into the DOM.
         * Resolves DOM references, wires up events, animations and autoplay.
         */
        $onImport: function () {
            var Elm  = this.getElm(),
                self = this;

            if (Elm.getAttribute('data-qui-options-animation')) {
                this.setAttribute('animation', Elm.getAttribute('data-qui-options-animation'));
            }

            this.navTab              = Elm.getElement('.quiqqer-tab-nav');
            this.navTabsItems        = Elm.getElements('.quiqqer-tab-nav-item');
            this.navContents         = Elm.getElements('.quiqqer-tab-content-item');
            this.NavContentContainer = Elm.getElement('.quiqqer-tab-content');

            if (!this.navTabsItems || !this.navContents) {
                return;
            }

            this.enableDragToScroll = parseInt(this.getAttribute('enabledragtoscroll'));

            if (this.enableDragToScroll === 1) {
                this.$initDragToScroll();
            }

            // animation effect
            if (this.getAttribute('animation')) {
                switch (this.getAttribute('animation')) {
                    case 'fadeOutFadeIn':
                        this.$animFuncOut = this.$fadeOut;
                        this.$animFuncIn = this.$fadeIn;
                        break;

                    case 'scaleToSmallScaleFromLarge':
                        this.$animFuncOut = this.$scaleOutToSmall;
                        this.$animFuncIn = this.$scaleInFromLarge;
                        break;

                    case 'scaleToSmallScaleFromSmall':
                        this.$animFuncOut = this.$scaleOutToSmall;
                        this.$animFuncIn = this.$scaleInFromSmall;
                        break;

                    case 'scaleToLargeScaleFromLarge':
                        this.$animFuncOut = this.$scaleOutToLarge;
                        this.$animFuncIn = this.$scaleInFromLarge;
                        break;

                    case 'scaleToLargeScaleFromSmall':
                        this.$animFuncOut = this.$scaleOutToLarge;
                        this.$animFuncIn = this.$scaleInFromSmall;
                        break;

                    case 'slideOutToRightSlideInFromLeft':
                        this.$animFuncOut = this.$slideOutToRight;
                        this.$animFuncIn = this.$slideInFromLeft;
                        break;

                    case 'slideOutToRightSlideInFromRight':
                        this.$animFuncOut = this.$slideOutToRight;
                        this.$animFuncIn = this.$slideInFromRight;
                        break;

                    case 'slideOutToBottomSlideInFromBottom':
                        this.$animFuncOut = this.$slideOutToBottom;
                        this.$animFuncIn = this.$slideInFromBottom;
                        break;

                    case 'slideOutToBottomSlideInFromTop':
                        this.$animFuncOut = this.$slideOutToBottom;
                        this.$animFuncIn = this.$slideInFromTop;
                        break;

                    case 'slideOutToLeftSlideInFromRight':
                        this.$animFuncOut = this.$slideOutToLeft;
                        this.$animFuncIn = this.$slideInFromRight;
                        break;

                    case 'slideOutToLeftSlideInFromLeft':
                        this.$animFuncOut = this.$slideOutToLeft;
                        this.$animFuncIn = this.$slideInFromLeft;
                        break;

                    default:
                        this.$animFuncOut = this.$scaleOutToLarge;
                        this.$animFuncIn = this.$scaleInFromSmall;
                        break;
                }
            }

            this.ActiveNavTab  = Elm.getElement('.quiqqer-tab-nav-item.active');
            this.ActiveContent = Elm.getElement('.quiqqer-tab-content-item.active');

            if (this.ActiveContent && this.$wasOpenedByUrl(this.ActiveContent)) {
                this.$prepareContentMedia(this.ActiveContent);
            }

            let clickEvent = function (event) {
                event.stop();
                // do not trigger a tab change while currently dragging
                if (self.isDragging) {
                    return;
                }
                if (self.clicked) {
                    return;
                }

                self.clicked = true;

                let NavTabItem = event.target;

                if (NavTabItem.nodeName !== 'LI') {
                    NavTabItem = NavTabItem.getParent('li');
                }

                let targetHref = NavTabItem.getElement('a').getAttribute('href');
                let target = targetHref ? targetHref.replace(/^#/, '') : '';

                if (!target) {
                    self.clicked = false;
                    return;
                }

                self.toggle(NavTabItem, target);

                self.$updateUrl(target, 'push');
            };

            this.navTabsItems.addEvent('click', clickEvent);
            this.$resize();

            // initialize autoplay / progress
            const autoplayAttr = parseInt(this.getAttribute('autoplay'));
            if (!isNaN(autoplayAttr)) {
                this.options.autoplay = autoplayAttr === 1;
            }

            const intervalAttr = parseInt(this.getAttribute('autoplayinterval'));
            if (!isNaN(intervalAttr)) {
                this.options.autoplayinterval = intervalAttr;
            }

            const pauseOnHoverAttr = this.getAttribute('pauseonhover');
            if (pauseOnHoverAttr !== null && pauseOnHoverAttr !== false) {
                this.options.pauseonhover = ['1', 1, true, 'true'].indexOf(pauseOnHoverAttr) !== -1;
            }

            const showProgressAttr = this.getAttribute('showprogress');
            if (showProgressAttr !== null) {
                this.options.showprogress = String(showProgressAttr) !== '0' && String(showProgressAttr) !== 'false';
            }

            this.$initViewportObserver();

            this.progresElms = Elm.querySelectorAll('.quiqqer-tabsAdvanced-progress');

            // if progress should be hidden, hide the containers visually
            if (!this.options.showprogress) {
                this.progresElms.forEach(function (P) {
                    P.style.display = 'none';
                });
            }

            if (this.$shouldUseHoverPause()) {
                Elm.addEventListener('mouseenter', this.$onMouseEnter);
                Elm.addEventListener('mouseleave', this.$onMouseLeave);
            }

            // start autoplay if enabled
            if (this.options.autoplay && this.ActiveNavTab) {
                this._autoPausedByHover = this.$isControlHovered();

                if (this.$canRunAutoplay() && !this._autoPausedByHover) {
                    this.$startProgress(this.ActiveNavTab);
                } else {
                    this.isPaused = true;
                    this._autoPausedByVisibility = document.hidden;
                    this._autoPausedByViewport = !this._isInViewport;
                }
            }

            // initialize slider control button
            this.SliderBtn = Elm.querySelector('[data-name="btnToggle"]');
            if (this.SliderBtn) {
                this.$updateSliderButton();

                this.SliderBtn.addEvent('click', function (e) {
                    e.stop();

                    // if autoplay was disabled before, enable and start it via click
                    if (!self.options.autoplay) {
                        self.options.autoplay = true;
                        self._autoPausedByVisibility = document.hidden;
                        self._autoPausedByViewport = !self._isInViewport;
                        self._autoPausedByHover = self.$isControlHovered();
                        self.isPaused = !self.$canRunAutoplay() || self._autoPausedByHover;
                        self.$updateSliderButton();
                        if (self.ActiveNavTab) {
                            if (self.$canRunAutoplay() && !self._autoPausedByHover) {
                                self.$startProgress(self.ActiveNavTab);
                            }
                        }
                        return;
                    }

                    // toggle pause / resume
                    if (self.isPaused) {
                        self.resumeAutoplay();
                    } else {
                        self.pauseAutoplay();
                    }
                });
            }

            // Autoplay uses a CSS animation + JS timeout as a fallback. When the browser tab is in the background,
            // animations/timers can get throttled and the content-switch animation can hang. If that happens while
            // autoplay keeps advancing, multiple panels can end up visible. To avoid this, we auto-pause autoplay
            // when the document is hidden and only auto-resume if we paused it ourselves (not if the user paused).
            document.addEventListener('visibilitychange', this.$onVisibilityChange);
        },

        $onVisibilityChange: function () {
            if (!this.options.autoplay) {
                return;
            }

            if (document.hidden) {
                if (!this.isPaused) {
                    this._autoPausedByVisibility = true;
                    this.pauseAutoplay();
                }
                return;
            }

            if (this._autoPausedByVisibility) {
                this._autoPausedByVisibility = false;
                if (!this._autoPausedByViewport && !this._autoPausedByHover) {
                    this.resumeAutoplay();
                }
            }
        },

        /**
         * Clean up global listeners and observers when the control is destroyed.
         */
        $onDestroy: function () {
            const Elm = this.getElm();

            document.removeEventListener('visibilitychange', this.$onVisibilityChange);

            if (Elm) {
                Elm.removeEventListener('mouseenter', this.$onMouseEnter);
                Elm.removeEventListener('mouseleave', this.$onMouseLeave);
            }

            if (this._viewportObserver) {
                this._viewportObserver.disconnect();
                this._viewportObserver = null;
            }
        },

        /**
         * Initialize viewport tracking for autoplay.
         * Autoplay should only continue while the tab control is visible.
         */
        $initViewportObserver: function () {
            const Elm = this.getElm();

            if (!Elm) {
                return;
            }

            this._isInViewport = this.$isInViewport(Elm);

            if (typeof IntersectionObserver === 'undefined') {
                return;
            }

            if (this._viewportObserver) {
                this._viewportObserver.disconnect();
            }

            this._viewportObserver = new IntersectionObserver(this.$onViewportIntersection, {
                threshold: 0.15
            });

            this._viewportObserver.observe(Elm);
        },

        /**
         * Pause autoplay while the control is outside the viewport and resume
         * it with the remaining duration once it becomes visible again.
         *
         * @param {IntersectionObserverEntry[]} entries
         */
        $onViewportIntersection: function (entries) {
            if (!entries || !entries.length) {
                return;
            }

            const Entry = entries[0];

            this._isInViewport = !!(
                Entry.isIntersecting &&
                Entry.intersectionRatio > 0
            );

            if (!this.options.autoplay) {
                return;
            }

            if (!this._isInViewport) {
                if (!this.isPaused && !document.hidden) {
                    this._autoPausedByViewport = true;
                    this.pauseAutoplay();
                }
                return;
            }

            if (this._autoPausedByViewport) {
                this._autoPausedByViewport = false;

                if (!document.hidden && !this._autoPausedByVisibility && !this._autoPausedByHover) {
                    this.resumeAutoplay();
                }
            }
        },

        /**
         * Pause autoplay while the user hovers the complete control.
         */
        $onMouseEnter: function () {
            if (!this.$shouldUseHoverPause()) {
                return;
            }

            if (!this.isPaused && this.$canRunAutoplay()) {
                this._autoPausedByHover = true;
                this.pauseAutoplay();
            }
        },

        /**
         * Resume autoplay after leaving the control if the pause was
         * triggered by hover and no other auto-pause reason is active.
         */
        $onMouseLeave: function () {
            if (!this._autoPausedByHover) {
                return;
            }

            this._autoPausedByHover = false;

            if (
                this.options.autoplay &&
                !this._autoPausedByVisibility &&
                !this._autoPausedByViewport &&
                this.$canRunAutoplay()
            ) {
                this.resumeAutoplay();
            }
        },

        /**
         * Check whether hover-based autoplay pause should be active.
         *
         * @return {boolean}
         */
        $shouldUseHoverPause: function () {
            if (!this.options.pauseonhover) {
                return false;
            }

            if (!window.matchMedia) {
                return true;
            }

            return window.matchMedia('(hover: hover) and (pointer: fine)').matches;
        },

        /**
         * Check whether the pointer currently hovers the control.
         *
         * @return {boolean}
         */
        $isControlHovered: function () {
            if (!this.$shouldUseHoverPause()) {
                return false;
            }

            const Elm = this.getElm();

            return !!(Elm && Elm.matches && Elm.matches(':hover'));
        },

        /**
         * Check whether autoplay is currently allowed to run.
         *
         * @return {boolean}
         */
        $canRunAutoplay: function () {
            return !document.hidden && this._isInViewport;
        },

        /**
         * Resize handler used to enable/disable drag-to-scroll behavior
         * based on the current width of the nav container.
         */
        $resize: function () {
            if (this.enableDragToScroll !== 1) {
                return;
            }

            if (this.navTab.scrollWidth > this.navTab.clientWidth) {
                this.navTab.addEventListener('mousedown', this.$mouseDownHandler);
            } else {
                this.navTab.removeEventListener('mousedown', this.$mouseDownHandler);
            }
        },

        /**
         * Toggle from the current active tab to the given nav item / target id.
         * Handles animations, ARIA updates and autoplay progress.
         *
         * @param {HTMLElement} NavItem - The navigation list item representing the target tab.
         * @param {string} target - The id of the target content element.
         */
        toggle: function (NavItem, target) {
            if (NavItem.classList.contains('active')) {
                this.clicked = false;
                return;
            }

            var TabContent = this.getElm().getElement('[id="' + target + '"]');

            if (!TabContent) {
                this.clicked = false;
                return;
            }

            var self = this;

            // prevent race conditions: stop existing progress immediately
            // (e.g. when user clicks manually while the old bar is about to finish)
            if (this.options.autoplay) {
                this._isSwitching = true; // suppress button update during internal switch
                this.$stopProgress();
            }

            this.NavContentContainer.setStyle('height', this.NavContentContainer.offsetHeight);

            // stop existing progress (without button update)
            this.$stopProgress();

            // re-initialize progress (fresh on every switch)
            // if paused, animation starts in paused state and stays stopped
            if (self.options.autoplay) {
                self.$startProgress(NavItem);
            } else {
                self.$stopProgress();
            }

            this.hideContent(this.ActiveContent).then(function () {
                self.disableNavItem(self.ActiveNavTab);
                self.$setNavItemPos(NavItem);
                TabContent.setStyle('display', null);
                self.$prepareContentMedia(TabContent);

                return Promise.all([
                    self.enableNavItem(NavItem),
                    self.showContent(TabContent),
                    self.$setHeightForContent(TabContent)
                ]);
            }).then(function () {
                self.clicked = false;
                self.NavContentContainer.setStyle('height', null);

                self._isSwitching = false; // internal switch finished

                // Ensure a consistent final state: only the newly active content stays visible.
                self.$normalizePanels();

                // update ARIA status
                try {
                    // tabs: aria-selected / tabindex
                    const oldTab = self.ActiveNavTab ? self.ActiveNavTab.getElement('[role="tab"]') : null;
                    const newTab = NavItem ? NavItem.getElement('[role="tab"]') : null;
                    if (oldTab) {
                        oldTab.setAttribute('aria-selected', 'false');
                        oldTab.setAttribute('tabindex', '-1');
                    }
                    if (newTab) {
                        newTab.setAttribute('aria-selected', 'true');
                        newTab.setAttribute('tabindex', '0');
                    }

                    // panels: aria-hidden
                    const oldPanel = self.ActiveContent;
                    const newPanel = TabContent;
                    if (oldPanel) {
                        oldPanel.setAttribute('aria-hidden', 'true');
                    }
                    if (newPanel) {
                        newPanel.setAttribute('aria-hidden', 'false');
                    }

                    // live region message
                    const Live = self.getElm().getElement('#tabs-live');
                    if (Live) {
                        const items = self.navTabsItems;
                        let index = -1;
                        for (let i = 0; i < items.length; i++) {
                            if (items[i] === NavItem) { index = i; break; }
                        }
                        const total = items ? items.length : 0;
                        const label = NavItem ? NavItem.getElement('.quiqqer-tabsAdvanced-nav-linkLabel') : null;
                        const text  = 'Slide ' + (index + 1) + ' von ' + total + (label ? ': ' + label.get('text') : '');
                        Live.set('text', text);
                    }
                } catch (e) {
                    // defensive: aria is optional
                }
            });
        },

        /**
         * Set nav item to inactive
         *
         * @param Item HTMLNode
         * @return Promise
         */
        disableNavItem: function (Item) {
            return new Promise(function (resolve) {
                Item.removeClass('active');

                resolve();
            });
        },

        /**
         * Set nav item to active
         *
         * @param Item HTMLNode
         * @return Promise
         */
        enableNavItem: function (Item) {
            var self = this;

            return new Promise(function (resolve) {
                Item.addClass('active');
                self.ActiveNavTab = Item;

                resolve();
            });
        },

        /**
         * Hide tab content
         *
         * @param Item HTMLNode
         * @return Promise
         */
        hideContent: function (Item) {
            const self = this;

            return new Promise(function (resolve) {
                // Fail-safe:
                // - if the animation function is missing, returns non-Promise, throws, or never resolves
                //   we still MUST continue and force the final DOM state (display:none).
                // Otherwise a single stuck animation can leave multiple panels visible.
                self.$withTimeout(self.$safeAnim(self.$animFuncOut, Item), 1200)
                    .catch(function () {
                    })
                    .then(function () {
                        Item.removeClass('active');
                        Item.setStyle('display', 'none');
                        resolve();
                    });
            });
        },

        /**
         * Show tab content
         *
         * @param Item HTMLNode
         * @return Promise
         */
        showContent: function (Item) {
            var self = this;

            return new Promise(function (resolve) {
                // Same fail-safe as hideContent(): even if the animation hangs, we still activate exactly one panel.
                self.$withTimeout(self.$safeAnim(self.$animFuncIn, Item), 1200)
                    .catch(function () {
                    })
                    .then(function () {
                        Item.style.display = null;
                        Item.style.opacity = null;
                        Item.addClass('active');
                        self.ActiveContent = Item;
                        resolve();
                    });
            });
        },

        /**
         * Ensure a consistent final state: only the newly active content stays visible.
         */
        $normalizePanels: function () {
            if (!this.navContents || !this.navContents.length) {
                return;
            }

            const active = this.ActiveContent;

            this.navContents.forEach(function (Content) {
                if (active && Content === active) {
                    Content.style.display = null;
                    Content.addClass('active');
                } else {
                    Content.removeClass('active');
                    Content.setStyle('display', 'none');
                }
            });
        },

        /**
         * Set height of tab content container
         *
         * @param height integer
         * @return Promise
         */
        $setHeight: function (height) {
            return this.$animate(this.NavContentContainer, {
                height: height
            });
        },

        /**
         * Wait until the browser has applied the latest layout changes.
         *
         * @return {Promise<void>}
         */
        $waitForLayout: function () {
            return new Promise(function (resolve) {
                requestAnimationFrame(function () {
                    requestAnimationFrame(resolve);
                });
            });
        },

        /**
         * Measure the content height after the element became visible.
         *
         * @param {HTMLElement} Item
         * @return {Promise<void>}
         */
        $setHeightForContent: function (Item) {
            const self = this;

            return this.$waitForLayout().then(function () {
                return self.$setHeight(self.$getContentHeight(Item));
            });
        },

        /**
         * Return the best available content height.
         *
         * @param {HTMLElement} Item
         * @return {number}
         */
        $getContentHeight: function (Item) {
            if (!Item) {
                return 0;
            }

            return Math.ceil(Math.max(
                Item.offsetHeight || 0,
                Item.scrollHeight || 0
            ));
        },

        /**
         * Ensure active tab images start loading immediately and update the
         * container height once the browser has final media dimensions.
         *
         * @param {HTMLElement} Item
         */
        $prepareContentMedia: function (Item) {
            if (!Item) {
                return;
            }

            const self = this;
            const Images = Item.querySelectorAll('img');

            if (!Images.length) {
                return;
            }

            Images.forEach(function (Image) {
                if (Image.loading === 'lazy') {
                    Image.loading = 'eager';
                }

                const syncHeight = function () {
                    self.$syncActiveContentHeight(Item);
                };

                if (!Image.dataset.quiTabsHeightSyncBound) {
                    Image.addEventListener('load', syncHeight);
                    Image.addEventListener('error', syncHeight);
                    Image.addEventListener('lazyloaded', syncHeight);
                    Image.dataset.quiTabsHeightSyncBound = '1';
                }

                if (
                    window.lazySizes &&
                    window.lazySizes.loader &&
                    typeof window.lazySizes.loader.unveil === 'function' &&
                    Image.classList.contains('lazyload')
                ) {
                    window.lazySizes.loader.unveil(Image);
                }
            });
        },

        /**
         * Only preload the initially active tab if it was explicitly opened
         * via the URL query parameter.
         *
         * @param {HTMLElement} Item
         * @return {boolean}
         */
        $wasOpenedByUrl: function (Item) {
            if (!Item || !Item.id) {
                return false;
            }

            try {
                const url = new URL(window.location.href);
                return url.searchParams.get('open') === Item.id;
            } catch (e) {
                return false;
            }
        },

        /**
         * Keep the container height in sync while the newly activated content
         * is still resolving its final image size.
         *
         * @param {HTMLElement} Item
         */
        $syncActiveContentHeight: function (Item) {
            if (!Item || this.ActiveContent !== Item) {
                return;
            }

            const height = this.$getContentHeight(Item);

            if (!height) {
                return;
            }

            this.NavContentContainer.setStyle('height', height);

            requestAnimationFrame(() => {
                if (this.ActiveContent === Item) {
                    this.NavContentContainer.setStyle('height', null);
                }
            });
        },

        // region animation

        /**
         * Animation - slide out to left
         *
         * @param Item HTMLNode
         * @return Promise
         */
        $slideOutToLeft: function (Item) {
            return this.$animate(Item, {
                opacity   : 0,
                translateX: -5,
            });
        },

        /**
         * Animation - slide out to bottom
         *
         * @param Item HTMLNode
         * @return Promise
         */
        $slideOutToBottom: function (Item) {
            return this.$animate(Item, {
                opacity   : 0,
                translateY: 5,
            });
        },

        /**
         * Animation - slide out to right
         *
         * @param Item HTMLNode
         * @return Promise
         */
        $slideOutToRight: function (Item) {
            return this.$animate(Item, {
                opacity   : 0,
                translateX: 5,
            });
        },

        /**
         * Animation - slide in from left to right
         *
         * @param Item HTMLNode
         * @return Promise
         */
        $slideInFromRight: function (Item) {
            Item.setStyles({
                transform: 'translateX(5px)',
                opacity  : 0
            });

            return this.$animate(Item, {
                translateX: 0,
                opacity   : 1
            });
        },

        /**
         * Animation - slide in from right to left
         *
         * @param Item HTMLNode
         * @return Promise
         */
        $slideInFromLeft: function (Item) {
            Item.setStyles({
                transform: 'translateX(-5px)',
                opacity  : 0
            });

            return this.$animate(Item, {
                translateX: 0,
                opacity   : 1
            });
        },

        /**
         * Animation - slide in from top to bottom
         *
         * @param Item HTMLNode
         * @return Promise
         */
        $slideInFromTop: function (Item) {
            Item.setStyles({
                transform: 'translateY(-5px)',
                opacity  : 0
            });

            return this.$animate(Item, {
                translateY: 0,
                opacity   : 1
            });
        },

        /**
         * Animation - slide in from bottom to top
         *
         * @param Item HTMLNode
         * @return Promise
         */
        $slideInFromBottom: function (Item) {
            Item.setStyles({
                transform: 'translateY(5px)',
                opacity  : 0
            });

            return this.$animate(Item, {
                translateY: 0,
                opacity   : 1
            });
        },

        /**
         * Animation - fade out
         *
         * @param Item HTMLNode
         * @return Promise
         */
        $fadeOut: function (Item) {
            return this.$animate(Item, {
                opacity : 0
            });
        },

        /**
         * Animation - fade in
         *
         * @param Item HTMLNode
         * @return Promise
         */
        $fadeIn: function (Item) {
            Item.setStyles({
                opacity : 0
            });

            return this.$animate(Item, {
                opacity : 1
            });
        },

        /**
         * Animation - hide by scale out
         *
         * @param Item HTMLNode
         * @return Promise
         */
        $scaleOutToSmall: function (Item) {
            return this.$animate(Item, {
                scale: 0.95,
                opacity: 0
            });
        },

        /**
         * Animation - hide by scale in
         *
         * @param Item HTMLNode
         * @return Promise
         */
        $scaleOutToLarge: function (Item) {
            // console.log(Item)
            // return new Promise((resolve) => {
            //     moofx(Item).animate({
            //         opacity : 0,
            //         'transform': 'scale(1.05)'
            //     }, {
            //         callback: resolve
            //     });
            // });

            return this.$animate(Item, {
                // scale: 1.05,
                'transform': 'scale(1.05)',
                opacity: 0
            });
        },

        /**
         * Animation - show by scale out
         *
         * @param Item HTMLNode
         * @return Promise
         */
        $scaleInFromLarge: function (Item) {
            Item.setStyles({
                scale: 1.05,
                'transform': 'scale(1.05)',
                opacity : 0
            });

            return this.$animate(Item, {
                opacity : 1,
                scale: 1,
            });
        },

        /**
         * Animation - show by scale in
         *
         * @param Item HTMLNode
         * @return Promise
         */
        $scaleInFromSmall: function (Item) {
            Item.setStyles({
                // scale: 0.95,
                'transform': 'scale(0.95)',
                opacity : 0
            });

            // return new Promise((resolve) => {
            //     moofx(Item).animate({
            //         opacity : 1,
            //         'transform': 'scale(1)',
            //     }, {
            //         callback: resolve
            //     });
            // });

            return this.$animate(Item, {
                opacity : 1,
                scale: 1,
            });
        },

        // endregion

        /**
         * Scroll active nav item to the left edge
         *
         * @param Item
         */
        $setNavItemPos: function (Item) {
            if (!Item) {
                return;
            }

            // visibility check within the nav container
            const visibleLeft  = this.navTab.scrollLeft;
            const visibleRight = visibleLeft + this.navTab.clientWidth;
            const itemLeft     = Item.offsetLeft;
            const itemRight    = itemLeft + Item.offsetWidth;

            if (itemLeft >= visibleLeft && itemRight <= visibleRight) {
                // already fully visible → no scrolling needed
                return;
            }

            // determine target position: cut off on left or right
            let targetLeft;
            if (itemLeft < visibleLeft) {
                targetLeft = itemLeft;
            } else {
                targetLeft = itemRight - this.navTab.clientWidth;
            }

            // duration from attribute or fallback option
            const duration = parseInt(this.getAttribute('scrollduration'), 10) || this.options.scrollduration;
            this.$smoothScrollTo(this.navTab, targetLeft, duration);
        },

        /**
         * Smoothly scroll a container horizontally to a target position
         * @param {HTMLElement} Container
         * @param {number} targetLeft
         * @param {number} duration in ms
         */
        $smoothScrollTo: function (Container, targetLeft, duration) {
            if (!Container) {
                return;
            }

            const maxLeft = Math.max(0, Container.scrollWidth - Container.clientWidth);
            const start   = Container.scrollLeft;
            const end     = Math.min(Math.max(targetLeft, 0), maxLeft);
            const change  = end - start;

            if (change === 0 || duration <= 0) {
                Container.scrollLeft = end;
                return;
            }

            const startTime = performance.now();

            const easeInOutQuad = function (t) {
                return t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t;
            };

            const step = (now) => {
                const elapsed = now - startTime;
                const t = Math.min(1, elapsed / duration);
                const eased = easeInOutQuad(t);
                Container.scrollLeft = start + change * eased;

                if (t < 1) {
                    requestAnimationFrame(step);
                }
            };

            requestAnimationFrame(step);
        },

        /**
         * Check whether the given element is at least partially inside
         * the current viewport.
         *
         * @param {HTMLElement} element - Element to check.
         * @return {boolean} True if the element intersects the viewport.
         */
        $isInViewport: function (element) {
            const rect = element.getBoundingClientRect();
            return (
                rect.bottom > 0 &&
                rect.right > 0 &&
                rect.top < (window.innerHeight || document.documentElement.clientHeight) &&
                rect.left < (window.innerWidth || document.documentElement.clientWidth)
            );
        },

        /**
         * Helper around anime.js that returns a Promise which resolves
         * when the animation has finished.
         *
         * @param {HTMLElement} Target - Element to animate.
         * @param {Object} options - Anime.js animation options.
         * @return {Promise<void>} Resolves once the animation completes.
         */
        $animate: function (Target, options) {
            return new Promise(function (resolve) {
                options          = options || {};
                options.targets  = Target;
                options.complete = resolve;
                options.duration = options.duration || 250;
                options.easing   = options.easing || 'easeInQuad';

                animejs(options);
            });
        },

        /**
         * Normalize the result of an animation function to a real Promise.
         * Covers:
         * - fn missing -> resolve immediately
         * - fn returns non-Promise -> Promise.resolve(...) makes it awaitable
         * - fn throws sync -> convert to rejected Promise
         *
         * @param fn
         * @param Item
         * @returns {Promise<never>|Promise<Awaited<*>>|Promise<void>}
         */
        $safeAnim: function (fn, Item) {
            try {
                if (typeof fn !== 'function') {
                    return Promise.resolve();
                }
                return Promise.resolve(fn.call(this, Item));
            } catch (e) {
                return Promise.reject(e);
            }
        },

        /**
         * Never block the tab switching forever: race the animation against a timeout.
         * Timeout resolves (not rejects) on purpose: the caller will enforce the final DOM state anyway.
         *
         * @param promiseLike
         * @param ms
         * @returns {Promise<Awaited<unknown>>}
         */
        $withTimeout: function (promiseLike, ms) {
            return Promise.race([
                Promise.resolve(promiseLike),
                new Promise(function (resolve) {
                    setTimeout(resolve, ms);
                })
            ]);
        },

        // region drag to scroll

        /**
         * Initialize drag-to-scroll behavior on the tab navigation container.
         * Adds the initial mousedown listener if scrolling is actually needed.
         */
        $initDragToScroll: function () {
            if (this.navTab.scrollWidth <= this.navTab.clientWidth) {
                return;
            }

            this.navTab.addEventListener('mousedown', this.$mouseDownHandler);
        },

        /**
         * Mouse move handler used while dragging the navigation bar.
         * Updates scroll position based on mouse delta.
         *
         * @param {MouseEvent} e - Mouse move event.
         */
        $mouseMoveHandler: function (e) {
            // how far the mouse has been moved
            const dx = e.clientX - this.navPos.x;
            const moved = Math.abs(e.clientX - this.dragStartX);
            if (moved > (parseInt(this.getAttribute('dragThreshold'), 10) || this.options.dragThreshold)) {
                this.isDragging = true;
            }

            // Scroll the element
            this.navTab.scrollLeft = this.navPos.left - dx;
        },

        /**
         * Mouse down handler starting a potential drag gesture
         * on the navigation container.
         *
         * @param {MouseEvent} e - Mouse down event.
         */
        $mouseDownHandler: function (e) {
            this.navTab.style.userSelect = 'none';

            this.navPos = {
                left: this.navTab.scrollLeft, // the current scroll
                x   : e.clientX, // get the current mouse position
            };

            // record drag start
            this.isDragging = false;
            this.dragStartX = e.clientX;

            document.addEventListener('mousemove', this.$mouseMoveHandler);
            document.addEventListener('mouseup', this.$mouseUpHandler);
        },

        /**
         * Mouse up handler finishing a drag gesture.
         * Cleans up document-level listeners and resets flags.
         */
        $mouseUpHandler: function () {
            document.removeEventListener('mousemove', this.$mouseMoveHandler);
            document.removeEventListener('mouseup', this.$mouseUpHandler);

            this.navTab.style.removeProperty('user-select');

            setTimeout(() => {
                this.isDragging = false;
                this.clicked = false;
            }, 50);
        },

        // endregion

        /**
         * Start the progress animation for the given nav item.
         * Also wires up callbacks to advance to the next tab on completion.
         *
         * @param {HTMLElement} NavItem - Navigation item whose progress should run.
         */
        $startProgress: function (NavItem) {
            if (!this.options.showprogress) {
                return;
            }

            this.$stopProgress();

            const Progress = NavItem.getElement('.quiqqer-tabsAdvanced-progress');
            const Bar      = Progress ? Progress.getElement('.quiqqer-tabsAdvanced-progress__bar') : null;

            if (!Progress || !Bar) {
                return;
            }

            // set duration
            const dur = this.options.autoplayinterval;
            Progress.style.setProperty('--progress-duration', dur + 'ms');
            Progress.style.setProperty('--progress-state', this.isPaused ? 'paused' : 'running');

            // hard reset animation and restart (ensures starting at 0%)
            Progress.removeClass('quiqqer-tabsAdvanced-progress--active');
            Bar.style.width = '0%';
            // force reflow so that the browser registers the reset
            void Bar.offsetWidth; void Progress.offsetWidth;
            Progress.addClass('quiqqer-tabsAdvanced-progress--active');
            // event handling for animation end + fallback timeout
            const self = this;
            const onEnd = function () {
                // Only act if this handler still belongs to the currently running progress instance.
                // Prevents a race where the previous bar's onEnd runs *after* a tab switch and
                // clears the timeout of the newly started progressbar.
                if (!self._progressRef || self._progressRef.handler !== onEnd || self._progressRef.bar !== Bar) {
                    return;
                }

                if (self._progressRef.timeout) {
                    clearTimeout(self._progressRef.timeout);
                    self._progressRef.timeout = null;
                }

                if (!self.isPaused && self.options.autoplay) {
                    self.$goToNextTab(NavItem);
                }
            };

            const startTs = performance.now();
            const duration = dur;
            const timeout = setTimeout(function () {
                if (!self.isPaused && self.options.autoplay) {
                    onEnd();
                }
            }, dur);

            this._progressRef = {
                bar      : Bar,
                container: Progress,
                handler  : onEnd,
                timeout  : timeout,
                startedAt: startTs,
                duration : duration,
                remainingMs: duration
            };

            this.$updateSliderButton();
        },

        /**
         * Stop any running progress animation and reset all indicators.
         */
        $stopProgress: function () {
            if (this._progressRef && this._progressRef.bar) {
                // console.log("$stopProgress() --> remove handeler for: ", this._progressRef.bar)
                this._progressRef.bar.removeEvent('animationend', this._progressRef.handler);
                this._progressRef.bar.removeEvent('webkitAnimationEnd', this._progressRef.handler);
            }

            if (this._progressRef && this._progressRef.timeout) {
                clearTimeout(this._progressRef.timeout);
            }

            // reset all progress indicators
            this.getElm().getElements('.quiqqer-tabsAdvanced-progress').forEach(function (P) {
                P.removeClass('quiqqer-tabsAdvanced-progress--active');
                P.style.removeProperty('--progress-duration');
                P.style.removeProperty('--progress-state');
                var Bar = P.getElement('.quiqqer-tabsAdvanced-progress__bar');
                if (Bar) {
                    Bar.style.width = '0%';
                }
            });
            this._progressRef = null;
            if (!this._isSwitching) {
                this.$updateSliderButton();
            }
        },

        /**
         * Pause the autoplay logic and freeze the current progress state.
         * Computes remaining time so that it can be resumed later.
         */
        pauseAutoplay: function () {
            this.isPaused = true;
            if (this._progressRef && this._progressRef.container) {
                this._progressRef.container.style.setProperty('--progress-state', 'paused');
            }
            // Fallback-Timeout stoppen, damit er nicht während Pause abläuft
            if (this._progressRef && this._progressRef.timeout) {
                clearTimeout(this._progressRef.timeout);
                this._progressRef.timeout = null;
            }
            // Verbleibende Zeit anhand der aktuellen Breite bestimmen
            if (this._progressRef && this._progressRef.container && this._progressRef.bar) {
                const total = this._progressRef.container.clientWidth || 0;
                const current = this._progressRef.bar.offsetWidth || 0;
                let frac = total > 0 ? (current / total) : 0;
                if (frac < 0) { frac = 0; }
                if (frac > 1) { frac = 1; }
                const remaining = Math.max(0, this._progressRef.duration * (1 - frac));
                this._progressRef.remainingMs = remaining;
            }
            this.$updateSliderButton();
        },

        /**
         * Resume autoplay from a previously paused state.
         * Uses stored remaining duration or recalculates it from DOM width.
         */
        resumeAutoplay: function () {
            if (!this.$canRunAutoplay()) {
                this._autoPausedByVisibility = document.hidden;
                this._autoPausedByViewport = !this._isInViewport;
                this.isPaused = true;
                this.$updateSliderButton();
                return;
            }

            this.isPaused = false;
            // if a progress exists, just continue and set a new timeout
            if (this._progressRef && this._progressRef.container) {
                this._progressRef.container.style.setProperty('--progress-state', 'running');

                // use remaining time, or recalculate if not present
                let rest = this._progressRef.remainingMs;
                if (rest == null) {
                    const total = this._progressRef.container.clientWidth || 0;
                    const current = this._progressRef.bar.offsetWidth || 0;
                    let frac = total > 0 ? (current / total) : 0;
                    if (frac < 0) { frac = 0; }
                    if (frac > 1) { frac = 1; }
                    rest = Math.max(0, this._progressRef.duration * (1 - frac));
                    this._progressRef.remainingMs = rest;
                }

                const self = this;
                if (this._progressRef.timeout) {
                    clearTimeout(this._progressRef.timeout);
                }
                this._progressRef.timeout = setTimeout(function () {
                    if (!self.isPaused && self.options.autoplay) {
                        self._progressRef.handler();
                    }
                }, rest + 60);
            } else if (this.ActiveNavTab) {
                // no progress present -> start fresh
                this.$startProgress(this.ActiveNavTab);
            }
            this.$updateSliderButton();
        },

        /**
         * Switch to the next tab in sequence, used by autoplay.
         *
         * @param {HTMLElement} CurrentNavItem - The currently active navigation item.
         */
        $goToNextTab: function (CurrentNavItem) {
            if (!this.navTabsItems || this.navTabsItems.length === 0) {
                return;
            }

            // index of current item
            const items = this.navTabsItems;
            let idx = -1;
            for (let i = 0; i < items.length; i++) {
                if (items[i] === CurrentNavItem) {
                    idx = i; break;
                }
            }

            if (idx === -1) {
                return;
            }

            const nextIdx  = (idx + 1) % items.length;
            const NextItem = items[nextIdx];

            // Toggle auf nächsten Tab
            const href = NextItem.getElement('a').getAttribute('href');
            const target = href ? href.replace(/^#/, '') : '';
            this.clicked = true; // block parallel clicks during auto toggle
            this.toggle(NextItem, target);

            this.$updateUrl(target, 'replace');
        },

        /**
         * updates the URL consistently: only ?open=<slug>
         * mode: 'push' (manual) or 'replace' (auto)
         */
        $updateUrl: function (slug, mode) {
            // Only update the URL on manual user interaction
            if (mode !== 'push') {
                return;
            }
            try {
                const urlObj = new URL(window.location.href);
                if (slug) {
                    urlObj.searchParams.set('open', slug);
                } else {
                    urlObj.searchParams.delete('open');
                }
                urlObj.hash = '';
                history.pushState(null, null, urlObj.toString());
            } catch (e) {}
        },

        /**
         * updates icon states on the slider button
         */
        $updateSliderButton: function () {
            if (!this.SliderBtn) {
                return;
            }

            if (!this.options.autoplay) {
                return;
            }

            const BtnText = this.SliderBtn.querySelector('[data-name="btnToggle-text"]');

            if (this.isPaused) {
                this.SliderBtn.removeClass('is-playing');
                this.SliderBtn.addClass('is-paused');
                this.SliderBtn.setAttribute('aria-pressed', 'false');
                this.SliderBtn.setAttribute(
                    'aria-label',
                    QUILocale.get(lg, 'frontend.control.tabs.slider.btn.label.play')
                );

                if (BtnText) {
                    BtnText.textContent = QUILocale.get(lg, 'frontend.control.tabs.slider.btn.label.play');
                }
            } else {
                // is progressbar active?
                if (this._progressRef) {
                    this.SliderBtn.removeClass('is-paused');
                    this.SliderBtn.addClass('is-playing');
                    this.SliderBtn.setAttribute('aria-pressed', 'true');
                    this.SliderBtn.setAttribute(
                        'aria-label',
                        QUILocale.get(lg, 'frontend.control.tabs.slider.btn.label.pause')
                    );

                    if (BtnText) {
                        BtnText.textContent = QUILocale.get(lg, 'frontend.control.tabs.slider.btn.label.pause');
                    }
                } else {
                    this.SliderBtn.removeClass('is-playing');
                    this.SliderBtn.addClass('is-paused');
                    this.SliderBtn.setAttribute('aria-pressed', 'false');
                    this.SliderBtn.setAttribute(
                        'aria-label',
                        QUILocale.get(lg, 'frontend.control.tabs.slider.btn.label.play')
                    );

                    if (BtnText) {
                        BtnText.textContent = QUILocale.get(lg, 'frontend.control.tabs.slider.btn.label.play');
                    }
                }
            }
        }
    });
});
