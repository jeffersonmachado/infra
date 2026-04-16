<?php

/**
 * @package   ActiveHelper_LiveHelp
 * @contact   www.activehelper.com, support@activehelper.com
 * @copyright 2010 - 2017 by ActiveHelper Inc. All rights reserved
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

 
 
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.html.editor' );	
JHTML::_('behavior.calendar');

?>
<form action="<?php echo JRoute::_('index.php?option=com_activehelper_livehelp&view=languages'); ?>" method="post" name="adminForm" id="adminForm">
  <fieldset class="adminform">  
 	<table class="admintable">
	   <tr>    		  
		   <td width="200" class="key> <label for="file_name"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_SERVER_LANGUAGES_FILE'); ?></label></td>
           <td> <input class="text_area" type="text" name="file_name" id="file_name" size="100" maxlength="100" value="<?php echo $this->file_name;?>"  readonly  /></td>	  
       </tr>	
		  <br>
	     <tr>
		  <td width="200" class="key> <label for="file_path"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_SERVER_LANGUAGES_FILE_PATH'); ?></label></td>
           <td> <input class="text_area" type="text" name="file_path" id="file_path" size="100" maxlength="100" value="<?php echo $this->file_path;?>"  readonly  /></td>	  		   
        </tr>	
    <br> 
    <tr>
      <td width="120" align="right" class="key">
       Language Translation  :  
      </td>
      <td>
        <?php
        echo $this->editor_script->display( 'lang_text',  $this->language_file ,'100%', '600', '100', '70' ) ;
        ?>
      </td>
    </tr>	
 </table> 	
 </fieldset>  
  <?php echo JHTML::_( 'form.token' ); ?>
  <input type="hidden" name="task" value="" />
</form>
