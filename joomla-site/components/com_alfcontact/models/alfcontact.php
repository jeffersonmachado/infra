<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
 
/**
 * AlfContact Model
 */
class AlfcontactModelAlfcontact extends JModelList
{
    protected function getListQuery()
    {
        $user = JFactory::getUser();
		$groups = implode(',', $user->getAuthorisedViewLevels());
		
		// Create a new query object.         
        $db = JFactory::getDBO();
        $query = $db->getQuery(true);
        // Select some fields
        $query->select('id,name,email,prefix,extra,defsubject,published,access');
        // From the alfcontact table
        $query->from('#__alfcontact');
        // Disable unpublished items
        $query->where('published > 0');
		// Accesslevels
		$query->where('access IN ('.$groups.')');
		// Language
		$query->where('language in (' . $db->Quote(JFactory::getLanguage()->getTag()) . ',' . $db->Quote('*') . ')');
		// Ordering
		$query->order('`ordering` ASC');
		
        return $query;
    }
	
	protected function populateState($ordering=null, $direction=null)
	{
		$this->setState('list.limit', 150);
	}
	
	function CheckCaptcha()
	{
		$app = JFactory::getApplication();
		$jinput = $app->input;
		$user = JFactory::getUser();
        //$config = JFactory::getConfig();

        // get default captcha from global config
        //$defcaptcha = $config->get('captcha');

        $params  = JComponentHelper::getParams('com_alfcontact');
		$captcha = $params->get('captcha');
		
		// not using captcha!
		if (($captcha == 0) OR (($captcha == 2) AND ($user->name))) {
			return true;
		}
		
		$return = false;

		$post = $jinput->getArray($_POST);
		JPluginHelper::importPlugin('captcha', 'recaptcha');
		$dispatcher = JEventDispatcher::getInstance();
		$resp = $dispatcher->trigger('onCheckAnswer', $post['recaptcha_response_field']);
		if ($resp[0])
		{
			$return = true;
		}

				
		if ($return) {
			return true;
		} else { 
			return false; 
		}
	}
}