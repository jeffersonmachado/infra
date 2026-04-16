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

class BlogFactoryBackendControllerPosts extends JControllerAdmin
{
    protected $option = 'com_blogfactory';

    public function getModel($name = 'Post', $prefix = 'BlogFactoryBackendModel', $config = array())
    {
        return parent::getModel($name, $prefix, $config);
    }

    public function export()
    {
        // Check for request forgeries
        JSession::checkToken() or die(JText::_('JINVALID_TOKEN'));

        // Get items to export from the request.
        $cid = $this->input->get('cid', array(), 'array');
        $export = $this->input->get('export', array(), 'array');

        if (empty($cid) || empty($export)) {
            JLog::add(JText::_($this->text_prefix . '_NO_ITEM_SELECTED'), JLog::WARNING, 'jerror');
        } else {
            // Get the model.
            $model = $this->getModel();

            // Make sure the item ids are integers
            JArrayHelper::toInteger($cid);

            // Export the items.
            try {
                $model->export($cid, $export);
                $this->setMessage(JText::plural($this->text_prefix . '_N_ITEMS_EXPORTED', count($cid)));
            } catch (Exception $e) {
                $this->setMessage($e->getMessage(), 'error');
            }
        }

        $this->setRedirect(JRoute::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));
    }

    public function import()
    {
        // Check for request forgeries
        JSession::checkToken() or die(JText::_('JINVALID_TOKEN'));

        // Get items to import from the request.
        $import = $this->input->get('import', array(), 'array');

        // Get the model.
        $model = $this->getModel();

        // Import the items.
        try {
            $model->import($import);
            $this->setMessage(JText::plural($this->text_prefix . '_N_ITEMS_IMPORTED', $model->getState('items.imported')));
        } catch (Exception $e) {
            $this->setMessage($e->getMessage(), 'error');
        }

        $this->setRedirect(JRoute::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));
    }

    public function batch()
    {
        // Check for request forgeries
        JSession::checkToken() or die(JText::_('JINVALID_TOKEN'));

        // Get items to import from the request.
        $cid = $this->input->get('cid', array(), 'array');
        $batch = $this->input->get('batch', array(), 'array');

        if (empty($cid) || empty($batch)) {
            JLog::add(JText::_($this->text_prefix . '_NO_ITEM_SELECTED'), JLog::WARNING, 'jerror');
        } else {
            // Get the model.
            $model = $this->getModel();

            // Make sure the item ids are integers
            JArrayHelper::toInteger($cid);

            // Import the items.
            try {
                $model->batch($cid, $batch);
                $this->setMessage(JText::plural($this->text_prefix . '_N_ITEMS_PROCESSED', count($cid)));
            } catch (Exception $e) {
                $this->setMessage($e->getMessage(), 'error');
            }
        }

        $this->setRedirect(JRoute::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));
    }
}
