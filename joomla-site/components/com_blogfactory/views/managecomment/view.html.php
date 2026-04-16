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

class BlogFactoryFrontendViewManageComment extends BlogFactoryFrontendView
{
    protected
        $option = 'com_blogfactory',
        $variables = array('form', 'item', 'data'),
        $css = array('icons'),
        $jhtmls = array(
        'behavior.tooltip',
        'jquery.framework',
    );

    protected function loadVariables()
    {
        parent::loadVariables();

        $this->form->bind($this->data);

        return true;
    }
}
