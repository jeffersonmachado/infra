<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

/**
 * AlfContacts View
 */
class AlfcontactViewAlfcontacts extends JViewLegacy
{
	protected $items;
	protected $pagination;
	protected $state;

	/**
    * ALFContacts view display method
    * @return void
    */
    public function display($tpl = null) 
    {
		// Assign data from the model to the view
        $this->items 		= $this->get('Items');
        $this->pagination 	= $this->get('Pagination');
		$this->state 		= $this->get('State');
		
		//Check for errors
		if (count($errors = $this->get('Errors'))) {
			JError::raiseError(500, implode("\n", $errors));
			return false;
		}
		
		// Preprocess the list of items to find ordering divisions.
		foreach ($this->items as &$item) {
			$item->order_up = true;
			$item->order_dn = true;
		}
		
		// Set the toolbar
        $this->addToolBar();
		$this->sidebar = JHtmlSidebar::render();
        
		// Display the template
        parent::display($tpl);
				
    }
		
	/**
	 * Setting the toolbar
	 */
	public function addToolBar() 
	{
		$user = JFactory::getUser();
		
		JToolbarHelper::title(JText::_('COM_ALFCONTACT_MANAGER_ALFCONTACTS'), 'alfcontact');
		
		If ($user->authorise('core.create')){
			JToolbarHelper::addNew('alfcontact.add');
		}
		
		If (($user->authorise('core.edit')) || ($user->authorise('core.edit.own'))) {
			JToolbarHelper::editList('alfcontact.edit');
		}
		
	    If ($user->authorise('core.edit.state')) {
			JToolbarHelper::publish('alfcontacts.publish', 'JTOOLBAR_PUBLISH', true);
			JToolbarHelper::unpublish('alfcontacts.unpublish', 'JTOOLBAR_UNPUBLISH', true);
		}
		
		if ($this->state->get('filter.published') == -2 && $user->authorise('core.delete')) {
			JToolbarHelper::deleteList('', 'alfcontacts.delete', 'JTOOLBAR_EMPTY_TRASH');
		} 
		elseif ($user->authorise('core.edit.state')) {
            JToolbarHelper::trash('alfcontacts.trash');
        }
				
		// Options button.
		If ($user->authorise('core.admin')) {
			JToolBarHelper::preferences('com_alfcontact');
		}
				
		JToolbarHelper::help('JHELP_COMPONENTS_ALFCONTACT_CONTACTS');
		
		JHtmlSidebar::setAction('index.php?option=com_alfcontact&view=alfcontacts');

		JHtmlSideBar::addFilter(
			JText::_('JOPTION_SELECT_PUBLISHED'),
			'filter_published',
			JHtml::_('select.options', JHtml::_('jgrid.publishedOptions'), 'value', 'text', $this->state->get('filter.published'), true)
		);

		JHtmlSideBar::addFilter(
			JText::_('JOPTION_SELECT_ACCESS'),
			'filter_access',
			JHtml::_('select.options', JHtml::_('access.assetgroups'), 'value', 'text', $this->state->get('filter.access'))
		);
		
		JHtmlSidebar::addFilter(
			JText::_('JOPTION_SELECT_LANGUAGE'),
			'filter_language',
			JHtml::_('select.options', JHtml::_('contentlanguage.existing', true, true), 'value', 'text', $this->state->get('filter.language'))
		);
	}
	
	/**
	 * Returns an array of fields the table can be sorted by
	 *
	 * @return  array  Array containing the field name to sort by as the key and display text as value
	 *
	 * @since   3.0
	 */
	protected function getSortFields()
	{
		return array(
			'ordering'  => JText::_('JGRID_HEADING_ORDERING'),
			'published' => JText::_('JSTATUS'),
			'name'      => JText::_('COM_ALFCONTACT_CONTACT_NAME'),
			'email'	    => JText::_('COM_ALFCONTACT_CONTACT_EMAIL'),
			'prefix'    => JText::_('COM_ALFCONTACT_CONTACT_PREFIX'),
			'extra'		=> JText::_('COM_ALFCONTACT_CONTACT_EXTRA'),
			'defsubject'=> JText::_('COM_ALFCONTACT_CONTACT_DEFAULT_SUBJECT'),
			'access'    => JText::_('JGRID_HEADING_ACCESS'),
			'language'	=> JText::_('JGRID_HEADING_LANGUAGE'),
			'id'        => JText::_('JGRID_HEADING_ID')
		);
	}
	
}
