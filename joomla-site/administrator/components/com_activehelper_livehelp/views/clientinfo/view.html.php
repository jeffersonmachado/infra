<?php

/**
 * @Product     ActiveHelper LiveHelp component for Joomla
 * @package		Client info
 * @subpackage	View
 * @copyright	(C) 2010- 2017 ActiveHelper Inc.
 * @author		ActiveHelper Inc.
 * @version     5.0
 * @Joomla      3.7
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html
*/


defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.application.component.view');

class activehelper_livehelpViewclientinfo extends JViewLegacy
{
  function display($tpl = null)
	{
      $item	=$this->get('Data');

	 $jinput   = JFactory::getApplication()->input;		  
     $cid      = $jinput->get( 'cid', array(0), '', 'array' ); 
		
		 
    $uri =JURI::getInstance();

  if ($uri->isSSL() == true)
     {$protocole = 'https://';
      $ssl ='ON'; }
    else
      {$protocole = 'http://';
       $ssl ='OFF'; }

    $host        =  $protocole.$uri->getHost();
    $server_dir  = JURI::root(true) . '/' . 'components/com_activehelper_livehelp/server/';
    $usr         =  $this-> username($cid[0]);
    $account     = 'default';

    $this->assignRef('server', $host);
    $this->assignRef('server_path', $server_dir);
    $this->assignRef('login', $usr);
    $this->assignRef('account', $account);
    $this->assignRef('ssl', $ssl);

    // Set the toolbar
     $this->addToolBar($item);
        
    parent::display($tpl);

	}

   function username($id)
    {
       $db = JFactory::getDbo();
       $query = $db->getQuery(true);
         
    $query->select('username')
       ->from('#__livehelp_users')
       ->where('id = ' . $id);

       $db->setQuery($query);
       
      	if (!$db->query()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		  } 
          
       return $db->loadResult(); 
       
	   }
       
 protected function addToolBar() 
	{
             
     JToolBarHelper::title( JText::_( 'COM_ACTIVEHELPER_LIVEHELP_CLIENT_INFO' ));                  		
     JToolBarHelper::cancel('cancel', 'Close','JTOOLBAR_CLOSE' );
	}       

}