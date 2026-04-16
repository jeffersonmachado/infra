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

class BlogFactoryBackendViewSettings extends BlogFactoryBackendView
{
    protected
        $option = 'com_blogfactory',
        $variables = array('form', 'formLayout', 'activeTab', 'guestGroup'),
        $buttons = array('apply', 'save', 'cancel'),
        $js = array('jquery.cookie'),
        $jhtmls = array('behavior.tooltip', 'formbehavior.chosen/select');

    protected function getActiveTab()
    {
        return JFactory::getApplication()->input->cookie->getCmd('com_blogfactory_settings_tab', 'general');
    }

    protected function getFormLayout()
    {
        return BlogFactoryHelper::getFormLayout($this->form);
    }

    protected function renderFieldsets($fieldsets)
    {
        $this->fieldsets = $fieldsets;

        return $this->loadTemplate('fieldset');
    }

    protected function getSingularName()
    {
        return $this->getName();
    }
}
