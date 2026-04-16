<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
 
/**
 * AlfContact View
 */
class AlfcontactViewAlfcontact extends JViewLegacy
{
        /**
         * display method of AlfContact view
         * @return void
         */
        public function display($tpl = null) 
        {
                // get the Data from the model and assign to the view
                $this->form = $this->get('Form');
                $this->item = $this->get('Item');
				$this->state= $this->get('State');
 
                //Check for errors.
                if (count($errors = $this->get('Errors'))) 
                {
                        JError::raiseError(500, implode('<br />', $errors));
                        return false;
                }
 
                // Set the toolbar
                $this->addToolBar();
 
                // Display the template
                parent::display($tpl);
				
        }
 
        /**
         * Setting the toolbar
         */
        protected function addToolBar() 
        {
            $user  = JFactory::getUser();
			$isNew = $this->item->id == 0;
            JToolBarHelper::title($isNew ? JText::_('COM_ALFCONTACT_MANAGER_ALFCONTACT_NEW') : JText::_('COM_ALFCONTACT_MANAGER_ALFCONTACT_EDIT'),'alfcontact');
            
			if ($user->authorise('core.edit')){		
				JToolBarHelper::apply('alfcontact.apply');
				JToolBarHelper::save('alfcontact.save');
            }
			
			if ($user->authorise('core.create')){
				JToolBarHelper::save2new('alfcontact.save2new');
			}
			
			if (!$isNew && $user->authorise('core.create')){
				JToolBarHelper::save2copy('alfcontact.save2copy');
			}
						
			if (empty($this->item->id)){
				JToolBarHelper::cancel('alfcontact.cancel');
            }
			else {
				JToolBarHelper::cancel('alfcontact.cancel', 'JTOOLBAR_CLOSE');
			}	
        }
}
