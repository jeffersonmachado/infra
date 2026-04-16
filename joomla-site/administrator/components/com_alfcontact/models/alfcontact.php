<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
 
/**
 * AlfContact Model
 */
class AlfcontactModelAlfcontact extends JModelAdmin
{
    /**
	* @var		string	The prefix to use with controller messages.
	* @since	1.6
	*/
	protected $text_prefix = 'COM_ALFCONTACT';
		
	/**
    * Returns a reference to the a Table object, always creating it.
    *
    * @param       type    The table type to instantiate
    * @param       string  A prefix for the table class name. Optional.
    * @param       array   Configuration array for model. Optional.
    * @return      JTable  A database object
    * @since       1.6
    */
    public function getTable($type = 'AlfContact', $prefix = 'AlfContactTable', $config = array()) 
    {
        return JTable::getInstance($type, $prefix, $config);
    }
    
    /**
    * Method to get the record form.
    *
    * @param       array   $data           Data for the form.
    * @param       boolean $loadData       True if the form is to load its own data (default case), false if not.
    * @return      mixed   A JForm object on success, false on failure
    * @since       1.6
    */
    public function getForm($data = array(), $loadData = true) 
    {
        // Get the form.
        $form = $this->loadForm('com_alfcontact.alfcontact', 'alfcontact', array('control' => 'jform', 'load_data' => $loadData));
        if (empty($form)) 
        {
            return false;
        }

        return $form;
    }
    
    /**
         * Method to get the data that should be injected in the form.
         *
         * @return      mixed   The data for the form.
         * @since       1.6
         */
        protected function loadFormData() 
        {
                // Check the session for previously entered form data.
                $data = JFactory::getApplication()->getUserState('com_alfcontact.edit.alfcontact.data', array());
                if (empty($data)) 
                {
                        $data = $this->getItem();
                }
                return $data;
        }
		
	/**
	 * Prepare and sanitise the table prior to saving.
	 *
	 * @since	1.6
	 */
	protected function prepareTable($table)
	{
		if (empty($table->id)) 
		{
			// Set the values

			// Set ordering to the last item if not set
			if (empty($table->ordering)) {
				$db = JFactory::getDbo();
				$db->setQuery('SELECT MAX(ordering) FROM #__alfcontact');
				$max = $db->loadResult();

				$table->ordering = $max+1;
			}
		}
		else 
		{
			// Set the values
		}
	}
		
}
