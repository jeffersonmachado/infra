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

class BlogFactoryBackendControllerNotifications extends JControllerAdmin
{
    protected $option = 'com_blogfactory';

    public function getModel($name = 'Notification', $prefix = 'BlogFactoryBackendModel', $config = array())
    {
        return parent::getModel($name, $prefix, $config);
    }
}
