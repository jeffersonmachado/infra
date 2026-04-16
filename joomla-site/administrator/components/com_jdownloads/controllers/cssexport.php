<?php
/*
 * @package Joomla
 * @copyright Copyright (C) 2005 Open Source Matters. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 *
 * @component jDownloads
 * @version 2.0  
 * @copyright (C) 2007 - 2011 - Arno Betz - www.jdownloads.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 * 
 * jDownloads is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.application.component.controller');

/**
 * jDownloads cssexport Controller
 *
 */
class jdownloadsControllerCssexport extends jdownloadsController
{
    /**
     * Constructor
     */
    function __construct()
    {
        parent::__construct();
    }
    
	/**
	 * logic to send the selected css file to the clients browser
     * 
	 */
	public function export()
    {
        global $jlistConfig;
        
        // Check for request forgeries.
        JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

        // Access check.
        if (!JFactory::getUser()->authorise('edit.config','com_jdownloads')){            
            JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
            $app->redirect(JRoute::_('index.php?option=com_jdownloads&view=layouts', false));
            
        } else {       
        
            jimport('joomla.filesystem.file');
            
            $app = JFactory::getApplication();
            $db = JFactory::getDBO();
            $jinput = JFactory::getApplication()->input;
            
            ini_set('max_execution_time', '300');
            
            $file = $jinput->get('filename', '', 'string');
            $source_path = JPATH_COMPONENT_SITE.'/assets/css/'.$file;
            $ss = is_file($source_path);
            $len = filesize($source_path);
            $file_extension = jFile::getExt($file);
            $ctype = 'text/css';
            
            if ($file && jFile::exists($source_path)){
                // send the file

                ob_end_clean();

                // needed for MS IE - otherwise content disposition is not used?
                if (ini_get('zlib.output_compression')){
                    ini_set('zlib.output_compression', 'Off');
                }
                
                header("Cache-Control: public, must-revalidate");
                header('Cache-Control: pre-check=0, post-check=0, max-age=0');
                header("Expires: 0"); 
                header("Content-Description: File Transfer");
                header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
                header("Content-Type: " . $ctype);
                header("Content-Length: ".(string)$len);
                header('Content-Disposition: attachment; filename="'.$file.'"');
                header("Content-Transfer-Encoding: binary\n");

                if (!ini_get('safe_mode')){ 
                    @set_time_limit(0);
                }

                @readfile($source_path);                
                exit;
            } else {
                // file not found                    
                $app->redirect(JRoute::_('index.php?option=com_jdownloads&view=layouts', false),  JText::_('COM_JDOWNLOADS_CSS_EXPORT_ERROR'), 'error');
            }
        }
        exit;
    }
}
?>