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
 
defined('_JEXEC') or die('Restricted access');

?>
<script type="text/javascript">
    Joomla.submitbutton = function(task) {
        var form = document.getElementById('adminForm');
        var images = form.images.value.toUpperCase();
        var files  = form.files.value.toUpperCase();
        var tables = form.tables.value.toUpperCase();
        
        if (task == 'uninstall.cancel'){
            Joomla.submitform(task);
        } else {
            if (images == 'YES' || files == 'YES' || tables == 'YES'){
                var answer = confirm("<?php echo JText::_('COM_JDOWNLOADS_RESTORE_RUN_FINAL'); ?>")
                if (answer){
                    Joomla.submitform(task);
                }    
            } else {
                Joomla.submitform(task);
            }
        }
    }
</script>  

<form action="<?php echo JRoute::_('index.php?option=com_jdownloads');?>" method="post" name="adminForm" id="adminForm">
   
    <?php if (!empty( $this->sidebar)) : ?>
        <div id="j-sidebar-container" class="span2">
            <?php echo $this->sidebar; ?>
        </div>
        <div id="j-main-container" class="span10">
    <?php else : ?>
        <div id="j-main-container">
    <?php endif;?>   
    
    <div>
        <fieldset style="background-color: #ffffff;" class="uploadform">
            <legend><?php echo JText::_('COM_JDOWNLOADS_UNINSTALL_OPTIONS_LABEL'); ?></legend>
  
            <div class="jdwarning"><?php echo JText::_('COM_JDOWNLOADS_UNINSTALL_WARNING'); ?>
            </div>                
            
            
            <div style="margin:10px;"><?php echo JText::_('COM_JDOWNLOADS_UNINSTALL_OPTIONS_IMAGES'); ?></div>
            <div><input style="margin:10px;" class="input_box" type=text" name="images" value="No" size="5" maxlength="3" /></div>
            <div style="margin:10px;"><?php echo JText::_('COM_JDOWNLOADS_UNINSTALL_OPTIONS_FILES'); ?></div>
            <input style="margin:10px;" class="input_box" type=text" name="files" value="No" size="5" maxlength="3" /><br>
            <div style="margin:10px;"><?php echo JText::_('COM_JDOWNLOADS_UNINSTALL_OPTIONS_TABLES'); ?></div>
            <input style="margin:10px;" class="input_box" type=text" name="tables" value="No" size="5" maxlength="3" /><br>
            
            <input style="margin:10px;" class="button" type="button" value="<?php echo JText::_('COM_JDOWNLOADS_UNINSTALL_RUN').'&nbsp; '; ?>" onclick="Joomla.submitbutton('uninstall.rununinstall')" />
            <input style="margin:10px;" class="button" type="button" value="<?php echo JText::_('COM_JDOWNLOADS_UNINSTALL_CANCEL').'&nbsp; '; ?>" onclick="Joomla.submitbutton('uninstall.cancel')" />
              
        </fieldset>
    </div>
  
    <input type="hidden" name="boxchecked" value="0" />
    <input type="hidden" name="option" value="com_jdownloads" />
    <input type="hidden" name="task" value="uninstall.rununinstall" />
    <input type="hidden" name="view" value="uninstall" />
    <input type="hidden" name="hidemainmenu" value="0" />
    
    <?php echo JHtml::_('form.token'); ?>
   </form>
