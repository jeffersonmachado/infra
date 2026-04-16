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

class BlogFactoryFrontendControllerHelper extends JControllerLegacy
{
    public function parseDate()
    {
        $date = $this->input->get('date', array(), 'array');
        $model = $this->getModel('Helper');
        $response = array();

        $value = $model->parseDate($date);

        if ($value) {
            $response['status'] = 1;
            $response['date'] = $model->getState('date.value');
            $response['label'] = $model->getState('date.label');
        } else {
            $response['status'] = 0;
        }

        echo json_encode($response);

        jexit();
    }

    public function alias()
    {
        $query = $this->input->getString('query');
        $model = $this->getModel('Helper');
        $response = array();

        $response['status'] = 1;
        $response['alias'] = $model->getAlias($query);

        echo json_encode($response);

        jexit();
    }
}
