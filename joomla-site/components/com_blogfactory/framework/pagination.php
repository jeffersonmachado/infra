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

class BlogFactoryPagination extends JPagination
{
    protected $anchor;

    public function getPagesLinks()
    {
        $html = array();

        if ($this->pagesTotal > 1) {
            $html[] = '<div class="pagination">';
            $html[] = parent::getPagesLinks();
            $html[] = '</div>';
        }

        return implode("\n", $html);
    }

    public function setAnchor($anchor)
    {
        $this->anchor = $anchor;
    }

    public function getAnchor()
    {
        return $this->anchor;
    }

    protected function _buildDataObject()
    {
        $data = parent::_buildDataObject();

        if (null !== $anchor = $this->getAnchor()) {
            $pages = array('start', 'previous', 'next', 'end', 'all');

            foreach ($pages as $page) {
                if (isset($data->$page)) {
                    $data->$page->link .= '#' . $anchor;
                }
            }

            foreach ($data->pages as &$page) {
                $page->link .= '#' . $anchor;
            }
        }

        return $data;
    }
}
