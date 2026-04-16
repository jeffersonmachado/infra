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

class BlogFactoryFrontendModelPost extends JModelLegacy
{
    protected $start;
    protected $limit;

    public function __construct($config = array())
    {
        parent::__construct($config);

        $this->start = JFactory::getApplication()->input->getInt('limitstart', 0);
        $this->limit = 2;
    }

    public function getItem()
    {
        static $posts = array();

        $preview = JFactory::getApplication()->input->getInt('preview');

        if (!is_null($preview)) {
            $hash = 'preview.' . $preview;
            if (!isset($posts[$hash])) {
                $revision = $this->getTable('Revision', 'BlogFactoryTable');
                $post = $this->getTable('Post', 'BlogFactoryTable');

                if (!$preview || !$revision->load(array('id' => $preview, 'type' => 'preview'))) {
                    throw new Exception(BlogFactoryText::_('post_error_preview_not_found'), 404);
                }

                if (!$post->load($revision->post_id)) {
                    throw new Exception(BlogFactoryText::_('post_error_preview_not_found'), 404);
                }

                if ($post->user_id != JFactory::getUser()->id) {
                    throw new Exception(BlogFactoryText::_('post_error_preview_not_found'), 404);
                }

                $data = array(
                    'title' => $revision->title,
                    'content' => $revision->content,
                );

                $post->bind($data);

                $posts[$hash] = $this->preparePost($post);
            }
        } else {
            // Initialise variables.
            $id = JFactory::getApplication()->input->getId('id');
            $table = $this->getTable('Post', 'BlogFactoryTable');
            $post = array('id' => $id, 'published' => 1);
            $hash = md5(implode('.', $post));

            // Find post.
            if (!isset($posts[$hash])) {
                $posts[$hash] = $table->load($post) ? $this->preparePost($table) : false;
            }
        }

        // Check if post exists.
        if (!isset($posts[$hash]) || !$posts[$hash]) {
            throw new Exception(BlogFactoryText::_('post_error_not_found'), 404);
        }

        return $posts[$hash];
    }

    public function getCategory()
    {
        $item = $this->getItem();
        $table = JTable::getInstance('Category');

        $table->load($item->category_id);

        return $table;
    }

    public function getTags()
    {
        $item = $this->getItem();
        $dbo = $this->getDbo();

        $query = $dbo->getQuery(true)
            ->select('t.name, t.alias')
            ->from('#__com_blogfactory_post_tag_map m')
            ->leftJoin('#__com_blogfactory_tags t ON t.id = m.tag_id')
            ->where('m.post_id = ' . $dbo->quote($item->id));
        $results = $dbo->setQuery($query)
            ->loadObjectList();

        return $results;
    }

    public function getBookmarks()
    {
        $dbo = $this->getDbo();
        $query = $dbo->getQuery(true)
            ->select('b.*')
            ->from('#__com_blogfactory_bookmarks b')
            ->where('b.published = ' . $dbo->quote(1))
            ->order('b.ordering ASC');

        $results = $dbo->setQuery($query)
            ->loadObjectList();

        return $results;
    }

    public function getVote()
    {
        $user = JFactory::getUser();
        $item = $this->getItem();
        $table = $this->getTable('Vote', 'BlogFactoryTable');

        if (!$table->load(array('user_id' => $user->id, 'type' => 'post', 'item_id' => $item->id))) {
            return false;
        }

        return $table;
    }

    public function vote($id, $vote)
    {
        // Initialise variables.
        $user = JFactory::getUser();
        $post = $this->getTable('Post', 'BlogFactoryTable');
        $table = $this->getTable('Vote', 'BlogFactoryTable');
        $settings = JComponentHelper::getParams('com_blogfactory');

        // Check if post rating is enabled.
        if (!$settings->get('post.enable.votes', 1)) {
            return false;
        }

        // Check if user is allowed to vote.
        if (!$this->getAllowedRating()) {
            return false;
        }

        // Check if post exists.
        if (!$post->load($id)) {
            $this->setState('error', BlogFactoryText::_('post_vote_error_not_found'));
            return false;
        }

        // Check if user has already voted.
        $data = array(
            'type' => 'post',
            'item_id' => $id,
            'user_id' => $user->id,
        );

        if ($user->guest) {
            $data['user_ip'] = JFactory::getApplication()->input->server->get('REMOTE_ADDR');
        }

        if ($table->load($data)) {
            $this->setState('error', BlogFactoryText::_('post_vote_error_vote_exists'));
            return false;
        }

        // Vote post.
        $data = array(
            'item_id' => $id,
            'type' => 'post',
            'vote' => $vote,
            'user_id' => $user->id,
            'user_ip' => JFactory::getApplication()->input->server->get('REMOTE_ADDR'),
        );

        if (!$table->save($data)) {
            return false;
        }

        // Update post votes.
        if (1 == $vote) {
            $total = ++$post->votes_up;
        } else {
            $total = ++$post->votes_down;
        }

        $post->store();
        $this->setState('votes.total', $total);

        return true;
    }

    public function getSubscribed()
    {
        $item = $this->getItem();
        $subscription = $this->getTable('Subscription', 'BlogFactoryTable');

        $result = $subscription->load(array('blog_id' => $item->blog_id, 'user_id' => JFactory::getUser()->id));

        return $result;
    }

    public function getBlog()
    {
        $item = $this->getItem();
        $blog = $this->getTable('Blog', 'BlogFactoryTable');

        $blog->load($item->blog_id);

        return $blog;
    }

    public function getAllowedRating()
    {
        $user = JFactory::getUser();

        return $user->authorise('frontend.post.vote', 'com_blogfactory');
    }

    protected function preparePost($post)
    {
        $post->metadata = new JRegistry($post->metadata);
        $post->content = str_replace('<hr class="blogfactory-read-more" />', '', $post->content);
        $post->username = JFactory::getUser($post->user_id)->username;

        return $post;
    }
}
