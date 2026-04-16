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

class JFormFieldBlogFactoryReportItemId extends JFormField
{
    public function getInput()
    {
        $type = $this->form->getValue('type');
        $url = $this->getUrlForItem($this->value, $type);

        return '<a href="' . $url . '">' . BlogFactoryText::_('report_type_' . $type) . '</a>';
    }

    protected function getUrlForItem($id, $type)
    {
        $url = '#';

        switch ($type) {
            case 'comment':
                $url = BlogFactoryRoute::task('comment.edit&id=' . $id);

                break;
        }

        return $url;
    }
}
