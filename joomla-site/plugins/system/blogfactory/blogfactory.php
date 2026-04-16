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

class PlgSystemBlogFactory extends JPlugin
{
  public function onBeforeRender()
  {
    $input = JFactory::getApplication()->input;

    if ('com_blogfactory' === $input->getCmd('option') &&
       (boolean)$this->params->get('cache_assets', 1))
    {
      JLoader::discover('BlogFactory', JPATH_SITE . '/components/com_blogfactory/framework');
      BlogFactoryHtml::renderAssets();
    }
  }

  public function onAfterInitialise()
	{
	  $app = JFactory::getApplication();

	  // Not in backend.
	  if ($app->isAdmin()) {
			return true;
		}

		// Not if SEF is disabled.
    if (!$app->get('sef')) {
      return true;
    }

    // Check if plugin router is enabled from settings.
    $settings = JComponentHelper::getParams('com_blogfactory');
    if (!$settings->get('general.enable.router_plugin', 0)) {
      return true;
    }

    // Get router.
	  $router = $app->getRouter();

    // Attach rules.
	  $router->attachParseRule(array($this, 'parse'));
	  $router->attachBuildRule(array($this, 'build'));
	}

	public function parse($router, &$uri)
  {
    jimport('joomla.filesystem.file');

    $vars  = array();
    $path  = $uri->getPath();
    $path  = explode('/', $path);
    $alias = $path[0];

    if (JFactory::getApplication()->get('sef_suffix')) {
      $alias = JFile::stripExt($alias);
    }

    // More than 1 segments or alias is empty
    if (1 < count($path) || '' == $alias) {
      return $vars;
    }

    // Find item (blog or post)
    $item = $this->findItemByAlias($alias);

    // Item was not found
    if (!$item) {
      return $vars;
    }

    // Set the vars
    $vars = array(
      'option' => 'com_blogfactory',
      'view'   => $item instanceof BlogFactoryTablePost ? 'post' : 'blog',
      'alias'  => $item->alias,
      'id'     => $item->id,
    );

    return $vars;
  }

  public function build($router, &$uri)
  {
    if ('com_blogfactory' == $uri->getVar('option') &&
        false === strpos($uri->toString(), 'index.php?Itemid=')
        && in_array($uri->getVar('view'), array('blog', 'post')))
    {
      $alias      = $uri->getVar('alias');
      $limitstart = $uri->getVar('limitstart');
      $format     = $uri->getVar('format', 'html');
      $query      = array();

      if (!is_null($limitstart)) {
        $query[] = 'limitstart=' . $limitstart;
      }

      if ('html' != $format) {
        $query[] = 'format=' . $format;
      }

      $query = $query ? '?' . implode('&', $query) : '';

      $uri->setQuery(null);
      $uri->setPath('index.php/' . $alias . $query);
    }
  }

  protected function findItemByAlias($alias)
  {
    JLoader::discover('BlogFactory', JPATH_SITE . '/components/com_blogfactory/framework');
    JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_blogfactory/tables/');

    $blog = JTable::getInstance('Blog', 'BlogFactoryTable');
    $post = JTable::getInstance('Post', 'BlogFactoryTable');

    $data = array('alias' => $alias);

    if ($blog->load($data)) {
      return $blog;
    }

    if ($post->load($data)) {
      return $post;
    }

    return false;
  }
}
