<?php

/**
 * @Product     ActiveHelper LiveHelp component for Joomla
 * @package		domains
 * @subpackage	Controller
 * @copyright	(C) 2010- 2017 ActiveHelper Inc.
 * @author		ActiveHelper Inc.
 * @version     5.0
 * @Joomla      3.7
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html
*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla controlleradmin library
jimport('joomla.application.component.controlleradmin');
jimport('joomla.log.log');

/**
 * Domains Controller
 */
class activehelper_livehelpControllerDomains  extends JControllerAdmin
{
	/**
	 * Proxy for getModel.
	 * @since	1.6
	 */
     
     protected	$option 		= 'com_activehelper_livehelp';
     	
	public function &getModel($name = 'Domain', $prefix = 'activehelper_livehelpModel')
	{ 
         
        $model = parent::getModel($name, $prefix, array('ignore_request' => true));
		return $model;
	}
    
    
 function delete()
	{	                        
		 
		$jinput = JFactory::getApplication()->input;
		$cid    = $jinput->get('cid');
       
        $model = $this->getModel( 'Domains' );

		foreach ($cid as $id) {
			$id = (int) $id;                      
      	
          if ($model->remove($id)) {              
              $msg = JText::_( 'Domain Removed' );              
          
             } 
           }

		if (count($cid) > 1) {
			$s = 's';
	   	}
        
        $this->setRedirect('index.php?option=com_activehelper_livehelp&view=domains', 'Domain' . $s . ' deleted.');
            
	}     
    
  function removerestriction()
	{	                        
	
	    $$jinput = JFactory::getApplication()->input;
		$cid = $$jinput->input->get('cid', array(0));	
		     	
        $model = $this->getModel( 'Domains' );

		foreach ($cid as $id) {
			$id = (int) $id;                      
      	
          if ($model->delete_restriction($id)) {              
              $msg = JText::_( 'Restriction Removed' );              
          
             } 
           }

		if (count($cid) > 1) {
			$s = 's';
	   	}
        
        $this->setRedirect('index.php?option=com_activehelper_livehelp&view=restrictions', 'Restriction ' . $s . ' deleted.');
            
	}              
}
