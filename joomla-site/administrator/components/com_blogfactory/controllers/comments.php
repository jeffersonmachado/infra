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

class BlogFactoryBackendControllerComments extends JControllerAdmin
{
    protected $option = 'com_blogfactory';

    public function __construct($config = array())
    {
        parent::__construct($config);

        $this->registerTask('unapprove', 'approve');
    }

    public function getModel($name = 'Comment', $prefix = 'BlogFactoryBackendModel', $config = array())
    {
        return parent::getModel($name, $prefix, $config);
    }

    public function approve()
    {
        // Check for request forgeries
        JSession::checkToken() or die(JText::_('JINVALID_TOKEN'));

        // Get items to publish from the request.
        $cid = JFactory::getApplication()->input->get('cid', array(), 'array');
        $data = array('approve' => 1, 'unapprove' => 0);
        $task = $this->getTask();
        $value = JArrayHelper::getValue($data, $task, 0, 'int');

        if (empty($cid)) {
            JLog::add(JText::_($this->text_prefix . '_NO_ITEM_SELECTED'), JLog::WARNING, 'jerror');
        } else {
            // Get the model.
            $model = $this->getModel();

            // Make sure the item ids are integers
            JArrayHelper::toInteger($cid);

            // Resolve the items.
            try {
                $model->approve($cid, $value);

                if ($value == 1) {
                    $ntext = $this->text_prefix . '_N_ITEMS_APPROVED';
                } else {
                    $ntext = $this->text_prefix . '_N_ITEMS_UNAPPROVED';
                }

                $this->setMessage(JText::plural($ntext, count($cid)));
            } catch (Exception $e) {
                $this->setMessage($model->getError(), 'error');
            }

        }
        $extension = $this->input->get('extension');
        $extensionURL = ($extension) ? '&extension=' . $extension : '';
        $this->setRedirect(JRoute::_('index.php?option=' . $this->option . '&view=' . $this->view_list . $extensionURL, false));
    }
}
