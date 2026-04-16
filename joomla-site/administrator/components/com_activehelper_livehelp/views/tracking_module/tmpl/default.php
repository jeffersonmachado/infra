<?php   

/**
 * @package   ActiveHelper_LiveHelp
 * @contact   www.activehelper.com, support@activehelper.com
 * @copyright 2010 - 2017 by ActiveHelper Inc. All rights reserved
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

 
defined( '_JEXEC' ) or die( 'Restricted access' );

?>
<form action="index.php" method="post" name="adminForm" id="adminForm">
  <fieldset class="adminform">
    <legend><?php echo JText::_('Script Parameters') ?></legend>
    <table class="admintable">
     <tr>
      <td width="200" class="key> <label for="domain id"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_DOMAIN_ID') ?></td>
      <td>
        <input class="text_area" type="text" name="Domain_id" id="Domain_id" size="10" maxlength="10" readonly="true" value="<?php echo $this->domain_id;?>" />
      </td>
    </tr>
    <tr>
      <td width="200" class="key> <label for="languages"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_LANGUAGES') ?></td>
       <td>     
         <?php echo $this->languajes; ?>   
      </td>   
    </tr>
    <tr>
      <td width="200" class="key> <label for="agent status"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_STATUS_TYPE') ?></td>
      <td>
        <?php echo $this->status_type; ?>  
      </td>
    </tr>
    <tr>
      <td width="200" class="key> <label for="agents"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_STATS_TOTAL_AGENTS') ?></td>        
      <td>
        <?php echo $this->agents; ?>
      </td>
    </tr>
      <tr>
      <td width="200" class="key> <label for="tracking"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_TRACKING') ?></td>
      <td>
        <?php echo $this->tracking; ?>  
      </td>
    </tr>
      <tr>
      <td width="200" class="key> <label for="status"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_STATUS_IND') ?></td>
      <td>
         <?php echo $this->status_indicator; ?>  
      </td>
    </tr> 
	 <tr>
      <td width="200" class="key> <label for="textdir"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_TEXTDIR') ?></td>
      <td>
         <?php echo $this->textdir; ?>  
      </td>
    </tr> 

     <tr>
      <td width="200" class="key> <label for="footer"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_FOOTER') ?></td>
      <td>
         <?php echo $this->footer; ?>  
      </td>
    </tr>     
    </table>
  </fieldset>
  <?php echo JHTML::_( 'form.token' ); ?>
  <input type="hidden" name="option" value="com_activehelper_livehelp" />  
  <input type="hidden" name="task" value="" />
  <input type="hidden" name="boxchecked" value="<?php echo $this->domain_id; ?>" />
  <input type="hidden" name="controller" value="domain" />   
    <input type="hidden" name="view" value="domains" />   
</form>