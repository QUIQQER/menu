<?php

namespace QUI\Menu\Mega;

use QUI;
use QUI\Exception;
use QUI\Interfaces\Projects\Site;

/**
 * Class AbstractMenu
 * Starting point for menu controls
 *
 * @package QUI\Menu
 */
abstract class AbstractChild extends QUI\Control
{
    /**
     * @var array<int, Site>|null
     */
    protected ?array $children = null;

    /**
     * Return the current site
     *
     * @return Site
     * @throws Exception
     */
    protected function getSite(): QUI\Interfaces\Projects\Site
    {
        if ($this->getAttribute('Site')) {
            return $this->getAttribute('Site');
        }

        $Site = QUI::getRewrite()->getSite();

        if ($Site === null) {
            throw new Exception('No rewrite site available.');
        }

        return $Site;
    }

    /**
     * @return array<int, Site>|null
     * @throws Exception
     */
    public function getChildren(): ?array
    {
        if (is_null($this->children)) {
            $children = $this->getSite()->getNavigation();
            $this->children = is_array($children) ? $children : [];
        }

        return $this->children;
    }

    /**
     * Returns the number of children
     *
     * @return int
     * @throws Exception
     */
    public function count(): int
    {
        return count($this->getChildren() ?? []);
    }
}
