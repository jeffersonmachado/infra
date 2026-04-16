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

class BlogFactoryFrontendControllerReport extends JControllerLegacy
{
    public function send()
    {
        $data = JFactory::getApplication()->input->get('jform', array(), 'array');
        $model = $this->getModel('Report');
        $response = array();

        $data['user_id'] = JFactory::getUser()->id;

        if ($model->send($data)) {
            $response['status'] = 1;
            $response['message'] = BlogFactoryText::_('report_send_success');
        } else {
            $response['status'] = 0;
            $response['message'] = BlogFactoryText::_('report_send_error');
            $response['error'] = $model->getState('error');
        }

        echo json_encode($response);

        jexit();
    }
}
