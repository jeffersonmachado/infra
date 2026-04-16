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

<div class="blogfactory-portlet <?php echo $this->minimized ? 'portlet-minimized' : ''; ?>"
     id="portlet-<?php echo $this->portlet; ?>">
    <div class="blogfactory-portlet-toggle-handle">
        <i class="factory-icon-chevron"></i>
        <i class="factory-icon-chevron-expand"></i>
    </div>

    <div class="blogfactory-portlet-header">
        <span><?php echo BlogFactoryText::_('dashboard_portlet_heading_' . $this->portlet); ?></span>
    </div>

    <div class="blogfactory-portlet-content">
        <?php echo $this->loadTemplate('portlet_' . $this->portlet); ?>
    </div>
</div>
