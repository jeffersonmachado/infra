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

class BlogFactoryFrontendModelDashboard extends JModelLegacy
{
    public function getSetup($default = 'overview/0/1;quickpost/0/1;bookmarks/0/1;followers/0/1;posts/0/2;comments/0/2')
    {
        $state = explode(';', JFactory::getApplication()->input->cookie->getString('com_blogfactory_dashboard', $default));
        $array = array();

        foreach ($state as $item) {
            list($portlet, $minimized, $column) = explode('/', $item);

            $array[$column][$portlet] = $minimized;
        }

        return $array;
    }

    public function getPortletPosts()
    {
        $user = JFactory::getUser();
        $dbo = $this->getDbo();

        $query = $dbo->getQuery(true)
            ->select('p.*')
            ->from('#__com_blogfactory_posts p')
            ->where('p.user_id = ' . $dbo->quote($user->id))
            ->order('p.created_at DESC');
        $results = $dbo->setQuery($query, 0, 5)
            ->loadObjectList();

        return $results;
    }

    public function getPortletComments()
    {
        $dbo = $this->getDbo();
        $user = JFactory::getUser();

        $query = $dbo->getQuery(true)
            ->select('c.*')
            ->from('#__com_blogfactory_comments c')
            ->order('c.created_at DESC')
            ->where('p.user_id = ' . $dbo->quote($user->id));

        // Select post.
        $query->select('p.title')
            ->leftJoin('#__com_blogfactory_posts p ON p.id = c.post_id');

        // Select user.
        $query->select('u.username, u.email AS user_email')
            ->leftJoin('#__users u ON u.id = c.user_id');

        // Select avatar.
        $query->select('prf.avatar_source, prf.avatar, prf.name AS profile_name, prf.url AS profile_url')
            ->leftJoin('#__com_blogfactory_profiles prf ON prf.id = c.user_id');

        $results = $dbo->setQuery($query, 0, 5)
            ->loadObjectList();

        return $results;
    }

    public function getPortletOverview()
    {
        $user = JFactory::getUser();
        $dbo = $this->getDbo();
        $data = array();

        // Get total number of posts.
        $query = $dbo->getQuery(true)
            ->select('COUNT(p.id)')
            ->from('#__com_blogfactory_posts p')
            ->where('p.user_id = ' . $dbo->quote($user->id));
        $data['posts_total'] = $dbo->setQuery($query)
            ->loadResult();

        // Get total number of posts published.
        $query = $dbo->getQuery(true)
            ->select('COUNT(p.id)')
            ->from('#__com_blogfactory_posts p')
            ->where('p.user_id = ' . $dbo->quote($user->id))
            ->where('p.published = ' . $dbo->quote(1));
        $data['posts_published'] = $dbo->setQuery($query)
            ->loadResult();

        // Get total number of comments.
        $query = $dbo->getQuery(true)
            ->select('COUNT(c.id)')
            ->from('#__com_blogfactory_comments c')
            ->leftJoin('#__com_blogfactory_posts p ON p.id = c.post_id')
            ->where('p.user_id = ' . $dbo->quote($user->id));
        $data['comments_total'] = $dbo->setQuery($query)
            ->loadResult();

        // Get total number of comments pending.
        $query = $dbo->getQuery(true)
            ->select('COUNT(c.id)')
            ->from('#__com_blogfactory_comments c')
            ->leftJoin('#__com_blogfactory_posts p ON p.id = c.post_id')
            ->where('p.user_id = ' . $dbo->quote($user->id))
            ->where('c.approved = ' . $dbo->quote(0));
        $data['comments_pending'] = $dbo->setQuery($query)
            ->loadResult();

        // Get total number of bookmarks.
        $query = $dbo->getQuery(true)
            ->select('COUNT(f.id)')
            ->from('#__com_blogfactory_followers f')
            ->where('f.user_id = ' . $dbo->quote($user->id));
        $data['bookmarks'] = $dbo->setQuery($query)
            ->loadResult();

        // Get total number of bookmarks subscribed.
        $query = $dbo->getQuery(true)
            ->select('COUNT(s.id)')
            ->from('#__com_blogfactory_subscriptions s')
            ->where('s.user_id = ' . $dbo->quote($user->id));;
        $data['bookmarks_subscribed'] = $dbo->setQuery($query)
            ->loadResult();

        // Get total number of followers.
        $blog = $this->getTable('Blog', 'BlogFactoryTable');
        $blog->load(array('user_id' => $user->id));

        $query = $dbo->getQuery(true)
            ->select('COUNT(f.id)')
            ->from('#__com_blogfactory_followers f')
            ->where('f.blog_id = ' . $dbo->quote($blog->id));
        $data['followers'] = $dbo->setQuery($query)
            ->loadResult();

        // Get total number of followers subscribed.
        $query = $dbo->getQuery(true)
            ->select('COUNT(s.id)')
            ->from('#__com_blogfactory_subscriptions s')
            ->where('s.blog_id = ' . $dbo->quote($blog->id));
        $data['followers_subscribed'] = $dbo->setQuery($query)
            ->loadResult();

        return $data;
    }

    public function getPortletBookmarks()
    {
        $user = JFactory::getUser();
        $dbo = $this->getDbo();

        $query = $dbo->getQuery(true)
            ->select('b.id, b.title, b.alias, f.created_at')
            ->from('#__com_blogfactory_followers f')
            ->leftJoin('#__com_blogfactory_blogs b ON b.id = f.blog_id')
            ->where('f.user_id = ' . $dbo->quote($user->id))
            ->order('f.created_at DESC');
        $results = $dbo->setQuery($query, 0, 5)
            ->loadObjectList();

        return $results;
    }

    public function getPortletFollowers()
    {
        $user = JFactory::getUser();
        $dbo = $this->getDbo();
        $blog = $this->getTable('Blog', 'BlogFactoryTable');

        $blog->load(array('user_id' => $user->id));

        $query = $dbo->getQuery(true)
            ->select('f.id')
            ->from('#__com_blogfactory_followers f')
            ->where('f.blog_id = ' . $dbo->quote($blog->id))
            ->order('f.created_at DESC');

        $query->select('u.username, u.email AS user_email')
            ->leftJoin('#__users u ON u.id = f.user_id');

        // Select avatar.
        $query->select('prf.avatar_source, prf.avatar')
            ->leftJoin('#__com_blogfactory_profiles prf ON prf.id = f.user_id');

        $results = $dbo->setQuery($query, 0, 15)
            ->loadObjectList();

        return $results;
    }
}
