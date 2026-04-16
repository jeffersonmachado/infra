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

class BlogFactoryFrontendModelComment extends JModelLegacy
{
    public function vote($userId, $data)
    {
        // Initialise variables.
        $table = $this->getTable('Comment', 'BlogFactoryTable');
        $vote = $this->getTable('Vote', 'BlogFactoryTable');

        // Check if user is registered.
        if (!$userId) {
            $this->setState('error', BlogFactoryText::_('comment_vote_error_guests_not_allowed'));
            return false;
        }

        // Check if comment exists.
        if (!isset($data['id']) || !$data['id'] || !$table->load($data['id'])) {
            $this->setState('error', BlogFactoryText::_('comment_vote_error_not_found'));
            return false;
        }

        // Check if comment is approved.
        $settings = JComponentHelper::getParams('com_blogfactory');
        if ($settings->get('comments.approval', 0) && !$table->approved) {
            $this->setState('error', BlogFactoryText::_('comment_vote_error_not_approved'));
            return false;
        }

        // Check if comment is publisjed.
        if (!$table->published) {
            $this->setState('error', BlogFactoryText::_('comment_vote_error_not_published'));
            return false;
        }

        // Check if user has already voted for comment.
        if ($vote->load(array('user_id' => $userId, 'item_id' => $data['id'], 'type' => 'comment'))) {
            $this->setState('error', BlogFactoryText::_('comment_vote_error_alreay_voted'));
            return false;
        }

        // Update the comment votes.
        if (1 == $data['vote']) {
            $table->votes_up++;
        } else {
            $table->votes_down++;
        }

        // Save the comment.
        if (!$table->store()) {
            return false;
        }

        // Register the new vote.
        $data = array(
            'user_id' => $userId,
            'item_id' => $data['id'],
            'vote' => $data['vote'],
            'type' => 'comment',
        );

        // Save the new vote.
        if (!$vote->save($data)) {
            return false;
        }

        return true;
    }

    public function getRating($commentId)
    {
        $table = $this->getTable('Comment', 'BlogFactoryTable');
        $table->load($commentId);

        return array('up' => $table->votes_up, 'down' => $table->votes_down);
    }

    public function delete($ids)
    {
        // Initialise variables.
        $ids = (array)$ids;
        $user = JFactory::getUser();

        // Check if user is logged in.
        if ($user->guest) {
            $this->setState('error', BlogFactoryText::_('comment_delete_error_login'));
            return false;
        }

        foreach ($ids as $id) {
            $table = $this->getTable('Comment', 'BlogFactoryTable');
            $post = $this->getTable('Post', 'BlogFactoryTable');

            // Check if comment exists.
            if (!$id || !$table->load($id)) {
                $this->setState('error', BlogFactoryText::_('comment_delete_error_not_found'));
                return false;
            }

            // Check if post exists.
            if (!$post->load($table->post_id)) {
                $this->setState('error', BlogFactoryText::_('comment_delete_error_post_not_found'));
                return false;
            }

            // Check if user is owner of the post or comment.
            if ($post->user_id != $user->id && $table->user_id != $user->id) {
                $this->setState('error', BlogFactoryText::_('comment_delete_error_not_allowed'));
                return false;
            }

            // Delete comment.
            if (!$table->delete()) {
                return false;
            }

            // Trigger delete comment event.
            $dispatcher = JEventDispatcher::getInstance();
            $dispatcher->register('onBlogFactoryCommentAfterDelete', 'onBlogFactoryCommentAfterDelete');
            $dispatcher->trigger('onBlogFactoryCommentAfterDelete', array($table));
        }

        return true;
    }

    public function save($data)
    {
        if ('' != $data['url'] && (0 !== strpos($data['url'], 'http://') || 0 !== strpos($data['url'], 'https://'))) {
            $data['url'] = 'http://' . $data['url'];
        }

        $form = $this->getForm();
        $data = $form->filter($data);

        if (!$form->validate($data)) {
            $errors = array();

            foreach ($form->getErrors() as $error) {
                $errors[] = $error->getMessage();
            }

            $this->setState('error', implode('<br />', $errors));
            return false;
        }

        $data['user_id'] = JFactory::getUser()->id;

        // Check if post exists.
        $post = $this->getTable('Post', 'BlogFactoryTable');
        if (!$data['post_id'] || !$post->load($data['post_id'])) {
            $this->setState('error', BlogFactoryText::_('comment_save_error_post_not_found'));
            return false;
        }

        // Check if post is published.
        if (!$post->published) {
            $this->setState('error', BlogFactoryText::_('comment_save_error_post_not_published'));
            return false;
        }

        // Check if post has comments enabled.
        if (!$post->comments) {
            $this->setState('error', BlogFactoryText::_('comment_save_error_comments_not_enabled'));
            return false;
        }

        // Check if post belongs to the user.
        if ($post->user_id == $data['user_id']) {
            $data['approved'] = 1;
        }

        $table = $this->getTable('Comment', 'BlogFactoryTable');
        if (!$table->save($data)) {
            return false;
        }

        $dispatcher = JEventDispatcher::getInstance();
        $dispatcher->register('onBlogFactoryCommentAfterSave', 'onBlogFactoryCommentAfterSave');
        $dispatcher->trigger('onBlogFactoryCommentAfterSave', array('com_blogfactory.comment', $table));

        return true;
    }

    public function getForm()
    {
        BlogFactoryForm::addFormPath(JPATH_SITE . '/components/com_blogfactory/models/forms');
        BlogFactoryForm::addRulePath(JPATH_SITE . '/components/com_blogfactory/models/rules');

        /* @var $form JForm */
        $user = JFactory::getUser();
        $form = BlogFactoryForm::getInstance('com_blogfactory.comment', 'comment', array('control' => 'jform'));

        if (!$user->guest) {
            $form->removeField('name');
            $form->removeField('email');
            $form->removeField('url');
            $form->removeField('captcha');
        } else {
            $settings = JComponentHelper::getParams('com_blogfactory');
            $captcha = $settings->get('comments.enable.captcha', 0);

            if (!$captcha) {
                $form->removeField('captcha');
            } else {
                $form->setFieldAttribute('captcha', 'plugin', $captcha);
            }
        }

        return $form;
    }

    public function approve($ids, $approved)
    {
        // Initialise variables.
        $user = JFactory::getUser();
        $ids = (array)$ids;

        foreach ($ids as $id) {
            $table = $this->getTable('Comment', 'BlogFactoryTable');
            $post = $this->getTable('Post', 'BlogFactoryTable');

            // Load comment.
            if (!$id || !$table->load($id)) {
                $this->setState('error', BlogFactoryText::_('comment_approve_error_comment_not_found'));
                return false;
            }

            // Check if user is allowed to approve comment.
            $post->load($table->post_id);
            if ($post->user_id != $user->id) {
                $this->setState('error', BlogFactoryText::_('comment_approve_error_not_allowed'));
                return false;
            }

            // Check if comment is already approved / unapproved.
            if (count($ids) == 1 && $approved != $table->approved) {
                $this->setState('error', BlogFactoryText::plural('comment_approve_error_comment_already_approved', $approved));
                return false;
            }

            // Approve / Unapprove comment.
            $table->approved = !$approved;

            if (!$table->store()) {
                return false;
            }
        }

        return true;
    }

    public function resolve($ids)
    {
        // Initialise variables.
        $user = JFactory::getUser();
        $ids = (array)$ids;

        foreach ($ids as $id) {
            $table = $this->getTable('Comment', 'BlogFactoryTable');
            $post = $this->getTable('Post', 'BlogFactoryTable');

            // Load comment.
            if (!$id || !$table->load($id)) {
                $this->setState('error', BlogFactoryText::_('comment_resolve_error_comment_not_found'));
                return false;
            }

            // Load post.
            if (!$post->load($table->post_id)) {
                $this->setState('error', BlogFactoryText::_('comment_resolve_error_post_not_found'));
                return false;
            }

            // Check if user is allowed to approve comment.
            if ($post->user_id != $user->id) {
                $this->setState('error', BlogFactoryText::_('comment_resolve_error_not_allowed'));
                return false;
            }

            // Check if comment is already resolved.
            if (count($ids) == 1 && !$table->reported) {
                $this->setState('error', BlogFactoryText::_('comment_reslove_error_comment_already_resloved'));
                return false;
            }

            // Resolve comment report.
            $table->reported = 0;

            if (!$table->store()) {
                return false;
            }
        }

        return true;
    }
}

function onBlogFactoryCommentAfterSave($context, $comment)
{
    if ('com_blogfactory.comment' != $context) {
        return true;
    }

    $notification = BlogFactoryNotification::getInstance(JFactory::getMailer());

    $post = JTable::getInstance('Post', 'BlogFactoryTable');
    $post->load($comment->post_id);

    $tokens = array(
        'post_link' => '<a href="' . BlogFactoryRoute::view('post&id=' . $post->id . '&alias=' . $post->alias, false, -1) . '">' . $post->title . '</a>',
        'post_title' => $post->title,
        'comment_date' => $comment->created_at,
        'comment_text' => $comment->text,
    );

    // Send notification to post owner.
    $blog = JTable::getInstance('Blog', 'BlogFactoryTable');
    $blog->load($post->blog_id);

    if ($blog->notification_comment) {
        $notification->send('comment.add.owner', $post->user_id, $tokens);
    }

    // Send notification to administrators.
    $notification->sendGroups('comment.add.admins', $tokens);

    return true;
}

function onBlogFactoryCommentAfterDelete($table)
{
    // Remove comment ratings.
    $model = JModelLegacy::getInstance('Ratings', 'BlogFactoryFrontendModel');
    $model->deleteForComment($table->id);

    return true;
}
