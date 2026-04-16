<?php

/**
-------------------------------------------------------------------------
blogfactory - Blog Factory 4.3.0
-------------------------------------------------------------------------
 * @author thePHPfactory
 * @copyright Copyright (C) 2011 SKEPSIS Consult SRL. All Rights Reserved.
 * @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * Websites: http://www.thePHPfactory.com
 * Technical Support: Forum - http://www.thePHPfactory.com/forum/
-------------------------------------------------------------------------
*/

defined('_JEXEC') or die;

class BlogFactoryFrontendViewPosts extends BlogFactoryFrontendView
{
    protected
        $variables = array(
        'items',
        'pagination',
        'categoriesEnabled',
        'filterTag',
        'filterCategory',
        'filterDate',
    ),
        $css = array('icons');

    protected function beforeDisplay()
    {
        parent::beforeDisplay();

        foreach ($this->items as $i => $item) {
            if (false !== strpos($item->content, '<hr class="blogfactory-read-more" />')) {
                $content = explode('<hr class="blogfactory-read-more" />', $item->content);
                $this->items[$i]->content = $content[0];
                $this->items[$i]->read_more = true;
            } else {
                $this->items[$i]->read_more = false;
            }

            $this->items[$i]->tags = $this->getModel()->getTags($item->id);
        }
    }

    protected function getCategoriesEnabled()
    {
        $settings = JComponentHelper::getParams('com_blogfactory');

        return $settings->get('general.enable.categories', 1);
    }
}
