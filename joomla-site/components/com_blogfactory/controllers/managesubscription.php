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

class BlogFactoryFrontendControllerManageSubscription extends JControllerLegacy
{
    public function delete()
    {
        $batch = $this->input->get('batch', array($this->input->getInt('id')), 'array');
        $model = $this->getModel('ManageSubscription');
        $return = $model->delete($batch);
        $response = array();

        if ($return) {
            $response['status'] = 1;
            $response['message'] = BlogFactoryText::plural('managesubscription_task_delete_success', count($batch));
        } else {
            $response['status'] = 0;
            $response['message'] = BlogFactoryText::plural('managesubscription_task_delete_error', count($batch));
            $response['error'] = $model->getState('error');
        }

        if ($this->isXMLHttpRequest()) {
            echo json_encode($response);
            jexit();
        }

        if (!$return) {
            JFactory::getApplication()->enqueueMessage($response['error'], 'error');
        }

        $redirect = $this->input->server->getString('HTTP_REFERER', BlogFactoryRoute::view('managesubscriptions'));
        $this->setRedirect($redirect, $response['message']);
    }

    protected function isXMLHttpRequest()
    {
        return strtolower($this->input->server->getString('HTTP_X_REQUESTED_WITH', '')) == 'xmlhttprequest';
    }
}
