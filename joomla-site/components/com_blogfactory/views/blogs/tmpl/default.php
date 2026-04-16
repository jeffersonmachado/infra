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

<h1><?php echo BlogFactoryText::_('blogs_page_heading'); ?></h1>

<?php echo BlogFactoryHelper::beginForm(BlogFactoryRoute::view('blogs'), 'GET', array('class' => 'blogfactory-blog-filters')); ?>
<div class="row-fluid">
    <div class="span5">
        <input type="text" placeholder="<?php echo BlogFactoryText::_('blogs_search_placeholder'); ?>" name="search"
               value="<?php echo $this->search; ?>" class="search-query"/>
    </div>

    <div class="span7">
        <?php echo $this->filterBookmark; ?>
        <?php echo $this->sort; ?>
        <?php echo $this->order; ?>
    </div>
</div>
</form>

<?php if (!$this->blogs): ?>
    <p><?php echo BlogFactoryText::_('blogs_no_blogs_found'); ?></p>
<?php else: ?>
    <?php foreach ($this->blogs as $blog): ?>
        <div class="blogfactory-blog">
            <div class="blogfactory-blog-photo">
                <img src="<?php echo JHtml::_('BlogFactoryBlog.getPhotoSource', $blog->photo); ?>"/>
            </div>

            <div class="blogfactory-blog-description">
                <a href="<?php echo BlogFactoryRoute::view('blog&id=' . $blog->id . '&alias=' . $blog->alias); ?>"
                   class="blogfactory-blog-title site-title">
                    <?php echo $blog->title; ?>
                </a>

                <?php if ($blog->description): ?>
                    <p><?php echo $blog->description; ?></p>
                <?php else: ?>
                    <p class="muted"><?php echo BlogFactoryText::_('blogs_blog_no_description'); ?></p>
                <?php endif; ?>

                <div class="muted blogfactory-blog-info small">
                    <a href="<?php echo BlogFactoryRoute::task('blog.bookmark&id=' . $blog->id); ?>"
                       rel="<?php echo $blog->bookmarked; ?>" class="button-bookmark"><i
                            class="factory-icon-bookmark"></i><span><?php echo BlogFactoryText::plural('blogs_blog_bookmark', $blog->bookmarked); ?></span></a>
                    <a href="<?php echo BlogFactoryRoute::task('blog.subscribe&id=' . $blog->id); ?>"
                       rel="<?php echo $blog->subscribed; ?>" class="button-subscribe"><i class="factory-icon-mail"></i><span><?php echo BlogFactoryText::plural('blogs_blog_subscribe', $blog->subscribed); ?></span></a>
                    <a href="<?php echo BlogFactoryRoute::view('blog&id=' . $blog->id . '&alias=' . $blog->alias); ?>"><i
                            class="factory-icon-document-text"></i><?php echo BlogFactoryText::plural('blogs_blog_posts', $blog->posts); ?>
                    </a>
                    <?php if ($blog->posts): ?>
                        <a href="<?php echo BlogFactoryRoute::view('blog&id=' . $blog->id . '&alias=' . $blog->alias); ?>"
                           class="hasTooltip"
                           title="<?php echo BlogFactoryText::sprintf('blogs_blog_last_activity', JHtml::_('date.relative', $blog->activity)); ?>"><i
                                class="factory-icon-clock"></i><?php echo JHtml::_('date.relative', $blog->activity); ?>
                        </a>
                    <?php endif; ?>
                    <a href="<?php echo BlogFactoryRoute::view('blog&id=' . $blog->id . '&alias=' . $blog->alias . '&format=raw'); ?>"><i
                            class="factory-icon-feed"></i><?php echo BlogFactoryText::_('blogs_blog_subscribe_rss'); ?>
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <?php echo $this->pagination->getPagesLinks(); ?>
<?php endif; ?>
