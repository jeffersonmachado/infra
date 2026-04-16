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

class BlogFactoryFrontendModelRatings extends JModelLegacy
{
    public function deleteForComment($id)
    {
        $dbo = $this->getDbo();
        $query = $dbo->getQuery(true)
            ->delete()
            ->from('#__com_blogfactory_votes')
            ->where('type = ' . $dbo->quote('comment'))
            ->where('item_id = ' . $dbo->quote($id));

        $result = $dbo->setQuery($query)
            ->execute();

        return $result;
    }
}
