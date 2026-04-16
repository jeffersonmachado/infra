<?php

/**
 * @Product     ActiveHelper LiveHelp component for Joomla
 * @package		script file
 * @subpackage	Installer
 * @copyright	(C) 2010- 2017 ActiveHelper Inc.
 * @author		ActiveHelper Inc.
 * @version     5.0
 * @Joomla      3.7
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
 
 // No Permission
defined('_JEXEC') or die ('Restricted access');

jimport('joomla.installer.installer');
jimport('joomla.filesystem.archive');
jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');
jimport('joomla.log.log');

class com_activehelper_livehelpInstallerScript {
    
    private $_current_version = null;
    private $_is_new_installation = true;
    private $_unzip = false;  
    public function preflight($type, $parent) {
                                               
   }
  
  
/***********************************************************************************************
* ---------------------------------------------------------------------------------------------
* LIVEHELP SERVER CORE FOLDERS CREATION 
* ---------------------------------------------------------------------------------------------
***********************************************************************************************/  
  
  function install($parent) 
   {    
       
        $db =  JFactory::getDBO();
        $src = $parent->getParent()->getPath('source');        
        $uri = JURI::getInstance();     

        $status = new JObject();     
            
        $CR = "\r\n";
     
     $path = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_activehelper_livehelp'. DIRECTORY_SEPARATOR . 'server';
	 $folder_import ='import';
	 $folder_uploads ='uploads';
	 
     $mode = (int)0755;
	 
     JFolder::create($path , $mode);
	 JFolder::create($path . DIRECTORY_SEPARATOR . $folder_import  , $mode);
	 JFolder::create($path . DIRECTORY_SEPARATOR . $folder_uploads , $mode);
                                                          	           
/***********************************************************************************************
* ---------------------------------------------------------------------------------------------
* SETTINGS FILE
* ---------------------------------------------------------------------------------------------
***********************************************************************************************/

    $domain_path = JPATH_ROOT . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR .'com_activehelper_livehelp'. DIRECTORY_SEPARATOR .'server' . DIRECTORY_SEPARATOR .'domains';
    $j_path      =  JPATH_ROOT;

    if ($uri->isSSL() == true)
     {$protocole = 'https://';
      $ssl =1; }
    else
      {$protocole = 'http://';
       $ssl =0; }


      $host        =  $uri->getHost();
      $j_dir       =  JURI::root(true) . '/' . 'components/com_activehelper_livehelp';

      $path_conf = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_activehelper_livehelp'. DIRECTORY_SEPARATOR . 'server'. DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . 'jlhconst.php' ;

        $jlhconst_file =  "<?php ".$CR.
		
		     ' $protocol = isset($_SERVER['. "'" .HTTPS. "'".']) ? '.  "'" .https. "'".' : '. "'" .http. "'".';'.$CR.  
             ' $protocol = '. "'" .http. "'".';'.$CR. 			             
             ' $ssl = 0;'.$CR.
             ' '.$CR.
             '  if ( isset( $_SERVER['. "'" .HTTPS. "'".'] ) && strtolower( $_SERVER['. "'" .HTTPS. "'".'] ) == '. "'" .on."'".' ) { '.$CR.
             '     $protocol = '."'" .https."'".';'.$CR.
             '     $ssl = 1;'.$CR.
             '      }'.$CR.
             ' '.$CR.
             ' '.$CR.
             ' '.$CR.
             ' define("J_HOST",' . '$protocol'. '.' . "'://'" . '.' . "'" . $host . "');".$CR.
             ' define("J_DOMAIN_SET_PATH",' . "'" . $domain_path . "');".$CR.
             ' define("J_DIR_PATH",' . "'" . $j_dir . "');".$CR.
             ' define("J_CONF_PATH",' . "'" . $j_path . "');".$CR.
             ' define("J_CONF_SSL",'. '$ssl' .  ");".$CR.
             ' '.$CR.
             ' '.$CR.
             "?>" ;

    JFile::write ($path_conf,$jlhconst_file);
  
        
         	           
/***********************************************************************************************
* ---------------------------------------------------------------------------------------------
* Database File
* ---------------------------------------------------------------------------------------------
***********************************************************************************************/
          $path_db = JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_activehelper_livehelp'. DIRECTORY_SEPARATOR . 'server'. DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . 'config_database.php' ;

            $config_db_file =  "<?php ".$CR.
		
		     " if (!defined('__CONFIG_DATABASE_INC')) {".$CR.  
			 ' '.$CR.
             " define('__CONFIG_DATABASE_INC', 1); ".$CR. 			             
			 ' '.$CR.
             " define( 'DIRECTORY_SEPARATOR', DIRECTORY_SEPARATOR );".$CR.
			 ' '.$CR.
             " include_once('constants.php'); ".$CR.
			 " include_once('jlhconst.php'); ".$CR.
			 ' '.$CR.
			 " require_once J_CONF_PATH . DIRECTORY_SEPARATOR .'configuration.php'; ".$CR.
			 ' '.$CR.
			 ' $config = new JConfig(); '.$CR.
			 ' '.$CR.
             ' define("DB_HOST", $config->host); '.$CR.
			 ' define("DB_USER", $config->user); '.$CR.
			 ' define("DB_PASS", $config->password); '.$CR.
			 ' define("DB_NAME", $config->db); '.$CR.
			 ' '.$CR.
             ' $table_prefix = $config->dbprefix . ' . "'livehelp_'; ".$CR.
             '  } '.$CR.
             "?> ";
			 
                JFile::write ($path_db,$config_db_file);
	                  
	            $this->_installationOutput($status);
        }	
		 
/***********************************************************************************************
* ---------------------------------------------------------------------------------------------
* UNINSTALL MODULES SECTION
* ---------------------------------------------------------------------------------------------
***********************************************************************************************/
   
 function uninstall($parent) 
    {
       $db = JFactory::getDBO();
       $status = new JObject();


        $db->setQuery("SELECT extension_id FROM #__extensions WHERE type = 'module' AND element = 'mod_activehelper_livehelp' LIMIT 1");
        $id = $db->loadResult();
        if ($id) {
          $installer = new JInstaller();
          $installer->uninstall('module', $id);
        }
		
        $this->_uninstallationOutput($status);
    
    } 
    
 protected function _installationOutput($status) {
 	  
    $sbinsOK="Installed";

?>
<img src="components/com_activehelper_livehelp/images/logo.png" alt="Activehelper LiveHelp" style="width:72px; height:72px; float: left; padding-right:15px;" />

<h2>ActiveHelper LiveHelp Server Installation</h2>
<h2><a href="index.php?option=com_activehelper_livehelp">Go to Dashboard</a></h2>
<table class="adminlist table table-striped">
	<thead>
		<tr>
			<th class="title" colspan="2"><?php echo JText::_('Extension'); ?></th>
			<th width="30%"><?php echo JText::_('Status'); ?></th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="3"></td>
		</tr>
	</tfoot>
	<tbody>
		<tr>
			<th colspan="3"><?php echo JText::_('Core'); ?></th>
		</tr>	
          <tr class="row0">
			<td class="key" colspan="2"><?php echo 'ActiveHelper LiveHelp Server ' . JText::_('Core'); ?></td>
			<td><strong><?php echo JText::_('Created Folder'); ?></strong></td>
		</tr>	
		<tr class="row1">
			<td class="key" colspan="2"><?php echo 'ActiveHelper Dashboard  '.JText::_('Component'); ?></td>
			<td><strong><?php echo JText::_('Installed'); ?></strong></td>
		</tr>	
        <tr>
			<th colspan="3"><?php echo JText::_('Configuration'); ?></th>
		</tr>
		<tr class="row0">
			<td class="key" colspan="2"><?php echo 'ActiveHelper Main Configuration File'; ?></td>
			<td><strong><?php echo JText::_('Installed'); ?></strong></td>
		</tr>
        <tr class="row1">
			<td class="key" colspan="2"><?php echo 'ActiveHelper - Default Domain Settings'; ?></td>
			<td><strong><?php echo JText::_('Installed'); ?></strong></td>
		</tr>	
		
		<th colspan="3"><?php echo JText::_('Guides and Resources'); ?></th>
		
         <tr class="row1">
			<td class="Docs" colspan="2"><?php echo 'Documenation : Live Chat getting started guide.'; ?></td>			
			<td><a target="_blank" href="http://www.activehelper.com/livechat/guides/joomla-live-chat-getting-started.html">Guide</a></td>
		</tr>	
		<tr class="row1">
			<td class="Docs" colspan="2"><?php echo 'Guides : Live Chat videos guides.'; ?></td>			
			<td><a target="_blank" href="http://www.activehelper.com/livechat/support/guides.html">Guide</a></td>
		</tr>
		<tr class="row1">
			<td class="Docs" colspan="2"><?php echo 'Technical Forum : Ask questions related to the Live Chat component.'; ?></td>			
			<td><a target="_blank" href="http://www.activehelper.com/support/live-chat-tech-support-forum.html">Forum</a></td>
		</tr>
		<tr class="row1">
			<td class="Docs" colspan="2"><?php echo 'Agent App : You will find the link to the desktop and mobile agent application.'; ?></td>			
			<td><a target="_blank" href="http://www.activehelper.com/livechat/downloads.html">Apps</a></td>
		</tr>	
        <tr class="row1">
			<td class="Docs" colspan="2"><?php echo 'Images : Free Live Chat buttons, invitations and skins.'; ?></td>			
			<td><a target="_blank" href="http://www.activehelper.com/livechat/free-live-chat-buttons-downloads.html">Buttons</a></td>
		</tr>
			
	</tbody>
</table>
	<?php
    }

	private function _uninstallationOutput($status) {
?>
<h2>ActiveHelper LiveHelp Server Removal</h2>
<table class="adminlist table table-striped">
	<thead>
		<tr>
			<th class="title" colspan="2"><?php echo JText::_('Extension'); ?></th>
			<th width="30%"><?php echo JText::_('Status'); ?></th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="3"></td>
		</tr>
	</tfoot>
	<tbody>
		<tr>
			<th colspan="3"><?php echo JText::_('Core'); ?></th>
		</tr>
		<tr class="row0">
			<td class="key" colspan="2"><?php echo 'ActiveHelper LiveHelp Server ' . JText::_('Core'); ?></td>
			<td><strong><?php echo JText::_('Removed'); ?></strong></td>
		</tr>
		<tr class="row1">
			<td class="key" colspan="2"><?php echo 'ActiveHelper Admin Area '.JText::_('Component'); ?></td>
			<td><strong><?php echo JText::_('Removed'); ?></strong></td>
		</tr>		
		<tr>
			<th colspan="3"><?php echo JText::_('Configuration'); ?></th>
		</tr>
		<tr class="row0">
			<td class="key" colspan="2"><?php echo 'ActiveHelper Main Configuration File'; ?></td>
			<td><strong><?php echo JText::_('Removed'); ?></strong></td>
		</tr>
        <tr class="row1">
			<td class="key" colspan="2"><?php echo 'ActiveHelper - Default Domain Settings'; ?></td>
			<td><strong><?php echo JText::_('Removed'); ?></strong></td>
		</tr>		        
	</tbody>
</table>
	<?php
    }   
 }	
?>