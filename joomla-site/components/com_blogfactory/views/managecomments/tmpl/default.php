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
    <?php if ($this->post): ?>
        <?php echo BlogFactoryText::sprintf('managecomments_page_heading_post', '<a href="#">' . $this->post->title . '</a>'); ?>
        <a href="<?php echo JHtml::_('BlogFactoryDashboard.manageCommentsFilterPost'); ?>" class="btn btn-small">
            <i class="factory-icon-funnel-minus"></i><?php echo BlogFactoryText::_('managecomments_remove_post_filter'); ?>
        </a>
    <?php else: ?>
        <?php echo BlogFactoryText::_('managecomments_page_heading'); ?>
    <?php endif; ?>
</h1>

<?php echo $this->loadTemplate('filters'); ?>

<?php if ($this->items): ?>
    <?php echo $this->loadTemplate('bulk_actions'); ?>

    <form action="" method="POST" id="blogfactory-form-list">
        <table class="table table-striped blogfactory-list">
            <thead>
            <?php echo $this->loadTemplate('header'); ?>
            </thead>

            <tfoot>
            <?php echo $this->loadTemplate('header'); ?>
            </tfoot>

            <tbody>
            <?php foreach ($this->items as $this->item): ?>
                <?php echo $this->loadTemplate('body'); ?>
            <?php endforeach; ?>
            </tbody>
        </table>

        <input type="hidden" name="task" id="task" value=""/>
    </form>

    <?php echo $this->loadTemplate('bulk_actions'); ?>

    <?php echo $this->pagination->getPagesLinks(); ?>
<?php else: ?>
    <p><?php echo BlogFactoryText::_('managecomments_no_items_found'); ?></p>
<?php endif; ?>
