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

use Joomla\Utilities\ArrayHelper;

jimport('joomla.application.component.controller');

/**
 * jDownloads cssinstall Controller
 *
 */
class jdownloadsControllerCssimport extends jdownloadsController
{
    /**
     * Constructor
     */
    function __construct()
    {
        parent::__construct();
    }
    
	/**
	 * logic to store the new css file on the server but make also a backup from the old css file
     * 
	 */
	public function import()
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
            
            ini_set('max_execution_time', '300');
            ignore_user_abort(true);
            flush(); 
            
            $target_upload_dir = JPATH_COMPONENT_SITE.'/assets/css/';
            $file_names = array('jdownloads_custom.css', 'jdownloads_buttons.css', 'jdownloads_fe.css');
            $rename_error = false;
            
            // get css file
            $file = ArrayHelper::getValue($_FILES,'install_file',array('tmp_name'=>''));
            
            // when file is not valid exit
            if (!$file['type'] == 'text/css' || (!in_array($file['name'], $file_names))){
                $app->redirect(JRoute::_('index.php?option=com_jdownloads&view=layouts', false),  JText::_('COM_JDOWNLOADS_CSS_IMPORT_MSG_WRONG_FILE_ERROR'), 'error');
            }
            
            // make at first a backup from the old css file
            if (JFile::exists($target_upload_dir.$file['name'])){
                $x = 1;
                // get the next free number
                while (JFile::exists($target_upload_dir.$file['name'].'.backup.'.$x)){
                    $x++;
                    if ($x == 500){
                        $rename_error = true;
                        continue;
                    }
                } 
                if (!$rename_error){
                    if (!JFile::move($target_upload_dir.$file['name'], $target_upload_dir.$file['name'].'.backup.'.$x)){
                        $rename_error = true;
                    }    
                }
            }
            
            if (!$rename_error){
               // all is correct so we can now move the file
                if (!move_uploaded_file($file['tmp_name'], $target_upload_dir.$file['name'])){
                        $rename_error = true;
                } else {
                    // succesful
                    $app->redirect(JRoute::_('index.php?option=com_jdownloads&view=layouts', false), JText::_('COM_JDOWNLOADS_CSS_IMPORT_MSG_SUCCESSFUL') );
                }
            } else {
                // not succesful
                $app->redirect(JRoute::_('index.php?option=com_jdownloads&view=layouts', false), JText::_('COM_JDOWNLOADS_CSS_IMPORT_MSG_STORE_ERROR'), 'error');
            }
        }
    }
}
?>