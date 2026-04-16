<?php

/**
 * @Product     ActiveHelper LiveHelp component for Joomla
 * @package		ExportToCSV
 * @subpackage	Lib
 * @copyright	(C) 2010- 2017 ActiveHelper Inc.
 * @author		ActiveHelper Inc.
 * @version     5.0
 * @Joomla      3.7
 * @license   : GNU/GPL http://www.gnu.org/copyleft/gpl.html 
*/

 defined('_JEXEC') or die('Restricted access');

include_once (JPATH_COMPONENT_ADMINISTRATOR .'/language/english.php');

////////////////////////////////////////////////////////////////
// Export table to CSV format
////////////////////////////////////////////////////////////////
function ExportToCSV($sql)
{
        $db = JFactory::getDbo();
        $csv_save = '';

         $db->setQuery($sql);
      
       	if (!$db->query()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		  } 
        
        
        $rows = @$db->loadAssocList();
        if (!empty($rows)) {
                $comma = _ES_CSV_DELIMITER;
                $quote = _ES_CSV_QUOTE;
                $CR = "\r\n";

                // Make csv rows for field name
                $i=0;
                $fields = $rows[0];
                $cnt_fields = count($fields);
                $csv_fields = '';
                foreach($fields as $name=>$val) {
                        $i++;
                        if ($cnt_fields<=$i) $comma = '';
                        $csv_fields .= $quote.$name.$quote.$comma;
                }
                // Make csv rows for data
                $csv_values = '';


                foreach($rows as $row) {
                        $i=0;
                        $comma = _ES_CSV_DELIMITER;
                        foreach($row as $name=>$val) {
                                $i++;
                                if ($cnt_fields<=$i) $comma = '';
                                $csv_values .= $quote.$val.$quote.$comma;
                        }
                        $csv_values .= $CR;
                }
                $csv_save = $csv_fields.$CR.$csv_values;
        }
        return $csv_save;
}
