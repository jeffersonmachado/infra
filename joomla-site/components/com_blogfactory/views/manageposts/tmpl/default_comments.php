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

if ($this->item->comments): ?>
    <a href="<?php echo BlogFactoryRoute::view('managecomments&post=' . $this->item->id); ?>"><i
            class="factory-icon-balloon"></i><?php echo $this->item->comments; ?></a>
<?php else: ?>
    <span class="muted">&mdash;</span>
<?php endif; ?>
