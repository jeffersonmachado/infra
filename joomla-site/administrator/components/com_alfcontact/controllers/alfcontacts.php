<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// AlfContacts Controller

class AlfcontactControllerAlfcontacts extends JControllerAdmin
{
    /**
    * Proxy for getModel.
    * @since       1.6
    */
    public function getModel($name = 'AlfContact', $prefix = 'AlfContactModel', $config = array('ignore_request' => true)) 
    {
        $model = parent::getModel($name, $prefix, $config);
		
        return $model;
    }

	/**
	 * Method to save the submitted ordering values for records via AJAX.
	 *
	 * @return	void
	 *
	 * @since   3.0
	 */
	public function saveOrderAjax()
	{
		// Get the input
		$input = JFactory::getApplication()->input;
		$pks = $input->post->get('cid', array(), 'array');
		$order = $input->post->get('order', array(), 'array');

		// Sanitize the input
		JArrayHelper::toInteger($pks);
		JArrayHelper::toInteger($order);

		// Get the model
		$model = $this->getModel();

		// Save the ordering
		$return = $model->saveorder($pks, $order);

		if ($return)
		{
			echo "1";
		}

		// Close the application
		JFactory::getApplication()->close();
	}
	
}
