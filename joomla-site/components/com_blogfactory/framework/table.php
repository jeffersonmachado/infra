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

class BlogFactoryTable extends JTable
{
    protected $table;
    protected $pk = 'id';

    public function __construct($dbo = null)
    {
        if (is_null($dbo)) {
            $dbo = JFactory::getDbo();
        }

        parent::__construct($this->table, $this->pk, $dbo);
    }

    public function __toString()
    {
        return $this->getTableName();
    }

    public function check()
    {
        if (!parent::check()) {
            return false;
        }

        // Set created at date.
        if (property_exists($this, 'created_at') && !$this->{$this->pk} && (is_null($this->created_at) || $this->getDbo()->getNullDate() == $this->created_at)) {
            $this->created_at = JFactory::getDate()->toSql();
        }

        // Set updated at date.
        if (property_exists($this, 'updated_at')) {
            $this->updated_at = JFactory::getDate()->toSql();
        }

        return true;
    }
}
