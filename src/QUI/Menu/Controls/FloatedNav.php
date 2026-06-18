<?php

/**
 * This file contains QUI\Menu\Controls\FloatedNav
 */

namespace QUI\Menu\Controls;

use QUI;
use QUI\Menu\Independent;

/**
 * Class WallpaperText
 *
 * @package quiqqer/menu
 */
class FloatedNav extends QUI\Control
{
    /**
     * constructor
     *
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        // default options
        $this->setAttributes([
            'class' => 'quiqqer-floatedNavControl',
            'menuId' => false,
            'posX' => 'right',
            // right, left
            'forceVerticalCenter' => true,
            // if true, container will be centered per JS. pure css way (top: 50%; transform: translateY: (-50%);) causes jumping effect on mobile, when url bar disappears
            'size' => 'medium',
            // small, medium, large
            'design' => 'iconBar',
            // iconBar, flat
            'animationType' => false,
            // false, showAll (show entire control), showOneByOne (show each entry one by one)
            'initAnimation' => false,
            'animationEasing' => 'easeOutExpo',
            // see easing names on https://easings.net/
//            'navInitOpen'     => 'always', // always, desktop, never
            'showToggleButton' => 'mobile',
            // always, mobile, hide,
            'showLangSwitch' => false,
        ]);

        parent::__construct($attributes);

        $this->setJavaScriptControl('package/quiqqer/menu/bin/Controls/FloatedNav');

        $this->addCSSFile(
            dirname(__FILE__) . '/FloatedNav.css'
        );
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Control::create()
     */
    public function getBody(): string
    {
        $Engine = QUI::getTemplateManager()->getEngine();
        $LangSwitch = null;

        try {
            $IndependentMenu = Independent\Handler::getMenu($this->getAttribute('menuId'));
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException($Exception);

            return '';
        }

        $showToggleButton = true;

        switch ($this->getAttribute('showToggleButton')) {
            case 'always':
                $this->setJavaScriptControlOption('showtogglebutton', 'always');
                $this->addCSSClass('quiqqer-floatedNavControl__toggleButton-always');
                break;

            case 'mobile':
                $this->setJavaScriptControlOption('showtogglebutton', 'mobile');
                $this->addCSSClass('quiqqer-floatedNavControl__toggleButton-mobile');
                break;

            case 'hide':
                $showToggleButton = false;
                break;
        }

        $size = match ($this->getAttribute('size')) {
            'small', 'medium', 'large' => 'quiqqer-floatedNavControl__size-' . $this->getAttribute('size'),
            default => 'quiqqer-floatedNavControl__size-medium',
        };

        $design = match ($this->getAttribute('design')) {
            'flat' => 'quiqqer-floatedNavControl__design-' . $this->getAttribute('design'),
            default => 'quiqqer-floatedNavControl__design-iconsBar',
        };

        switch ($this->getAttribute('posX')) {
            case 'left':
            case 'right':
                $posX = 'quiqqer-floatedNavControl__posX-' . $this->getAttribute('posX');
                $this->setJavaScriptControlOption('position', $this->getAttribute('posX'));
                break;

            default:
                $posX = 'quiqqer-floatedNavControl__posX-right';
                $this->setJavaScriptControlOption('position', 'right');

                break;
        }

        if ($this->getAttribute('showLangSwitch')) {
            $LangSwitch = new QUI\Bricks\Controls\LanguageSwitches\Flags([
                'showFlags' => 0,
                'class' => 'quiqqer-floatedNav-entry'
            ]);
        }

        $animation = '';

        switch ($this->getAttribute('animationType')) {
            case 'showAll':
            case 'showOneByOne':
                $this->setJavaScriptControlOption('animationtype', $this->getAttribute('animationType'));
                $this->setJavaScriptControlOption('animationeasing', $this->getAnimationEasingName());
                $animation = 'quiqqer-floatedNav__animation-' . $this->getAttribute('animationType');
                break;
        }

        $initAnimation = 'quiqqer-floatedNavControl__noInitAnimation';

        if ($this->getAttribute('initAnimation')) {
            $this->setJavaScriptControlOption('initanimation', 1);
            $initAnimation = 'quiqqer-floatedNavControl__initAnimation';
        }

        if ($this->getAttribute('forceVerticalCenter')) {
            $this->setJavaScriptControlOption('forceverticalcenter', 1);
        }

        $this->addCSSClass($initAnimation);

        $this->addCSSClass($size);
        $this->addCSSClass($design);
        $this->addCSSClass($posX);

        $children = $IndependentMenu->getChildren();

        $Engine->assign([
            'this' => $this,
            'children' => $children,
            'animation' => $animation,
            'LangSwitch' => $LangSwitch,
            'showToggleButton' => $showToggleButton
        ]);

        return $Engine->fetch(dirname(__FILE__) . '/FloatedNav.html');
    }

    /**
     * Get correct easing name for animation
     * https://easings.net/
     *
     * @return false|mixed|string
     */
    public function getAnimationEasingName(): mixed
    {
        switch ($this->getAttribute('animationEasing')) {
            case 'easeInQuad':
            case 'easeInCubic':
            case 'easeInQuart':
            case 'easeInQuint':
            case 'easeInSine':
            case 'easeInExpo':
            case 'easeInCirc':
            case 'easeInBack':
            case 'easeOutQuad':
            case 'easeOutCubic':
            case 'easeOutQuart':
            case 'easeOutQuint':
            case 'easeOutSine':
            case 'easeOutExpo':
            case 'easeOutCirc':
            case 'easeOutBack':
            case 'easeInBounce':
            case 'easeInOutQuad':
            case 'easeInOutCubic':
            case 'easeInOutQuart':
            case 'easeInOutQuint':
            case 'easeInOutSine':
            case 'easeInOutExpo':
            case 'easeInOutCirc':
            case 'easeInOutBack':
            case 'easeInOutBounce':
            case 'easeOutBounce':
            case 'easeOutInQuad':
            case 'easeOutInCubic':
            case 'easeOutInQuart':
            case 'easeOutInQuint':
            case 'easeOutInSine':
            case 'easeOutInExpo':
            case 'easeOutInCirc':
            case 'easeOutInBack':
            case 'easeOutInBounce':
                return $this->getAttribute('animationEasing');

            default:
                return 'easeOutExpo';
        }
    }
}
