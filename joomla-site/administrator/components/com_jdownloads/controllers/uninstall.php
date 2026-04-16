<?php
/*
 * @package Joomla
 * @copyright Copyright (C) 2005 Open Source Matters. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 *
 * @component jDownloads
 * @version 3.2  
 * @copyright (C) 2007 - 2016 - Arno Betz - www.jdownloads.com
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
 * jDownloads Restore Controller
 *
 */
class jdownloadsControlleruninstall extends jdownloadsController
{
	/**
	 * Constructor
	 *
	 */
	    public function __construct($config = array())
    {
        parent::__construct($config);
       
	}

	/**
	 * logic to use the uninstall options to uninstall jD via Joomla uninstaller
	 *
	 */
	public function rununinstall()
    {
        jimport('joomla.installer.installer');
        $db = JFactory::getDBO();
        $session  = JFactory::getSession();
        $app = JFactory::getApplication();
        $jinput = JFactory::getApplication()->input;
        
        // Check for request forgeries.
        JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

        // Access check.
        if (!JFactory::getUser()->authorise('edit.config','com_jdownloads')){            
            JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
            $this->setRedirect(JRoute::_('index.php?option=com_jdownloads', true));
            
        } else {       
            // 
            $del_images  = $jinput->get('images', 'no', 'STRING');
            $del_files   = $jinput->get('files', 'no', 'STRING');
            $del_tables  = $jinput->get('tables', 'no', 'STRING');
 
            $session->set('del_jd_images', $del_images);
            $session->set('del_jd_files', $del_files);
            $session->set('del_jd_tables', $del_tables);
            
            $db->setQuery('SELECT `extension_id` FROM #__extensions WHERE `element` = "com_jdownloads" AND `type` = "component"');
            $id = $db->loadResult();
            if($id){
                $installer = new JInstaller;
                $result = $installer->uninstall('component',$id,1);
                $result_msg = array('name'=>'jDownloads Component','client'=>'site', 'result'=>$result);
            }
            $msg = $session->get('jd_uninstall_msg');
            if ($msg){
                $this->setRedirect(JRoute::_('index.php?option=com_installer&view=manage', false), $msg);
            } else {  
                $this->setRedirect(JRoute::_('index.php?option=com_installer&view=manage', false));
            }
        }    
    }
    
    /**
     * cancel the uninstall process
     *
     */
    public function cancel()
    {
        $session  = JFactory::getSession();        
        $session->clear('del_jd_images');
        $session->clear('del_jd_files');
        $session->clear('del_jd_tables');
        
        $cancel_msg = JText::_('COM_JDOWNLOADS_UNINSTALL_CANCEL_MSG');
        $this->setRedirect(JRoute::_('index.php?option=com_installer&view=manage', false), $cancel_msg);
    }
}
?>