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

class JFormFieldBlogFactoryReportUser extends JFormField
{
    public function getInput()
    {
        $username = $this->getUsername($this->value);

        return '<a href="' . JRoute::_('index.php?option=com_users&task=user.edit&id=' . $this->value) . '">' . $username . '</a>';
    }

    protected function getUsername($id)
    {
        $user = JTable::getInstance('User');
        $user->load($id);

        return $user->username;
    }
}
