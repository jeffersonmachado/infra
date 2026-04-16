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

class BlogFactoryFrontendModelIntegrations extends JModelLegacy
{
    protected $limit = 10;
    protected $limitstart;

    public function __construct($config = array())
    {
        parent::__construct($config);

        $this->limitstart = JFactory::getApplication()->input->getInt('limitstart', 0);
    }

    public function getExtensions()
    {
        static $extensions = null;

        if (is_null($extensions)) {
            $settings = JComponentHelper::getParams('com_blogfactory');
            $items = $settings->get('integrations.extensions', array());
            $extensions = array();

            foreach ($items as $component) {
                $language = JFactory::getLanguage();
                $language->load($component . '.sys', JPATH_ADMINISTRATOR);

                $extensions[$component] = JText::_(strtoupper($component));
            }
        }

        return $extensions;
    }

    public function getExtension()
    {
        return JFactory::getApplication()->input->getCmd('extension');
    }

    public function getSearchQuery()
    {
        $query = JFactory::getApplication()->input->getString('query');

        if ('' == $query) {
            return null;
        }

        return $query;
    }

    public function getItems()
    {
        $provider = $this->getDataProviderForExtension($this->getExtension());
        $user = JFactory::getUser();

        if (!$provider) {
            return array();
        }

        return $provider->getItems($user->id, $this->limit, $this->limitstart, $this->getSearchQuery());
    }

    public function getPagination()
    {
        $provider = $this->getDataProviderForExtension($this->getExtension());
        $user = JFactory::getUser();
        $total = 0;

        if ($provider) {
            $total = $provider->getTotal($user->id, $this->getSearchQuery());
        }

        return new JPagination($total, $this->limitstart, $this->limit);
    }

    protected function getDataProviderForExtension($extension = null)
    {
        static $providers = array();

        if (!isset($providers[$extension])) {
            jimport('joomla.filesystem.file');

            $provider = false;
            $extensions = $this->getExtensions();

            if (isset($extensions[$extension])) {
                $file = JPATH_SITE . '/components/' . $extension . '/models/blogfactorydataprovider.php';

                if (JFile::exists($file)) {
                    $class = ucfirst(str_replace('com_', '', $extension)) . 'BlogFactoryDataProvider';
                    require_once $file;

                    $provider = new $class;
                }
            }

            $providers[$extension] = $provider;
        }

        return $providers[$extension];
    }
}
