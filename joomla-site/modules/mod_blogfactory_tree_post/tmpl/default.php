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

<div class="mod_blogfactory_tree_post<?php echo $moduleclass_sfx; ?>">
  <ul id="<?php echo $id; ?>">
    <?php foreach ($items as $year => $months): ?>
      <li>
        <a href="<?php echo JRoute::_('index.php?option=com_blogfactory&view=posts&date=' . $year); ?>">
          <?php if ($no_posts): ?><span class="badge badge-info"><?php echo $months['count']; ?></span><?php endif; ?><?php echo $year; ?>
        </a>

        <ul>
          <?php foreach ($months['months'] as $month => $posts): ?>
            <li>
              <a href="<?php echo JRoute::_('index.php?option=com_blogfactory&view=posts&date=' . $year . '-' . $month); ?>">
                <?php if ($no_posts): ?><span class="badge badge-info"><?php echo $posts['count']; ?></span><?php endif; ?><?php echo JFactory::getDate()->monthToString($month); ?>
              </a>

              <ul>
                <?php foreach ($posts['posts'] as $post): ?>
                  <li><a href="<?php echo $post->link; ?>"><?php echo $post->title; ?></a></li>
                <?php endforeach; ?>
              </ul>
            </li>
          <?php endforeach; ?>
        </ul>
      </li>
    <?php endforeach; ?>
  </ul>
</div>
