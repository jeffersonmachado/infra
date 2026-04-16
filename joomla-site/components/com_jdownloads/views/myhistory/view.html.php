<?php
/**
 * @package jDownloads
 * @version 3.2  
 * @copyright (C) 2007 - 2017 - Arno Betz - www.jdownloads.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 * 
 * jDownloads is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */
 
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

/**
 * View class for a list of downloads
 */
class jdownloadsViewMyHistory extends JViewLegacy
{
    protected $state = null;
    protected $item = null;
    protected $items = null;
    protected $pagination = null;

	/**
	 * Display the view
     * @return    mixed    False on error, null otherwise.
	 */
	public function display($tpl = null)
	{
        
        global $jlistConfig;
        
        $app    = JFactory::getApplication();
        $user    = JFactory::getUser();
        
        if ($user->guest){
            $menus = $app->getMenu();
            $menu  = $menus->getActive();

            if ($menu){
                $redirect_url = $menu->link.'&Itemid='.$menu->id;
                $redirect_url = urlencode(base64_encode($redirect_url));
                $redirect_url = '&return='.$redirect_url;
                $login_url    = 'index.php?option=com_users&view=login';
                $final_url    = $login_url.$redirect_url;

                JControllerLegacy::setRedirect($final_url);
                JControllerLegacy::redirect();
            } else {
                JError::raiseNotice(100, JText::_('COM_JDOWNLOADS_MY_DOWNLOAD_HISTORY_NOT_FOUND'));
                return false;            
            }
        }
        
        $jd_user_settings = JDHelper::getUserRules();

        // Initialise variables
        $state        = $this->get('State');
        $items        = $this->get('Items');
        $pagination   = $this->get('Pagination');
        
        if (!$items){
            JError::raiseNotice(100, JText::_('COM_JDOWNLOADS_MY_DOWNLOAD_HISTORY_NOT_FOUND'));
            return false;            
        }        
        
        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            JError::raiseWarning(500, implode("\n", $errors));
            return false;
        }

        if ($items === false) {
            return JError::raiseError(404, JText::_('COM_JDOWNLOADS_MY_DOWNLOAD_HISTORY_NOT_FOUND'));
        }

        // add all needed cripts and css files
        $document = JFactory::getDocument();
        
        $document->addScript(JUri::base().'components/com_jdownloads/assets/js/jdownloads.js');
        
        $document->addScriptDeclaration('var live_site = "'.JUri::base().'";');

        if ($jlistConfig['use.css.buttons.instead.icons']){
           $document->addStyleSheet( JUri::base()."components/com_jdownloads/assets/css/jdownloads_buttons.css", "text/css", null, array() ); 
        }
        
        $document->addStyleSheet( JUri::base()."components/com_jdownloads/assets/css/jdownloads_fe.css", "text/css", null, array() );
        
        $custom_css_path = JPATH_ROOT.'/components/com_jdownloads/assets/css/jdownloads_custom.css';
        if (JFile::exists($custom_css_path)){
            $document->addStyleSheet( JURI::base()."components/com_jdownloads/assets/css/jdownloads_custom.css", 'text/css', null, array() );                
        }   
        
        $this->jd_image_path = JPATH_ROOT  . '/images/jdownloads';        
        
        $params = &$state->params;
                
        // Compute the download slugs and prepare text (runs content plugins).
        for ($i = 0, $n = count($items); $i < $n; $i++)
        {
            $item = &$items[$i];
            
            // required for some content plugins
            $item->text = '';
            $item->id = '';
            
            $dispatcher = JDispatcher::getInstance();

            JPluginHelper::importPlugin('content');
            $dispatcher->trigger('onContentPrepare', array ('com_jdownloads.downloads', &$item, &$item->params, 0));

            $item->event = new stdClass();

            $results = $dispatcher->trigger('onContentAfterTitle', array('com_jdownloads.downloads', &$item, &$item->params, 0));
            $item->event->afterDisplayTitle = trim(implode("\n", $results));

            $results = $dispatcher->trigger('onContentBeforeDisplay', array('com_jdownloads.downloads', &$item, &$item->params, 0));
            $item->event->beforeDisplayContent = trim(implode("\n", $results));

            $results = $dispatcher->trigger('onContentAfterDisplay', array('com_jdownloads.downloads', &$item, &$item->params, 0));
            $item->event->afterDisplayContent = trim(implode("\n", $results));
        }        
        
        //Escape strings for HTML output
        $this->pageclass_sfx = htmlspecialchars($params->get('pageclass_sfx'));

        $this->assignRef('state',  $state);        
        $this->assignRef('params', $params);
        $this->assignRef('items',  $items);
        $this->assignRef('pagination', $pagination);

        $this->_prepareDocument();

        parent::display($tpl);
    }

    /**
     * Prepares the document
     */
    protected function _prepareDocument()
    {
        $app    = JFactory::getApplication();
        $menus    = $app->getMenu();
        $title    = null;

        // Because the application sets a default page title,
        // we need to get it from the menu item itself
        $menu = $menus->getActive();
        
        if ($menu) {
            $this->params->def('page_heading', $this->params->get('page_title', $menu->title));
        } else {
            $this->params->def('page_heading', JText::_('COM_JDOWNLOADS_DOWNLOADS'));
        }        
        
        $title = $this->params->get('page_title', '');
        if (empty($title)) {
            $title = $app->getCfg('sitename');
        }
        elseif ($app->getCfg('sitename_pagetitles', 0) == 1) {
            $title = JText::sprintf('JPAGETITLE', $app->getCfg('sitename'), $title);
        }
        elseif ($app->getCfg('sitename_pagetitles', 0) == 2) {
            $title = JText::sprintf('JPAGETITLE', $title, $app->getCfg('sitename'));
        }
        $this->document->setTitle($title);

        if ($this->params->get('menu-meta_description'))
        {
            $this->document->setDescription($this->params->get('menu-meta_description'));
        }

        if ($this->params->get('menu-meta_keywords'))
        {
            $this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
        }

        if ($this->params->get('robots'))
        {
            $this->document->setMetadata('robots', $this->params->get('robots'));
        }
    }
}
