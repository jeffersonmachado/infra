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

class BlogFactoryFrontendViewPost extends BlogFactoryFrontendView
{
    protected
        $variables = array(
        'item',
        'category',
        'tags',
        'bookmarks',
        'vote',
        'categoriesEnabled',
        'ratingsEnabled',
        'bookmarksEnabled',
        'subscribed',
        'blog',
        'allowedRating'
    ),
        $css = array('icons', 'tooltip', 'notification'),
        $js = array('tooltip', 'notification'),
        $jhtmls = array('bootstrap.tooltip', 'jquery.framework');

    protected function beforeDisplay()
    {
        parent::beforeDisplay();

        $settings = JComponentHelper::getParams('com_blogfactory');

        if (!isset($this->item->id) || !$this->item->id) {
            $this->setLayout('not_found');
        } elseif ((2 == $this->item->visibility || !$settings->get('post.enable.guest_view', 1)) && JFactory::getUser()->guest) {
            $this->setLayout('not_visible');
        }
    }

    protected function getCategoriesEnabled()
    {
        $settings = JComponentHelper::getParams('com_blogfactory');

        return $settings->get('general.enable.categories', 1);
    }

    protected function getRatingsEnabled()
    {
        $settings = JComponentHelper::getParams('com_blogfactory');

        return $settings->get('post.enable.votes', 1);
    }

    protected function getBookmarksEnabled()
    {
        $settings = JComponentHelper::getParams('com_blogfactory');

        return $settings->get('post.enable.bookmarks', 1);
    }

    protected function setMetadata($document)
    {
        // Set title.
        $document->setTitle($this->item->title);

        // Set description.
        $document->setDescription($this->item->metadata->get('description'));

        // Set keywords.
        $document->setMetaData('keywords', $this->item->metadata->get('keywords'));

        // Set pinbback url.
        $pburl = JURI::root() . 'index.php?option=com_blogfactory&amp;task=post.pingback&amp;format=raw';
        $pblink = '<link rel="pingback" href="' . $pburl . '" />';

        $document->setHeadData(array('custom' => array($pblink)));
        header('X-Pingback: ' . $pburl);
    }
}
