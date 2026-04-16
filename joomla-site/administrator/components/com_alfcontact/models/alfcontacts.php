<?php

defined('_JEXEC') or die('Restricted access');

// AlfContactList Model
class AlfcontactModelAlfcontacts extends JModelList
{
    //Overriding the constructor to enable sorting
	public function __construct($config = array())
	{
		if (empty($config['filter_fields'])){
			$config['filter_fields'] = array(
				'name', 'a.name',
				'ordering', 'a.ordering',
				'published', 'a.published',
				'access', 'a.access', 'access_level',
				'id', 'a.id',
				'email', 'a.email',
				'prefix', 'a.prefix',
				'extra', 'a.extra',
				'defsubject', 'a.defsubject',
				'language', 'a.language', 'language_title',
			);
		}
		
		parent::__construct($config);
	}
	
	public function getItems()
	{
		$items = parent::getItems();
		foreach ($items as $item){
			$item->url = 'index.php?option=com_alfcontact&amp;task=alfcontact.edit&amp;id=' . $item->id;
		}
		
		return $items;
	}
	
	public function getListQuery()
	{
		$user 	= JFactory::getUser();

		// Create a new query object.
		$db		= $this->getDbo();
		$query 	= $db->getQuery(true);
		
		//$query->select('a.*, g.title AS access_level');
		//$query->from('#__alfcontact AS a');
		//$query->leftJoin('#__viewlevels AS g ON g.id = a.access');
		
		$query->select('a.*');
		$query->from($db->quoteName('#__alfcontact').' AS a');
		
		//Join over the language
		$query->select('l.title AS language_title');
		$query->leftjoin($db->quoteName('#__languages').' AS l ON l.lang_code = a.language');

		// Join over the asset groups.
		$query->select('ag.title AS access_level');
		$query->leftjoin($db->quoteName('#__viewlevels').' AS ag ON ag.id = a.access');
	
		// Filter by access level.
		if ($access = $this->getState('filter.access')) {
			$query->where('a.access = '.(int) $access);
		}

		// Implement View Level Access
		if (!$user->authorise('core.admin'))
		{
		    $groups	= implode(',', $user->getAuthorisedViewLevels());
			$query->where('a.access IN ('.$groups.')');
		}
		
		// Filter by published state
		$published = $this->getState('filter.published');
		if (is_numeric($published)) {
			$query->where('a.published = ' . (int) $published);
		}
		elseif ($published === '') {
			$query->where('(a.published = 0 OR a.published = 1)');
		}
		
		$search = $this->getState('filter.search');
				
		if (!empty($search)) {
			$search = '%' . $db->escape($search, true) . '%';
			
			$field_searches = "name LIKE '{$search}' OR email LIKE '{$search}'";
			
			$query->where($field_searches);				
		}
 		
		// Filter on the language.
		if ($language = $this->getState('filter.language')) {
			$query->where('a.language = ' . $db->quote($language));
		}
		
		// Column ordering
		$orderCol = $this->getState('list.ordering');
		$orderDirn = $this->getState('list.direction');
		
		if ($orderCol != '') {
			$query->order($db->escape($orderCol.' '.$orderDirn));
		}
		
		return $query;
	}
	
	protected function populateState($ordering = null, $direction = null)
	{
		$search = $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search');
		$this->setState('filter.search', $search);
		
		$accessId = $this->getUserStateFromRequest($this->context.'.filter.access', 'filter_access', null, 'int');
		$this->setState('filter.access', $accessId);
		
		$published = $this->getUserStateFromRequest($this->context.'.filter.published', 'filter_published');
		$this->setState('filter.published', $published);
		
		$language = $this->getUserStateFromRequest($this->context.'.filter.language', 'filter_language', '');
		$this->setState('filter.language', $language);
		
		parent::populateState($ordering, $direction);
	}
}
