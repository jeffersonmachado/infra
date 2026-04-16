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

class BlogFactoryView extends JViewLegacy
{
    protected
        $variables = array(),
        $variablesDefault = array(),
        $jhtmls = array(),
        $js = array(),
        $css = array(),
        $cssDefault = array();

    public function __construct($config = array())
    {
        parent::__construct($config);

        $this->loadAssets();
    }

    public function display($tpl = null)
    {
        $document = JFactory::getDocument();

        $this->loadVariables();
        $this->setMetadata($document);
        $this->beforeDisplay();

        return parent::display($tpl);
    }

    protected function loadVariables()
    {
        // Get the variables that need to be loaded.
        $variables = array_merge($this->variables, $this->variablesDefault);

        // Load each variable.
        foreach ($variables as $variable) {
            $this->$variable = $this->get($variable);

            $method = 'get' . $variable;
            if (is_null($this->$variable) && method_exists($this, $method)) {
                $this->$variable = $this->$method();
            }
        }

        // Check for errors loading the variables.
        if (count($errors = $this->get('Errors'))) {
            throw new Exception(implode("\n", $errors), 500);
        }

        return true;
    }

    protected function loadAssets()
    {
        JHtml::_('bootstrap.loadCss');

        // Load JHtmls.
        foreach ($this->jhtmls as $jhtml) {
            if (false !== strpos($jhtml, '/')) {
                $jhtml = explode('/', $jhtml);
            } else {
                $jhtml = array($jhtml);
            }

            // Check for arrays of parameters.
            foreach ($jhtml as $key => $value) {
                if (0 === strpos($value, '[') && strlen($value) - 1 === strpos($value, ']')) {
                    $jhtml[$key] = explode(',', trim($value, '[]'));
                }
            }

            call_user_func_array(array('JHtml', '_'), $jhtml);
        }

        $prefix = JFactory::getApplication()->isAdmin() ? 'admin/' : '';

        // Load Javascript files.
        $this->js[] = $prefix . $this->getName();
        foreach ($this->js as $js) {
            BlogFactoryHtml::script($js);
        }

        // Load Stylesheet files.
        $css = array_merge($this->cssDefault, $this->css);
        $css[] = $prefix . $this->getName();

        foreach ($css as $stylesheet) {
            BlogFactoryHtml::stylesheet($stylesheet);
        }
    }

    /**
     * @param $document JDocumentHtml
     */
    protected function setMetadata($document)
    {
    }

    protected function beforeDisplay()
    {
    }
}
