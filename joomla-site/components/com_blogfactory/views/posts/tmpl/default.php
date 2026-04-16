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

<h1>
    <?php if ($this->filterCategory->id): ?>
        <?php echo BlogFactoryText::sprintf($this->getName() . '_page_heading_category', $this->filterCategory->title); ?>
    <?php elseif ($this->filterTag->id): ?>
        <?php echo BlogFactoryText::sprintf($this->getName() . '_page_heading_tag', $this->filterTag->name); ?>
    <?php elseif ($this->filterDate): ?>
        <?php echo BlogFactoryText::sprintf($this->getName() . '_page_heading_date', $this->filterDate); ?>
    <?php else: ?>
        <?php echo BlogFactoryText::_($this->getName() . '_page_heading'); ?>
    <?php endif; ?>
</h1>

<div class="blogfactory-posts" style="overflow: hidden;">
    <?php foreach ($this->items as $this->item): ?>
        <?php echo $this->loadTemplate('post'); ?>
    <?php endforeach; ?>

    <?php if (!$this->items): ?>
        <?php echo BlogFactoryText::_('posts_no_posts_found'); ?>
    <?php endif; ?>
</div>

<?php echo $this->pagination->getPagesLinks(); ?>
