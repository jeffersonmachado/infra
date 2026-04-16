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

<h1><?php echo $this->blog->title; ?></h1>

<div class="blogfactory-posts" style="overflow: hidden;">
    <?php foreach ($this->posts as $this->item): ?>
        <?php echo $this->loadTemplate('post'); ?>
    <?php endforeach; ?>

    <?php if (!$this->posts): ?>
        <?php echo BlogFactoryText::_('posts_no_posts_found'); ?>
    <?php endif; ?>
</div>

<?php echo $this->pagination->getPagesLinks(); ?>
