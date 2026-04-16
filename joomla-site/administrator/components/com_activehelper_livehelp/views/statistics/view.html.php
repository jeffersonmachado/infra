<?php

/**
 * @Product     ActiveHelper LiveHelp component for Joomla
 * @package		statistics 
 * @subpackage	View
 * @copyright	(C) 2010- 2017 ActiveHelper Inc.
 * @author		ActiveHelper Inc.
 * @version     5.0
 * @Joomla      3.7
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html
*/


defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.application.component.view');


class activehelper_livehelpViewstatistics extends JViewLegacy 
{
	function display($tpl = null)
	{
		
	$model = $this->getModel();
	
	$rowsdomains         = $model->most_active_domains_statistic();
    $rowsagents          = $model->most_active_agents_statistic();
    $rowsagents_rating   = $model->avg_agents_statistic();
	
	$this->assignRef('rowsdomains', $rowsdomains);
    $this->assignRef('rowsagents', $rowsagents);
    $this->assignRef('rowsagents_rating', $rowsagents_rating);
    
	// Set the toolbar
	$this->addToolBar();
	
    parent::display($tpl);
        
	}
	
 protected function addToolBar() 
	{
		JToolBarHelper::title(JText::_('COM_ACTIVEHELPER_LIVEHELP_REPORTS'));               

	}
}


