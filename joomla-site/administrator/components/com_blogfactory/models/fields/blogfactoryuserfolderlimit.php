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

class JFormFieldBlogFactoryUserFolderLimit extends JFormField
{
    public function getInput()
    {
        $html = array();

        $html[] = '<div>';
        foreach ($this->getGroups() as $group) {
            $id = $this->id . '_' . $group->value;
            $name = $this->name . '[' . $group->value . ']';
            $value = isset($this->value[$group->value]) ? (int)$this->value[$group->value] : 10;

            $html[] = '<div>';
            $html[] = '<label for="' . $id . '">' . $group->text . '</label>';
            $html[] = '<input type="text" id="' . $id . '" name="' . $name . '" value="' . $value . '" />';
            $html[] = '</div>';
        }
        $html[] = '</div>';

        return implode("\n", $html);
    }

    protected function getGroups()
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->select('a.id AS value, a.title AS text, COUNT(DISTINCT b.id) AS level')
            ->from($db->quoteName('#__usergroups') . ' AS a')
            ->join('LEFT', $db->quoteName('#__usergroups') . ' AS b ON a.lft > b.lft AND a.rgt < b.rgt')
            ->group('a.id, a.title, a.lft, a.rgt')
            ->order('a.lft ASC');
        $db->setQuery($query);
        $groups = $db->loadObjectList();

        return $groups;
    }
}
