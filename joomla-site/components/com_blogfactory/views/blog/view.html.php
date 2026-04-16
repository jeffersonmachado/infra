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

class BlogFactoryFrontendViewBlog extends BlogFactoryFrontendView
{
    protected
        $variables = array(
        'blog',
        'posts',
        'pagination',
        'categoriesEnabled',
    ),
        $css = array('posts', 'icons');

    public function __construct($config = array())
    {
        parent::__construct($config);

        $this->addTemplatePath(JPATH_COMPONENT_SITE . '/views/posts/tmpl/');
        $this->addTemplatePath(JPATH_COMPONENT_SITE . '/views/blog/tmpl/');
    }

    protected function beforeDisplay()
    {
        parent::beforeDisplay();

        $model = JModelLegacy::getInstance('Posts', 'BlogFactoryFrontendModel');

        foreach ($this->posts as $i => $item) {
            if (false !== strpos($item->content, '<hr class="blogfactory-read-more" />')) {
                $content = explode('<hr class="blogfactory-read-more" />', $item->content);
                $this->posts[$i]->content = $content[0];
                $this->posts[$i]->read_more = true;
            } else {
                $this->posts[$i]->read_more = false;
            }

            $this->posts[$i]->tags = $model->getTags($item->id);
        }
    }

    protected function getCategoriesEnabled()
    {
        $settings = JComponentHelper::getParams('com_blogfactory');

        return $settings->get('general.enable.categories', 1);
    }
}
