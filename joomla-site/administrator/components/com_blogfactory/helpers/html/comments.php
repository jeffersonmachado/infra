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

class JHtmlBlogFactoryComments
{
    public static function approved($value, $i, $prefix = '', $enabled = true, $checkbox = 'cb')
    {
        $states = array(
            1 => array('unapprove', BlogFactoryText::_('approved'), BlogFactoryText::_('unapprove_item'), BlogFactoryText::_('approved'), false, 'publish', 'publish'),
            0 => array('approve', BlogFactoryText::_('unapproved'), BlogFactoryText::_('approve_item'), BlogFactoryText::_('unapproved'), false, 'pending', 'pending'),
        );

        return JHtml::_('jgrid.state', $states, $value, $i, $prefix, $enabled, true, $checkbox);
    }
}
