<?php

/**
 * @Product     ActiveHelper LiveHelp component for Joomla
 * @package		CSV
 * @subpackage	Viewes
 * @copyright	(C) 2010- 2017 ActiveHelper Inc.
 * @author		ActiveHelper Inc.
 * @version     5.0
 * @Joomla      3.7
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.application.component.view');
jimport('joomla.log.log');

include_once (JPATH_COMPONENT_ADMINISTRATOR .'/lib/csvcreation.php');

class activehelper_livehelpViewCSV extends JViewLegacy
{
	function display($tpl = null)
	{

	
	     
	     $jinput    = JFactory::getApplication();
         $filename  = $jinput->input->get("reportname");   
         $sql       = $jinput->input->getString('SQL'); 


        ob_end_clean();

        $file_name = 'export_'.$filename.'.csv';
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Accept-Ranges: bytes');
        header('Content-Disposition: attachment; filename='.basename($file_name).';');
        header('Content-Type: text/plain; '._ISO);
        header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Pragma: no-cache');
       
        echo ExportToCSV($sql);

        die(); // no need to send anything else

        parent::display($tpl);

	}

}