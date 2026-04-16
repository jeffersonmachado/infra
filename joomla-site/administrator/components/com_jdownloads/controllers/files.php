<?php
/**
 * @package jDownloads
 * @version 2.5  
 * @copyright (C) 2007 - 2013 - Arno Betz - www.jdownloads.com
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
 * Jdownloads Component Jdownloads Controller
 *
 * @package Joomla
 * @subpackage Jdownloads
 */
class jdownloadsControllerFiles extends jdownloadsController
{
	/**
	 * Constructor
	 *
	 */
	function __construct()
	{
		parent::__construct();                            
        
        require_once JPATH_COMPONENT.'/helpers/jdownloadshelper.php';                           

	}
    
    public function getModel($name = 'files', $prefix = 'jdownloadsModel', $config = array('ignore_request' => true))
    {
        $model = parent::getModel($name, $prefix, $config);
        return $model;
    }

    public function delete()
    {
         
         global $jlistConfig;
         jimport('joomla.filesystem.file');
         
         $canDo    = JDownloadsHelper::getActions();
         
         if ($canDo->get('core.delete')) {
         
             $msg = '';
             $deleted = 0;

             $cid    = $this->input->get('cid', array(), 'array');

             if (count($cid)){
                 foreach ($cid as $file){
                     // sanitize the filename
                     $file = JDownloadsHelper::sanitizeUrlParam($file);
                     
                     if (is_file($jlistConfig['files.uploaddir'].DS.$file)){
                         // delete the file
                         if (!JFile::delete($jlistConfig['files.uploaddir'].DS.$file)){
                             // can not delete!
                             $this->setRedirect( 'index.php?option=com_jdownloads&view=files', JText::_('COM_JDOWNLOADS_MANAGE_FILES_DELETE_ERROR'), 'error');
                         } else {    
                             $deleted++;
                         } 
                     }
                 }
                     
                 if ($deleted){
                     // successful!
                     $msg = sprintf(JText::_('COM_JDOWNLOADS_MANAGE_FILES_DELETE_SUCCESS'),$deleted);    
                 }
             }
             
         }    
         // set redirect
         $this->setRedirect( 'index.php?option=com_jdownloads&view=files', $msg );
    }
    
    public function uploads() 
    {
         // set redirect
         $this->setRedirect( 'index.php?option=com_jdownloads&view=uploads');        
    }  
    
                  
    public function downloads() 
    {
         // set redirect
         $this->setRedirect( 'index.php?option=com_jdownloads&view=downloads');        
    }  
    
}
?>