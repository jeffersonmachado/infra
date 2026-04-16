<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
 
/**
 * General Controller of AlfContact component
 */

class AlfcontactController extends JControllerLegacy
{
        /**
         * display task
         *
         * @return void
         */
    function display($cachable = false, $urlparams = false) 
    {
        $jinput = JFactory::getApplication()->input;

        // set default view if not set
        $jinput->set('view', $jinput->get('view', 'Alfcontacts'));
 
        // call parent behavior
        parent::display($cachable, $urlparams);
    }
}
