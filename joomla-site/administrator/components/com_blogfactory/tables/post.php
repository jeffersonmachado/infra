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

class BlogFactoryTablePost extends BlogFactoryTable
{
    protected $table = '#__com_blogfactory_posts';

    public function check()
    {
        if (!parent::check()) {
            return false;
        }

        // Set post title.
        if (is_null($this->title) || '' == $this->title) {
            $this->title = BlogFactoryText::_('post_untitled');
        }

        // Set post alias.
        if (is_null($this->alias) || '' == $this->alias) {
            $this->alias = JApplicationHelper::stringURLSafe($this->title);
        }

        // Check if alias is unique.
        $settings = JComponentHelper::getParams('com_blogfactory');
        if ($settings->get('general.enable.router_plugin', 0)) {
            JLoader::register('BlogFactoryHelper', JPATH_COMPONENT_ADMINISTRATOR . '/helpers/blogfactory.php');
            $this->alias = BlogFactoryHelper::checkUniqueAlias($this->alias, $this->id);
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

        // Remove all comments for post.
        $query = $dbo->getQuery(true)
            ->select('c.id')
            ->from('#__com_blogfactory_comments c')
            ->where('c.post_id = ' . $dbo->quote($id));
        $results = $dbo->setQuery($query)
            ->loadColumn();

        foreach ($results as $result) {
            $table = JTable::getInstance('Comment', 'BlogFactoryTable');
            $table->delete($result);
        }

        // Remove all tag maps for post.
        $query = $dbo->getQuery(true)
            ->delete()
            ->from('#__com_blogfactory_post_tag_map')
            ->where('post_id = ' . $dbo->quote($id));
        $dbo->setQuery($query)
            ->execute();

        // Remove all revisions for post.
        $query = $dbo->getQuery(true)
            ->delete()
            ->from('#__com_blogfactory_revisions')
            ->where('post_id = ' . $dbo->quote($id));
        $dbo->setQuery($query)
            ->execute();

        // Remove all votes for post.
        $query = $dbo->getQuery(true)
            ->delete()
            ->from('#__com_blogfactory_votes')
            ->where('type = ' . $dbo->quote('post'))
            ->where('item_id = ' . $dbo->quote($id));
        $dbo->setQuery($query)
            ->execute();
    }
}
