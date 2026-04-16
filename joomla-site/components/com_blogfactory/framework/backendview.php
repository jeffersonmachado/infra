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

class BlogFactoryBackendView extends BlogFactoryView
{
    protected
        $variablesDefault = array('sidebar'),
        $buttons = array(),
        $cssDefault = array('admin/main'),
        $filters = array();

    protected function beforeDisplay()
    {
        $this->setTitle();
        $this->addToolbar();

        $viewHelp = new BlogFactoryViewHelp();
        $viewHelp->render($this->getName());
    }

    protected function getSidebar()
    {
        BlogFactoryHelper::addSubmenu($this->getName());

        $this->addFilters();

        return JHtmlSidebar::render();
    }

    protected function setTitle()
    {
        JToolbarHelper::title(BlogFactoryText::_('page_heading_' . $this->getName()));
    }

    protected function addToolbar()
    {
        // Initialise variables.
        $singular = $this->getSingularName();
        $plural = $this->getPluralName();
        $bar = JToolBar::getInstance('toolbar');

        if (isset($this->item) && !$this->item->id) {
            if (($key = array_search('save2copy', $this->buttons)) !== false) {
                unset($this->buttons[$key]);
            }
        }

        foreach ($this->buttons as $type => $button) {
            if (is_int($type)) {
                $type = $button;
            }

            switch ($type) {
                case 'home':
                    JToolbarHelper::custom('home', 'home', 'home', BlogFactoryText::_('toolbar_home'), false);
                    break;

                case 'about':
                    JToolbarHelper::custom('about', 'support', 'support', BlogFactoryText::_('toolbar_about'), false);
                    break;

                case 'dashboard':
                    JToolbarHelper::custom('', 'dashboard', 'dashboard', BlogFactoryText::_('toolbar_dashboard'), false);
                    break;

                case 'add':
                    JToolBarHelper::addNew($singular . '.' . $type);
                    break;

                case 'edit':
                    JToolBarHelper::editList($singular . '.' . $type);
                    break;

                case 'publish':
                    JToolBarHelper::publish($plural . '.' . $type, 'JTOOLBAR_PUBLISH', true);
                    break;

                case 'unpublish':
                    JToolBarHelper::unpublish($plural . '.' . $type, 'JTOOLBAR_UNPUBLISH', true);
                    break;

                case 'resolve':
                    JToolBarHelper::publish($plural . '.' . $type, BlogFactoryText::_('toolbar_resolve'), true);
                    break;

                case 'unresolve':
                    JToolBarHelper::unpublish($plural . '.' . $type, BlogFactoryText::_('toolbar_unresolve'), true);
                    break;

                case 'approve':
                    JToolBarHelper::custom($plural . '.' . $type, 'publish', 'publish', BlogFactoryText::_('toolbar_approve'), true);
                    break;

                case 'unapprove':
                    JToolBarHelper::custom($plural . '.' . $type, 'pending', 'pending', BlogFactoryText::_('toolbar_unapprove'), true);
                    break;

                case 'export':
                    JHtml::_('bootstrap.modal', 'collapseModal');

                    $title = BlogFactoryText::_('toolbar_export');
                    $dhtml = "<button data-toggle=\"modal\" data-target=\"#collapseModal\" class=\"btn btn-small\">
						<i class=\"icon-share\" title=\"$title\"></i>
						$title</button>";
                    $bar->appendButton('Custom', $dhtml, 'export');
                    break;

                case 'import':
                    JHtml::_('bootstrap.modal', 'collapseModal');

                    $title = BlogFactoryText::_('toolbar_import');
                    $dhtml = "<button data-toggle=\"modal\" data-target=\"#batch-import\" class=\"btn btn-small\">
						<i class=\"icon-share\" title=\"$title\"></i>
						$title</button>";
                    $bar->appendButton('Custom', $dhtml, 'import');
                    break;

                case 'batch':
                    JHtml::_('bootstrap.modal', 'modalBatch');

                    $title = BlogFactoryText::_('toolbar_batch');
                    $dhtml = "<button data-toggle=\"modal\" data-target=\"#modalBatch\" class=\"btn btn-small\">
						<i class=\"icon-checkbox-partial\" title=\"$title\"></i>
						$title</button>";
                    $bar->appendButton('Custom', $dhtml, 'batch');
                    break;

                case 'delete':
                    JToolBarHelper::deleteList('', $plural . '.' . $type);
                    break;

                case 'apply':
                    JToolBarHelper::apply($singular . '.' . $type);
                    break;

                case 'save':
                    JToolBarHelper::save($singular . '.' . $type);
                    break;

                case 'save2copy':
                    JToolBarHelper::save2copy($singular . '.' . $type);
                    break;

                case 'save2new':
                    JToolBarHelper::save2new($singular . '.' . $type);
                    break;

                case 'close':
                case 'cancel':
                    JToolBarHelper::cancel($singular . '.cancel', 'JTOOLBAR_CLOSE');
                    break;
            }
        }
    }

    protected function addFilters()
    {
        foreach ($this->filters as $filter) {
            $method = 'getFilter' . ucfirst($filter);

            if (!method_exists($this, $method)) {
                continue;
            }

            $values = call_user_func_array(array($this, $method), array());

            JHtmlSidebar::addFilter(
                BlogFactoryText::_($this->getName() . '_filter_' . $filter . '_option'),
                'filter_' . $filter,
                JHtml::_('select.options', $values, 'value', 'text', $this->state->get('filter.' . $filter), true)
            );
        }
    }

    protected function getSingularName()
    {
        return \Doctrine\Common\Inflector\Inflector::singularize($this->getName());
    }

    protected function getPluralName()
    {
        return \Doctrine\Common\Inflector\Inflector::pluralize($this->getName());
    }

    protected function getFilterPublished()
    {
        return JHtml::_('jgrid.publishedOptions', array(
            'trash' => false,
            'all' => false,
            'archived' => false,
        ));
    }

    protected function getFilterLanguage()
    {
        return JHtml::_('contentlanguage.existing', true, true);
    }

    protected function getListDirn()
    {
        return $this->state->get('list.direction');
    }

    protected function getListOrder()
    {
        return $this->state->get('list.ordering');
    }
}
