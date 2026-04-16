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

class BlogFactoryTableBlog extends BlogFactoryTable
{
    protected $table = '#__com_blogfactory_blogs';

    public function check()
    {
        if (!parent::check()) {
            return false;
        }

        // Check if a user is set.
        if (!$this->user_id) {
            $this->user_id = JFactory::getUser()->id;
        }

        // Check if user already has another blog.
        if ($this->userHasAnotherBlog($this->user_id, $this->id)) {
            throw new LogicException(BlogFactoryText::_('blog_save_check_user_has_another_blog'));
        }

        if ('' == $this->alias) {
            $this->alias = JApplicationHelper::stringURLSafe($this->title);
        }

        return true;
    }

    public function delete($pk = null)
    {
        if (!parent::delete($pk)) {
            return false;
        }

        $k = $this->_tbl_key;
        $pk = (is_null($pk)) ? $this->$k : $pk;

        $this->deleteDependencies($pk);

        return true;
    }

    protected function deleteDependencies($id)
    {
        $dbo = $this->getDbo();

        // Remove all posts for blog.
        $query = $dbo->getQuery(true)
            ->select('p.id')
            ->from('#__com_blogfactory_posts p')
            ->where('p.blog_id = ' . $dbo->quote($id));
        $results = $dbo->setQuery($query)
            ->loadColumn();

        foreach ($results as $result) {
            $table = JTable::getInstance('Post', 'BlogFactoryTable');
            $table->delete($result);
        }

        // Remove all followers for blog.
        $query = $dbo->getQuery(true)
            ->delete()
            ->from('#__com_blogfactory_followers')
            ->where('blog_id = ' . $dbo->quote($id));
        $dbo->setQuery($query)
            ->execute();
    }

    protected function userHasAnotherBlog($userId, $blogId)
    {
        $blog = BlogFactoryTable::getInstance('Blog', 'BlogFactoryTable');

        $dbo = $this->getDbo();

        $query = $dbo->getQuery(true)
            ->select('b.id')
            ->from($dbo->qn($this->getTableName(), 'b'))
            ->where($dbo->qn('b.user_id') . ' = ' . $dbo->q($userId))
            ->where($dbo->qn('b.id') . ' <> ' . $dbo->q($blogId));

        $result = $dbo->setQuery($query)
            ->loadResult();

        return $result;
    }
}
