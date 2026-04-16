<?php

/**
 * @Product     ActiveHelper LiveHelp component for Joomla
 * @package		Agents
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
jimport('joomla.error.log');

/**
 * Domains Controller
 */
class activehelper_livehelpControllerAgents  extends JControllerAdmin
{
	/**
	 * Proxy for getModel.
	 * @since	1.6
	 */
          
     protected	$option 		= 'com_activehelper_livehelp';
     	
	public function &getModel($name = 'Agents', $prefix = 'activehelper_livehelpModel')
	{    
        $model = parent::getModel($name, $prefix, array('ignore_request' => true));
		return $model;
	}
    
    
 function delete()
	{	   

        $jinput = JFactory::getApplication()->input;
		$cid    = $jinput->get('cid');

        $model = $this->getModel( 'Agents' );
                     
         
		foreach ($cid as $id) {
			$id = (int) $id;                      
       
          $model->remove($id);
       
          if ($model->remove($id)) {              
              $msg = JText::_( 'Agent Removed' );              
          
             } 
           }

		if (count($cid) > 1) {
			$s = 's';
	   	}
              
       $this->setRedirect('index.php?option=com_activehelper_livehelp&view=agents', 'Agent' . $s . ' deleted.');
            
	}        
}
