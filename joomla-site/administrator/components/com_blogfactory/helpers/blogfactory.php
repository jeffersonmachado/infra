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

class BlogFactoryHelper
{
    public static function addSubmenu($vName)
    {
        JLoader::discover('BlogFactory', JPATH_SITE . '/components/com_blogfactory/framework');

        $submenu = array(
            'dashboard',
            'comments',
            'posts',
            'reports',
            'blogs',
            'users',
            'categories',
            'bookmarks',
            'notifications',
            'settings',
            'about',
        );

        foreach ($submenu as $item) {
            if ('categories' == $item) {
                JHtmlSidebar::addEntry(
                    BlogFactoryText::_('submenu_' . $item), JRoute::_('index.php?option=com_categories&extension=com_blogfactory'), $vName == $item
                );
            } else {
                JHtmlSidebar::addEntry(
                    BlogFactoryText::_('submenu_' . $item), BlogFactoryRoute::view($item), $vName == $item
                );
            }
        }
    }

    public static function addLabelsToForm($form, $debug = false)
    {
        $formName = str_replace('.', '_', $form->getName());

        foreach ($form->getFieldsets() as $fieldset) {
            foreach ($form->getFieldset($fieldset->name) as $field) {
                $fieldGroup = str_replace('.', '_', $field->group);
                $fieldName = ($fieldGroup ? $fieldGroup . '_' : '') . $field->fieldname;

                $label = $form->getFieldAttribute($field->fieldname, 'label', '', $field->group);

                if ('' == $label) {
                    $label = !$debug ? JText::_(strtoupper($formName . '_form_field_' . $fieldName . '_label')) : $fieldName;
                    $form->setFieldAttribute($field->fieldname, 'label', $label, $field->group);
                }

                $desc = $form->getFieldAttribute($field->fieldname, 'description', '', $field->group);

                if ('' == $desc) {
                    $desc = !$debug ? JText::_(strtoupper($formName . '_form_field_' . $fieldName . '_desc')) : $fieldName;
                    $form->setFieldAttribute($field->fieldname, 'description', $desc, $field->group);
                }
            }
        }
    }

    public static function getNotificationTypes()
    {
        $xml = simplexml_load_file(JPATH_COMPONENT_ADMINISTRATOR . '/blogfactory.xml');
        $notifications = $xml->xpath('//parameters/notifications/notification');
        $options = array();

        foreach ($notifications as $notification) {
            $value = (string)$notification->attributes()->type;
            $text = BlogFactoryText::_('notification_type_' . $value);

            $options[] = array('value' => $value, 'text' => $text);
        }

        return $options;
    }

    public static function checkUniqueAlias($alias, $itemId = 0)
    {
        $unique = false;

        while (!$unique) {
            $post = JTable::getInstance('Post', 'BlogFactoryTable');
            $blog = JTable::getInstance('Blog', 'BlogFactoryTable');

            $data = array('alias' => $alias);

            if ($post->load($data)) {
                if ($post->id != $itemId) {
                    $alias = self::generateNextAlias($alias);
                    continue;
                }
            }

            if ($blog->load($data)) {
                if ($blog->id != $itemId) {
                    $alias = self::generateNextAlias($alias);
                    continue;
                }
            }

            $unique = true;
        }

        return $alias;
    }

    public static function getFormLayout($form)
    {
        $layout = array();

        foreach ($form->getFieldsets() as $set => $options) {
            if (!isset($layout[$options->tab])) {
                $layout[$options->tab] = array('left' => array(), 'right' => array(), 'full' => array());
            }

            $layout[$options->tab][$options->side][] = $set;
        }

        return $layout;
    }

    public static function exportPostToContent($postId, $options)
    {
        $post = JTable::getInstance('Post', 'BlogFactoryTable');
        $post->load($postId);

        if (!is_array($options)) {
            $options = (array)$options;
        }

        $content = JTable::getInstance('Content', 'JTable');
        $content->load($post->exported_to_id);

        $text = explode('<hr class="blogfactory-read-more" />', $post->content);
        $data = array(
            'id' => $content->load($post->exported_to_id) ? $post->exported_to_id : null,
            'title' => $post->title,
            'introtext' => $text[0],
            'fulltext' => isset($text[1]) ? $text[1] : null,
            'state' => $options['status'],
            'catid' => $options['category'],
            'access' => $options['access'],
            'language' => $options['language'],
            'featured' => $options['featured'],
        );

        try {
            $content->save($data);
        } catch (Exception $e) {
            JFactory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            return false;
        }

        $post->exported_to_id = $content->id;
        $post->store();

        return true;
    }

    public static function importContent($options)
    {
        $articles = self::getArticlesForImport($options['source']);
        $count = 0;

        if (!$articles) {
            throw new Exception(BlogFactoryText::_('import_content_error_no_articles_found'));
        }

        JPluginHelper::importPlugin('content');
        $dispatcher = JEventDispatcher::getInstance();

        foreach ($articles as $article) {
            $article->text = $article->introtext . '<hr class="blogfactory-read-more" />' . $article->fulltext;
            $params = new JRegistry;
            $dispatcher->trigger('onContentPrepare', array('com_content.article', &$article, &$params, 0));

            $data = array(
                'title' => $article->title,
                'content' => $article->text,
                'blog_id' => $options['target']['blog_id'],
                'user_id' => $options['target']['user_id'],
                'category_id' => isset($options['target']['category_id']) ? $options['target']['category_id'] : null,
                'visibility' => $options['target']['visibility'],
                'published' => $options['target']['published'],
            );

            $post = JTable::getInstance('Post', 'BlogFactoryTable');
            if (!$post->save($data)) {
                throw new Exception($content->getError());
            }

            $count++;
        }

        return $count;
    }

    public static function checkPermissions()
    {
        $input = JFactory::getApplication()->input;

        $view = $input->getCmd('view');
        $task = $input->getCmd('task');

        if ('blog.auto' === $task) {
            $view = null;
        }

        $xml = simplexml_load_file(JPATH_COMPONENT_SITE . '/permissions.xml');

        $paths = array(
            '/permissions/permission/view[text()="' . $view . '"]/ancestor::permission',
            '/permissions/permission/task[text()="' . $task . '"]/ancestor::permission',
        );

        if (false !== strpos($task, '.')) {
            list($controller, $task) = explode('.', $task);

            $paths[] = '/permissions/permission/controller[text()="' . $controller . '"]/ancestor::permission';
        }

        $permissions = array();
        $query = $xml->xpath(implode(' | ', $paths));

        if ($query) {
            foreach ($query as $permission) {
                $permission = (string)$permission->attributes()->type;

                if (!in_array($permission, $permissions)) {
                    $permissions[] = $permission;
                }
            }
        }

        if (!$permissions) {
            return true;
        }

        foreach ($permissions as $permission) {
            self::checkPermission($permission);
        }
    }

    public static function filterPostContent($text)
    {
        $settings = JComponentHelper::getParams('com_blogfactory');
        $tags = $settings->get('post.tags.whitelist', 'ol,li,span,em,strong,ul,blockquote,p,a,h1,h2,h3,h4,h5,h6,hr,img,sup,sub,code');
        $attrs = $settings->get('post.attrs.whitelist', 'src,style,href,height,width,target,class,alt');

        $tags = explode(',', $tags);
        $attrs = explode(',', $attrs);

        $tagsMethod = 0;
        $attrMethod = 0;
        $xssAuto = 0;

        return self::doFilterPostContent($text, $tags, $attrs);
    }

    public static function doFilterPostContent($text, array $tags = array(), array $attrs = array(), $tagsMethod = 0, $attrMethod = 0, $xssAuto = 0)
    {
        $filter = JFilterInput::getInstance($tags, $attrs, $tagsMethod, $attrMethod, $xssAuto);

        return $filter->clean($text, 'html');
    }

    public static function beginForm($url, $method = 'GET', $config = array())
    {
        $app = JFactory::getApplication();
        $html = array();
        $class = isset($config['class']) ? $config['class'] : '';

        // Add the start form tag.
        $html[] = '<form action="' . $url . '" method="' . $method . '" class="' . $class . '">';

        // Check if SEF it's not enabled.
        if (!$app->get('sef', 0)) {
            $url = parse_url($url);
            parse_str($url['query'], $output);

            foreach ($output as $key => $value) {
                $html[] = '<input type="hidden" name="' . $key . '" value="' . $value . '">';
            }
        }

        BlogFactoryHtml::script('form');

        return implode("\n", $html);
    }

    public static function deleteUserAvatar($avatar)
    {
        jimport('joomla.filesystem.file');

        if ($avatar && JFile::exists($path = JPATH_SITE . '/media/com_blogfactory/avatars/' . $avatar)) {
            JFile::delete($path);
        }

        return true;
    }

    public static function saveUserAvatar($file, $oldAvatar)
    {
        if (isset($file['error']) && 0 == $file['error']) {
            // Remove old thumbnail.
            self::deleteUserAvatar($oldAvatar);

            $ext = JFile::getExt($file['name']);
            $name = md5(time() . mt_rand(10000, 99999)) . '.' . $ext;

            $images = BlogFactoryImages::getInstance();
            $images->resizeCrop($file['tmp_name'], 48, JPATH_SITE . '/media/com_blogfactory/avatars/' . $name);

            return $name;
        }

        return false;
    }

    protected static function checkPermission($permission)
    {
        $user = JFactory::getUser();
        $app = JFactory::getApplication();

        $redirect = null;
        $message = null;

        switch ($permission) {
            case 'logged':
                if ($user->guest) {
                    $return = base64_encode(JUri::getInstance()->current());
                    $redirect = JRoute::_('index.php?option=com_users&view=login&return=' . $return, false);
                    $message = BlogFactoryText::_('permission_logged_message');
                }
                break;

            case 'has_blog':
                JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_blogfactory/tables');
                $table = JTable::getInstance('Blog', 'BlogFactoryTable');

                if (!$table->load(array('user_id' => $user->id))) {
                    $params = JComponentHelper::getParams('com_blogfactory');

                    if ('manual' === $params->get('general.blog_creation', 'manual')) {
                        $redirect = BlogFactoryRoute::view('blogedit');
                        $message = BlogFactoryText::_('permission_has_blog_message');
                    } else {
                        $return = base64_encode(JUri::getInstance()->current());
                        $redirect = BlogFactoryRoute::task('blog.auto&return=' . $return);
                    }
                }

                break;

            case 'create_blog':
                $results = JEventDispatcher::getInstance()->trigger('onBlogFactoryBeforeCreateBlog', array(
                    'com_blogfactory.create_blog.before', $user->id
                ));

                if ($results) {
                    foreach ($results as $result) {
                        if (!is_array($result) || true === $result[0]) {
                            continue;
                        }

                        $redirect = BlogFactoryRoute::view('blogs');
                        $message = $result[1];
                        break;
                    }
                    break;
                }

                if (self::isSocialFactoryIntegrated()) {
                    if (!self::isUserAllowedBySocialFactory()) {
                        $redirect = BlogFactoryRoute::view('blogs');
                        $message = BlogFactoryText::_('permission_socialfactory_create_blog_message');
                    }
                } elseif (!$user->authorise('frontend.blog.create', 'com_blogfactory')) {
                    $redirect = BlogFactoryRoute::view('blogs');
                    $message = BlogFactoryText::_('permission_create_blog_message');
                }
                break;
        }

        if ($redirect) {
            if (!self::isXmlHttpRequest()) {
                $app->redirect($redirect, $message);
            }

            echo json_encode(array('status' => 0, 'message' => $message));
            jexit();
        }
    }

    protected static function isXmlHttpRequest()
    {
        return JFactory::getApplication()->input->server->get('HTTP_X_REQUESTED_WITH', '') == 'XMLHttpRequest';
    }

    protected static function generateNextAlias($alias)
    {
        if (preg_match('/(.+)\-([0-9]+)\b/', $alias, $matches)) {
            return $matches[1] . '-' . (intval($matches[2]) + 1);
        }

        return $alias . '-1';
    }

    protected static function isSocialFactoryIntegrated()
    {
        $extension = JTable::getInstance('Extension');
        $result = $extension->find(array('type' => 'component', 'element' => 'com_socialfactory'));

        if (!$result) {
            return false;
        }

        $settings = JComponentHelper::getParams('com_socialfactory');

        return $settings->get('integration_blogfactory.enable', 0);
    }

    protected static function isUserAllowedBySocialFactory()
    {
        defined('DS') or define('DS', DIRECTORY_SEPARATOR);

        JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_socialfactory/tables');
        $user = JTable::getInstance('SfUser', 'SocialFactoryTable');
        $user->load(JFactory::getUser()->id);

        $restriction = $user->getMembershipFeature('blogfactory.enable', 0);

        if (false === $restriction) {
            return true;
        }

        return $restriction;
    }

    protected static function getArticlesForImport($filters)
    {
        $dbo = JFactory::getDbo();

        $query = $dbo->getQuery(true)
            ->select('c.*')
            ->from('#__content AS c');

        $defaultFilters = array('catid', 'state', 'access', 'language', 'featured');

        foreach ($defaultFilters as $filter) {
            if (isset($filters[$filter]) && '' != $value = $filters[$filter]) {
                $query->where($dbo->qn('c.' . $filter) . ' = ' . $dbo->q($value));
            }
        }

        $results = $dbo->setQuery($query)
            ->loadObjectList();

        return $results;
    }
}
