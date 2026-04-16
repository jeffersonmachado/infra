<?php

/**
-------------------------------------------------------------------------
blogfactory - Blog Factory 4.3.0
-------------------------------------------------------------------------
 * @author thePHPfactory
 * @copyright Copyright (C) 2011 SKEPSIS Consult SRL. All Rights Reserved.
 * @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * Websites: http://www.thePHPfactory.com
 * Technical Support: Forum - http://www.thePHPfactory.com/forum/
-------------------------------------------------------------------------
*/

defined('_JEXEC') or die;

?>

<div class="panel panel-default" id="panel-<?php echo $this->panel; ?>">
    <div class="panel-heading">
        <a href="#" class="fa fa-chevron-down muted" style="float: right; padding: 3px; text-decoration: none;"></a>
        <h3 class="panel-title"><span
                class="fa fa-ellipsis-v muted"></span><?php echo BlogFactoryText::_('dashboard_panel_' . $this->panel); ?>
        </h3>
    </div>

    <div class="panel-body" style="<?php echo $this->state ? '' : 'display: none;'; ?>">
        <?php echo $this->loadTemplate('panel_' . $this->panel); ?>
    </div>
</div>
