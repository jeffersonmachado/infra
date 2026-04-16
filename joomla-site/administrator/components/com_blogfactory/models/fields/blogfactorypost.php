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

JFormHelper::loadFieldType('List');

class JFormFieldBlogFactoryPost extends JFormFieldList
{
    public function getOptions()
    {
        $dbo = JFactory::getDbo();
        $query = $dbo->getQuery(true)
            ->select('p.id AS value, CONCAT(p.title, "&nbsp;(", u.username, ")") AS text')
            ->from('#__com_blogfactory_posts p')
            ->order('u.username ASC, p.title ASC');

        $query->leftJoin('#__users u ON u.id = p.user_id');

        $results = $dbo->setQuery($query)
            ->loadObjectList();

        return $results;
    }
}
