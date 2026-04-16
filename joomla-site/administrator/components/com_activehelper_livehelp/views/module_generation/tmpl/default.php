<?php   

/**
 * @package   ActiveHelper_LiveHelp
 * @contact   www.activehelper.com, support@activehelper.com
 * @copyright 2010 - 2017 by ActiveHelper Inc. All rights reserved
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

 
defined( '_JEXEC' ) or die( 'Restricted access' );
include_once (JPATH_COMPONENT_ADMINISTRATOR .'/language/english.php');
?>
<form action="index.php" method="post" name="adminForm" id="adminForm">
  <fieldset class="adminform">
    <legend><?php echo JText::_('Module Settings') ?></legend>
    <table class="admintable">
     <tr>
      <td width="200" class="key> <label for="domain id"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_DOMAIN_ID') ?></td>
      <td>
        <input class="text_area" type="text" name="Domain_id" id="Domain_id" size="10" maxlength="10" readonly="true" value="<?php echo $this->domain_id;?>" />
      </td>
    </tr>
    <tr>
      <td width="200" class="key> <label for="languages"> <?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_JOOMLA') ?></td>
       <td>     
         <?php echo $this->j_ver; ?>   
      </td>   
    </tr>
    <tr>
      <td> <a target="_blank" href="<?php echo _EXT_LINK_FOOTER_FAQ; ?>"><?php echo JText::_('COM_ACTIVEHELPER_LIVEHELP_MOD_FOOTER_FAQ') ?></a></td>            
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