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

interface BlogFactoryDataProviderInterface
{
    public function getItems($userId, $limitstart = 0, $limit = 0, $search = null);

    public function getTotal($userId, $search = null);
}

class BlogFactoryDataProvider
{
    protected $dbo;

    public function __construct($config = array())
    {
        if (isset($config['dbo'])) {
            $this->dbo = $config['dbo'];
        } else {
            $this->dbo = JFactory::getDbo();
        }
    }

    public function getDbo()
    {
        return $this->dbo;
    }
}
