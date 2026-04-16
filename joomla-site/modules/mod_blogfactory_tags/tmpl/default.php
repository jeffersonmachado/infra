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

<div class="mod_blogfactory_tags<?php echo $moduleclass_sfx; ?>">
  <?php foreach ($tags as $tag): ?>
    <a href="<?php echo JRoute::_('index.php?option=com_blogfactory&view=posts&tag=' . $tag->alias); ?>" style="font-size: <?php echo $tag->size; ?>px;"><?php echo $tag->name; ?></a>
  <?php endforeach; ?>
</div>
