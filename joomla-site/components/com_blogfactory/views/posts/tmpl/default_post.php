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

<div class="blogfactory-post">
    <div class="blogfactory-post-title">
        <a href="<?php echo BlogFactoryRoute::view('post&id=' . $this->item->id . '&alias=' . $this->item->alias); ?>">
            <?php echo $this->item->title; ?>
        </a>
    </div>

    <div class="blogfactory-post-info">
    <span class="blogfactory-post-info-item">
      <a href="<?php echo BlogFactoryRoute::view('posts&date=' . JFactory::getDate($this->item->created_at)->format('Y-m-d')); ?>"><i
              class="factory-icon-clock"></i><?php echo JHtml::_('date', $this->item->created_at, 'DATE_FORMAT_LC2'); ?></a>
    </span>

        <?php if ($this->categoriesEnabled && $this->item->category_id): ?>
            <span class="blogfactory-post-info-item">
        <a href="<?php echo BlogFactoryRoute::view('posts&category_id=' . $this->item->category_id); ?>"><i
                class="factory-icon-folder"></i><?php echo $this->item->category_title; ?></a>
      </span>
        <?php endif; ?>

        <?php if ($this->item->has_comments_enabled): ?>
            <span class="blogfactory-post-info-item">
        <a href="<?php echo BlogFactoryRoute::view('post&id=' . $this->item->id . '&alias=' . $this->item->alias); ?>#comments"><i
                class="factory-icon-balloon"></i><?php echo BlogFactoryText::plural('posts_comments', $this->item->comments); ?></a>
      </span>
        <?php endif; ?>

        <span class="blogfactory-post-info-item">
      <a href="<?php echo BlogFactoryRoute::view('blog&id=' . $this->item->blog_id . '&alias=' . $this->item->blog_alias); ?>"><i
              class="factory-icon-blog"></i><?php echo $this->item->blog_title; ?></a>
    </span>

        <?php if ($this->item->tags): ?>
            <span class="blogfactory-post-info-item">
        <?php foreach ($this->item->tags as $tag): ?>
            <a href="<?php echo BlogFactoryRoute::view('posts&tag=' . $tag->alias); ?>"><i
                    class="factory-icon-tag"></i><?php echo $tag->name; ?></a>
        <?php endforeach; ?>
      </span>
        <?php endif; ?>
    </div>

    <div class="blogfactory-post-content">
        <?php echo $this->item->content; ?>

        <?php if ($this->item->read_more): ?>
            <div class="blogfactory-post-read-more">
                <a href="<?php echo BlogFactoryRoute::view('post&id=' . $this->item->id . '&alias=' . $this->item->alias); ?>"
                   class="btn btn-small"><?php echo BlogFactoryText::_('posts_post_continue_reading'); ?></a>
            </div>
        <?php endif; ?>
    </div>
</div>
