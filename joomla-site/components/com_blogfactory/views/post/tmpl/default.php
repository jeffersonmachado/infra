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

<div class="page-header">
    <h1><?php echo $this->item->title; ?></h1>
</div>

<div class="post-content">
    <?php echo $this->item->content; ?>
</div>

<div class="post-info muted" class="muted">
    <?php echo BlogFactoryText::sprintf('post_info_posted_by', $this->item->username); ?>

    <?php if ($this->categoriesEnabled && $this->category->id): ?>
        <?php echo BlogFactoryText::sprintf('post_info_posted_in', '<a href="' . BlogFactoryRoute::view('posts&category_id=' . $this->category->id) . '">' . $this->category->title . '</a>'); ?>
    <?php endif; ?>

    <?php if ($this->tags): ?>
        <?php echo BlogFactoryText::sprintf('post_info_posted_tagged', JHtml::_('BlogFactoryPost.tags', $this->tags)); ?>
    <?php endif; ?>

    <?php echo BlogFactoryText::sprintf('post_info_posted_on', JHtml::_('date', $this->item->created_at, 'DATE_FORMAT_LC2')); ?>

    <?php echo BlogFactoryText::sprintf('post_info_posted_in_blog', $this->blog->title); ?>

    <a href="<?php echo BlogFactoryRoute::task('blog.subscribe&id=' . $this->item->blog_id); ?>"
       rel="<?php echo $this->subscribed; ?>" class="button-subscribe">
        <i class="factory-icon-mail"></i><span><?php echo BlogFactoryText::plural('blogs_blog_subscribe', $this->subscribed); ?></span>
    </a>
</div>

<div class="post-info muted">
    <?php if ($this->ratingsEnabled): ?>
        <div class="post-rating">
            <span><?php echo BlogFactoryText::_('post_rating'); ?></span>

            <?php if (!$this->allowedRating): ?>
                <i class="factory-icon-thumb-up"></i><span><?php echo $this->item->votes_up; ?></span>
                <i class="factory-icon-thumb-down"></i><span><?php echo $this->item->votes_down; ?></span>.
            <?php elseif ($this->vote): ?>
                <i class="factory-icon-thumb-up"></i><span
                    class="<?php echo 1 == $this->vote->vote ? 'voted' : ''; ?>"><?php echo $this->item->votes_up; ?></span>
                <i class="factory-icon-thumb-down"></i><span
                    class="<?php echo -1 == $this->vote->vote ? 'voted' : ''; ?>"><?php echo $this->item->votes_down; ?></span>.
            <?php else: ?>
                <a href="<?php echo BlogFactoryRoute::task('post.vote&format=raw&vote=up&id=' . $this->item->id); ?>"
                   class="vote-up"><i
                        class="factory-icon-thumb-up"></i><span><?php echo $this->item->votes_up; ?></span></a>
                <a href="<?php echo BlogFactoryRoute::task('post.vote&format=raw&vote=down&id=' . $this->item->id); ?>"
                   class="vote-down"><i
                        class="factory-icon-thumb-down"></i><span><?php echo $this->item->votes_down; ?></span></a>.
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($this->bookmarksEnabled): ?>
        <div class="post-bookmarks">
            <span><?php echo BlogFactoryText::_('post_bookmark'); ?></span>

            <?php foreach ($this->bookmarks as $this->bookmark): ?>
                <?php echo JHtml::_('BlogFactoryPost.bookmark', $this->bookmark, array('title' => $this->item->title)); ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($this->item->comments): ?>
    <?php echo JHtml::_('BlogFactoryComments.display', $this->item->id, array('comments' => $this->item->comments)); ?>
<?php endif; ?>
