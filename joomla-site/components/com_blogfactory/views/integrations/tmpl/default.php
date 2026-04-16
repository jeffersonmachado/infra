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

<div class="blogfactory-view">
    <h2><?php echo BlogFactoryText::_($this->getName() . '_page_heading'); ?></h2>

    <div class="tabbable tabs-left">
        <ul class="nav nav-tabs">
            <?php foreach ($this->extensions as $element => $title): ?>
                <li class="<?php echo $this->extension == $element ? 'active' : ''; ?>"><a
                        href="<?php echo BlogFactoryRoute::view('integrations&tmpl=component&extension=' . $element); ?>"><?php echo $title; ?></a>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="tab-content" style="margin: 10px;">
            <?php echo $this->loadTemplate('items'); ?>
        </div>
    </div>

    <div class="pagination">
        <?php echo $this->pagination->getPagesLinks(); ?>
    </div>

</div>
