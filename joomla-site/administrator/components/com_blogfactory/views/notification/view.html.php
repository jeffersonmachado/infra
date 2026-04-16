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

class BlogFactoryBackendViewNotification extends BlogFactoryBackendView
{
    protected
        $option = 'com_blogfactory',
        $variables = array('form', 'item', 'state'),
        $buttons = array('apply', 'save', 'save2copy', 'save2new', 'cancel'),
        $jhtmls = array('behavior.tooltip', 'formbehavior.chosen/select');

    protected function setTitle()
    {
        if ($this->item->id) {
            JToolbarHelper::title(BlogFactoryText::sprintf('page_heading_' . $this->getName() . '_edit', $this->item->subject));
        } else {
            JToolbarHelper::title(BlogFactoryText::_('page_heading_' . $this->getName() . '_new'));
        }
    }

    protected function loadFieldset($fieldset)
    {
        $this->fieldset = $fieldset;

        return $this->loadTemplate('fieldset');
    }
}
