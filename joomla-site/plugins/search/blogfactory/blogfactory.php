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

class PlgSearchBlogfactory extends JPlugin
{
	protected $autoloadLanguage = true;

	public function onContentSearchAreas()
	{
		static $areas = array(
			'com_blogfactory_posts' => 'PLG_SEARCH_BLOGFACTORY_POSTS',
		);

		return $areas;
	}

	public function onContentSearch($text, $phrase = '', $ordering = '', $areas = null)
	{
		$dbo   = JFactory::getDbo();
		$date  = JFactory::getDate();
    $text  = trim($text);
    $array = array();

		if (is_array($areas)) {
			if (!array_intersect($areas, array_keys($this->onContentSearchAreas()))) {
				return array();
			}
		}

    if ('' == $text) {
			return array();
		}

    $query = $dbo->getQuery(true)
      ->select('p.id, p.title, p.alias, p.created_at, p.publish_up, p.content')
      ->from('#__com_blogfactory_posts p')
      ->where('p.published = ' . $dbo->quote(1))
      ->where('(p.publish_up = ' . $dbo->quote($dbo->getNullDate()) . ' OR p.publish_up < ' . $dbo->quote($date->toSql()) . ')');

    // Select the category.
    $query->select('c.title AS category_title')
      ->leftJoin('#__categories c ON c.id = p.category_id');

    // Filter results.
    switch ($phrase) {
      case 'all':
      case 'any':
        $where = array();
        $words = explode(' ', $text);
        foreach ($words as $i => $word) {
          $word = $dbo->quote('%' . $word . '%');
          $where[] = '(p.title LIKE ' . $word . ' OR p.content LIKE ' . $word . ')';
        }

        $query->where('(' . implode(($phrase == 'all' ? ') AND (' : ') OR ('), $where) . ')');
        break;

      case 'exact':
        $text = $dbo->quote('%' . $text . '%');
        $query->where('(p.title LIKE ' . $text . ' OR p.content LIKE ' . $text . ')');
        break;
    }

    // Order results.
    switch ($ordering) {
      case 'newest':
      default:
        $query->order('p.created_at DESC');
        break;

      case 'oldest':
        $query->order('p.created_at ASC');
        break;

      case 'alpha':
        $query->order('p.title ASC');
        break;

      case 'category':
        $query->order('c.title ASC');
        break;
    }

    $results = $dbo->setQuery($query)
      ->loadObjectList();

    $pageBreak = '<hr class="blogfactory-read-more" />';

    foreach ($results as $result) {
      $post = new stdClass();

      if (false === strpos($result->content, $pageBreak)) {
        $content = $result->content;
      }
      else {
        list($content, $full) = explode($pageBreak, $result->content);
      }

      $post->title = $result->title;
      $post->text  = $content;
      $post->href  = JRoute::_('index.php?option=com_blogfactory&view=post&id=' . $result->id . '&alias=' . $result->alias);
      $post->section = $result->category_title;
      $post->browsernav = 2;
      $post->created = $result->created_at;

      $array[] = $post;
    }

		return $array;
	}
}
