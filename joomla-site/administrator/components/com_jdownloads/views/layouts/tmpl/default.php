<?php
defined('_JEXEC') or die('Restricted access');

global $jlistConfig, $jversion;    
    
    jimport( 'joomla.html.html.tabs' );
    
    JHtml::_('behavior.tooltip');
    JHtml::_('jquery.framework');

    if (version_compare( $jversion->RELEASE,  '3.4', 'ge' ) == FALSE ) {
        // is not 3.4 or newer - so we must use mootols
        JHTML::_('behavior.formvalidation'); 
    } else {
        JHtml::_('behavior.formvalidator'); //Joomla >= 3.4 jQuery
    }     

    ?>
    
<form action="index.php" method="post" name="adminForm" id="adminForm">
    
    <?php if (!empty( $this->sidebar)) : ?>
        <div id="j-sidebar-container" class="span2">
            <?php echo $this->sidebar; ?>
        </div>
        <div id="j-main-container" class="span10">
    <?php else : ?>
        <div id="j-main-container">
    <?php endif;?>    
        
    <div id="j-main-container" class="span10">
        <div class="adminform">
            <div class="span7">
                <div id="cpanel">          
                    <?php
                        $option = 'com_jdownloads';
                        $link = 'index.php?option='.$option.'&amp;view=templates&amp;type=1';
                                jdownloadsViewlayouts::quickiconButton( $link, 'layouts48.png', JText::_( 'COM_JDOWNLOADS_BACKEND_TEMP_TYP1' ) );
                        
                        $link = 'index.php?option='.$option.'&amp;view=templates&amp;type=4';
                                jdownloadsViewlayouts::quickiconButton( $link, 'layouts48.png', JText::_( 'COM_JDOWNLOADS_BACKEND_TEMP_TYP4' ) );

                        $link = 'index.php?option='.$option.'&amp;view=templates&amp;type=2';
                                jdownloadsViewlayouts::quickiconButton( $link, 'layouts48.png', JText::_( 'COM_JDOWNLOADS_BACKEND_TEMP_TYP2' ) );
                                
                        $link = 'index.php?option='.$option.'&amp;view=templates&amp;type=5';
                                jdownloadsViewlayouts::quickiconButton( $link, 'layouts48.png', JText::_( 'COM_JDOWNLOADS_BACKEND_TEMP_TYP5' ) );                        

                        $link = 'index.php?option='.$option.'&amp;view=templates&amp;type=3';
                                jdownloadsViewlayouts::quickiconButton( $link, 'layouts48.png', JText::_( 'COM_JDOWNLOADS_BACKEND_TEMP_TYP3' ) );
                                
                        $link = 'index.php?option='.$option.'&amp;view=templates&amp;type=7';
                                jdownloadsViewlayouts::quickiconButton( $link, 'layouts48.png', JText::_( 'COM_JDOWNLOADS_BACKEND_TEMP_TYP7' ) );                        

                        $link = 'index.php?option='.$option.'&amp;view=cssedit';
                                jdownloadsViewlayouts::quickiconButton( $link, 'css.png', JText::_( 'COM_JDOWNLOADS_BACKEND_EDIT_CSS_TITLE' ) );                        
                                                        
                        $link = 'index.php?option='.$option.'&amp;view=languageedit';
                                jdownloadsViewlayouts::quickiconButton( $link, 'langmanager.png', JText::_( 'COM_JDOWNLOADS_BACKEND_EDIT_LANG_TITLE' ) );
                    ?>
        
                    </div>
                <div style="clear:both">&nbsp;</div>
            </div>
        <div class="span5"> 
            <div class="well">
				<div style="margin:0px;">
					<?php echo JHtml::_('tabs.start', 'jdlayout-sliders-layouts', array('useCookie' => true)); ?> 
					<?php echo JHtml::_('tabs.panel', JText::_('COM_JDOWNLOADS_BACKEND_TEMPPANEL_TABTEXT_INFO'),'layouts_note'); ?>
					<?php echo  JText::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_HEAD'); ?>
					<?php echo  JText::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_HEAD_INFO').JText::_('COM_JDOWNLOADS_BACKEND_SETTINGS_TEMPLATES_HEAD_INFO2'); ?>
				</div>
                <?php echo JHtml::_('tabs.end'); ?>
			</div>
        </div>
     </div>
     </div>

     <input type="hidden" name="option" value="com_jdownloads" />
     <input type="hidden" name="task" value="" />
     <input type="hidden" name="boxchecked" value="0" />
     <input type="hidden" name="controller" value="layouts" />
     </form>    
