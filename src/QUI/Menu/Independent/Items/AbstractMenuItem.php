<?php

namespace QUI\Menu\Independent\Items;

use QUI;
use QUI\Locale;

use function array_filter;
use function array_values;
use function is_array;
use function is_string;
use function json_decode;
use function trim;

/**
 * Base class for menu items
 */
abstract class AbstractMenuItem
{
    /** @var array<string, mixed> */
    protected array $attributes = [];
    /** @var list<AbstractMenuItem> */
    protected array $children = [];

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    //region frontend methods


    /**
     * @return string
     */
    public function getType(): string
    {
        return is_string($this->attributes['type'] ?? null) ? $this->attributes['type'] : '';
    }

    /**
     * @param ?Locale $Locale
     * @return string
     */
    public function getTitle(null | Locale $Locale = null): string
    {
        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        $current = $Locale->getCurrent();
        $title = $this->attributes['title'];

        if (is_string($title)) {
            $title = json_decode($title, true);
        }

        if (is_array($title) && isset($title[$current]) && is_string($title[$current])) {
            return $title[$current];
        }

        return '';
    }

    /**
     * @param ?Locale $Locale
     * @return string
     */
    public function getShort(null | Locale $Locale = null): string
    {
        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        $current = $Locale->getCurrent();
        $data = $this->getCustomData();

        if (!is_array($data) || !isset($data['short'])) {
            return '';
        }

        $short = $data['short'];

        if (is_string($short)) {
            $short = json_decode($short, true);
        }

        if (is_array($short) && isset($short[$current]) && is_string($short[$current])) {
            return $short[$current];
        }

        return '';
    }

    /**
     * @param Locale|null $Locale
     * @return string
     */
    public function getName(null | Locale $Locale = null): string
    {
        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        $data = $this->getCustomData();
        $current = $Locale->getCurrent();

        if (is_array($data) && isset($data['name'])) {
            if (is_string($data['name'])) {
                $name = json_decode($data['name'], true);
            } else {
                $name = $data['name'];
            }

            if (is_array($name) && isset($name[$current]) && is_string($name[$current])) {
                return $name[$current];
            }
        }

        return $this->getTitle($Locale);
    }

    /**
     * alias for name
     * - can be used for the `a title=""` attribute
     *
     * @param Locale|null $Locale
     * @return string
     */
    public function getTitleAttribute(null | Locale $Locale = null): string
    {
        return $this->getName($Locale);
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return '';
    }

    /**
     * Resolve a multilingual url value ({"de": "...", "en": "..."}) for the locale,
     * with a fallback to the first non-empty entry;
     * a plain string (old single-language format) is returned unchanged
     * for every language (backward compatible).
     *
     * @param mixed $url
     * @param ?Locale $Locale
     * @return string
     */
    protected function resolveLocalizedUrl(mixed $url, null | Locale $Locale = null): string
    {
        if (is_string($url)) {
            $decoded = json_decode($url, true);

            if (!is_array($decoded)) {
                return trim($url);
            }

            $url = $decoded;
        }

        if (!is_array($url)) {
            return '';
        }

        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        $current = $Locale->getCurrent();

        if (isset($url[$current]) && is_string($url[$current]) && trim($url[$current]) !== '') {
            return trim($url[$current]);
        }

        // fallback, so entries without a translated url keep working
        foreach ($url as $langUrl) {
            if (is_string($langUrl) && trim($langUrl) !== '') {
                return trim($langUrl);
            }
        }

        return '';
    }

    /**
     * @return string
     */
    public function getIcon(): string
    {
        if (isset($this->attributes['icon'])) {
            return is_string($this->attributes['icon']) ? $this->attributes['icon'] : '';
        }

        return '';
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        if (isset($this->attributes['identifier'])) {
            return is_string($this->attributes['identifier']) ? $this->attributes['identifier'] : '';
        }

        return '';
    }

    /**
     * @return string
     */
    public function getRel(): string
    {
        $data = $this->getCustomData();

        if (is_array($data) && isset($data['rel'])) {
            return is_string($data['rel']) ? $data['rel'] : '';
        }

        return '';
    }

    /**
     * @return string
     */
    public function getTarget(): string
    {
        $data = $this->getCustomData();

        if (is_array($data) && isset($data['target'])) {
            switch ($data['target']) {
                case "_self":
                case "frame":
                case "popup":
                case "_blank":
                case "_top":
                case "_parent":
                    return is_string($data['target']) ? $data['target'] : '';
            }
        }

        return '';
    }

    /**
     * return the menu typ for the children
     *
     * @return string
     */
    public function getMenuType(): string
    {
        $data = $this->getCustomData();

        if (is_array($data) && isset($data['menuType'])) {
            return is_string($data['menuType']) ? $data['menuType'] : '';
        }

        return '';
    }

    /**
     * @return mixed
     */
    public function getCustomData(): mixed
    {
        if (isset($this->attributes['data'])) {
            return $this->attributes['data'];
        }

        return null;
    }

    /**
     * @param Locale|null $Locale
     * @return string
     */
    public function getHTML(null | Locale $Locale = null): string
    {
        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        $url = $this->getUrl();
        $title = $this->getTitle($Locale);
        $name = $this->getName($Locale);
        $relValue = $this->getRel();

        // rel attribute
        $rel = '';

        if (!empty($relValue)) {
            switch ($relValue) {
                case 'alternate':
                case 'author':
                case 'bookmark':
                case 'external':
                case 'help':
                case 'license':
                case 'next':
                case 'nofollow':
                case 'noopener':
                case 'noreferrer':
                case 'prev':
                case 'search':
                case 'tag':
                    $rel = 'rel="' . $relValue . '"';
                    break;
            }
        }

        // target attribute
        $target = $this->getTarget();
        $targetAttribute = '';

        if (!empty($target)) {
            $targetAttribute = $target;
        }

        // html link
        return "<a href=\"$url\" title=\"$title\" $rel $targetAttribute>$name</a>";
    }

    //endregion

    //region status

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->getStatus();
    }

    /**
     * @return bool
     */
    public function getStatus(): bool
    {
        $data = $this->getCustomData();

        if (is_array($data) && isset($data['status'])) {
            return !!$data['status'];
        }

        return true;
    }

    //endregion

    //region type stuff

    abstract public static function itemTitle(): string;

    /**
     * Short description of the menu types
     *
     * @return string
     */
    public static function itemShort(): string
    {
        return '';
    }

    /**
     * @return string
     */
    public static function itemIcon(): string
    {
        return 'fa fa-file-o';
    }

    /**
     * @return string
     */
    public static function itemJsControl(): string
    {
        return '';
    }

    //endregion

    //region children

    /**
     * Return the children of this item
     *
     * @param bool $onlyActive - if true, returns only the active children, if false, all children are returned
     * @return list<AbstractMenuItem>
     */
    public function getChildren(bool $onlyActive = true): array
    {
        if ($onlyActive === false) {
            return $this->children;
        }

        return array_values(array_filter($this->children, function (AbstractMenuItem $Item): bool {
            return $Item->isActive();
        }));
    }

    /**
     * Add a child item
     *
     * @param AbstractMenuItem $Item
     */
    public function appendChild(AbstractMenuItem $Item): void
    {
        $this->children[] = $Item;
    }

    //endregion
}
