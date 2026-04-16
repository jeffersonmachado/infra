<?php

/**
 * @Product     ActiveHelper LiveHelp component for Joomla
 * @package		Read_chat
 * @subpackage	View
 * @copyright	(C) 2010- 2017 ActiveHelper Inc.
 * @author		ActiveHelper Inc.
 * @version     5.0
 * @Joomla      3.7
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html 
*/


defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.application.component.view');

class activehelper_livehelpViewread_chat extends JViewLegacy
{
   var $_query = null;

	function display($tpl = null)
	{
      
	  $jinput = JFactory::getApplication()->input;
      $cid   = $jinput->getVar( 'cid', array(0), '', 'array' );	
				
            
      $model = $this->getModel();
      
      $chat = $model->load_chat($cid);
      $chat_stats = $model->chat_statistic($cid);

        foreach ($chat_stats as $row )
        {
          $agent      = $row["agent"];
          $department = $row["department"];
          $server     = $row["server"];
          $visitor    = $row["username"];
          $email      = $row["email"];
          $date       = $row["date"];
          $rating     = $row["rating"];
          $country    = $row["country"];
          $city       = $row["city"];
          $phone      = $row["phone"];
          $company    = $row["company"];
         }

      $this->assignRef('chat',$chat);

      $this->assignRef('agent',$agent);
      $this->assignRef('department',$department);
      $this->assignRef('server',$server);
      $this->assignRef('visitor',$visitor);
      $this->assignRef('email',$email);
      $this->assignRef('date',$date);
      $this->assignRef('rating',$rating);
      $this->assignRef('country',$country);
      $this->assignRef('city',$city);
      $this->assignRef('phone',$phone);
      $this->assignRef('company',$company);
      
      $this->assign('ExecSQL',  $model->buildSearch());


       // Set the toolbar
       $this->addToolBar($cid);
        
       parent::display($tpl);
	}


 	protected function addToolBar( $id ) 
	{
		JToolBarHelper::title(JText::_('COM_ACTIVEHELPER_LIVEHELP_CHAT_TRANS') . $id );
        JToolBarHelper::custom('reports.tocsv', 'publish.png', 'publish_f2.png',JText::_('COM_ACTIVEHELPER_LIVEHELP_B_TOCSV'), false);
        JToolBarHelper::custom('reports.send_chat', 'publish.png', 'publish_f2.png',JText::_('COM_ACTIVEHELPER_LIVEHELP_SEND_BY_EMAIL'), false);  
        JToolBarHelper::cancel( 'cancel', $alt = 'JTOOLBAR_CLOSE'); 
	}
 }