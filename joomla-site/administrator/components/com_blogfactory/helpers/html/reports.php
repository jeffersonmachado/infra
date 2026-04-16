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

class JHtmlBlogFactoryReports
{
    public static function resolved($value, $i, $prefix = '', $enabled = true, $checkbox = 'cb')
    {
        $states = array(
            1 => array('unresolve', BlogFactoryText::_('resolved'), BlogFactoryText::_('unresolve_item'), BlogFactoryText::_('resolved'), false, 'publish', 'publish'),
            0 => array('resolve', BlogFactoryText::_('unresolved'), BlogFactoryText::_('resolve_item'), BlogFactoryText::_('unresolved'), false, 'unpublish', 'unpublish'),
        );

        return JHtml::_('jgrid.state', $states, $value, $i, $prefix, $enabled, true, $checkbox);
    }
}
