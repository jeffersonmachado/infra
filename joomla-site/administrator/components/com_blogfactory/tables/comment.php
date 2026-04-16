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

class BlogFactoryTableComment extends BlogFactoryTable
{
    protected $table = '#__com_blogfactory_comments';

    public function approve($pks = null, $state = 1)
    {
        $k = $this->_tbl_key;

        // Sanitize input.
        JArrayHelper::toInteger($pks);
        $state = (int)$state;

        // If there are no primary keys set check to see if the instance key is set.
        if (empty($pks)) {
            if ($this->$k) {
                $pks = array($this->$k);
            } else {
                return false;
            }
        }

        // Update the resolved state for rows with the given primary keys.
        $query = $this->_db->getQuery(true)
            ->update($this->_tbl)
            ->set('approved = ' . (int)$state);

        // Build the WHERE clause for the primary keys.
        $query->where($k . ' = ' . implode(' OR ' . $k . ' = ', $pks));

        $this->_db->setQuery($query);
        $this->_db->execute();

        // If the JTable instance value is in the list of primary keys that were set, set the instance.
        if (in_array($this->$k, $pks)) {
            $this->approved = $state;
        }

        return true;
    }

    public function check()
    {
        if (!parent::check()) {
            return false;
        }

        if (is_null($this->published)) {
            $this->published = 1;
        }

        if (is_null($this->created_at) || !$this->created_at) {
            $this->created_at = JFactory::getDate()->toSql();
        }

        $this->text = $this->replaceBannedWords($this->text);

        return true;
    }

    public function replaceBannedWords($text)
    {
        $settings = JComponentHelper::getParams('com_blogfactory');
        $words = $settings->get('banned_words.words', '');

        if ('' != $words) {
            $words = explode("\n", $words);

            foreach ($words as $i => $word) {
                $word = trim($word);

                if ('' == $word) {
                    unset($words[$i]);
                } else {
                    $words[$i] = $word;
                }
            }

            if ($words) {
                $text = str_ireplace($words, '***', $text);
            }
        }

        return $text;
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

        // Remove all votes for comment.
        $query = $dbo->getQuery(true)
            ->delete()
            ->from('#__com_blogfactory_votes')
            ->where('type = ' . $dbo->quote('comment'))
            ->where('item_id = ' . $dbo->quote($id));
        $dbo->setQuery($query)
            ->execute();
    }
}
