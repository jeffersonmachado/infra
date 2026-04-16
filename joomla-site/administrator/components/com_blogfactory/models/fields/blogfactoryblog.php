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

class JFormFieldBlogFactoryBlog extends JFormFieldList
{
    public function getOptions()
    {
        $dbo = JFactory::getDbo();
        $query = $dbo->getQuery(true)
            ->select('b.id AS value, b.title AS text')
            ->from('#__com_blogfactory_blogs b')
            ->order('b.title ASC');

        $options = $dbo->setQuery($query)
            ->loadObjectList();

        $options = array_merge(parent::getOptions(), $options);

        return $options;
    }
}
