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

class BlogFactoryFrontendModelManageBookmark extends JModelLegacy
{
    public function delete($ids)
    {
        // Initialise variables.
        $ids = (array)$ids;
        $user = JFactory::getUser();

        JArrayHelper::toInteger($ids);

        foreach ($ids as $id) {
            $table = $this->getTable('Follower', 'BlogFactoryTable');

            // Check if bookmark exists.
            if (!$id || !$table->load(array('user_id' => $user->id, 'blog_id' => $id))) {
                $this->setState('error', BlogFactoryText::_('bookmark_delete_error_not_found'));
                return false;
            }

            if (!$table->delete()) {
                return false;
            }
        }

        return true;
    }
}
