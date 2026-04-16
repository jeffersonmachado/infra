<?php
/**
 */

defined('_JEXEC') or die;

/**
 */
class PlgContentJDownloads_Tags_Fix extends JPlugin
{
	/**
	 * @param   string   $context  The context of the content passed to the plugin 
	 * @param   object   $data     A JTableContent object
	 * @param   boolean  $isNew    If the content is just about to be created
	 *
	 */
	public function onContentAfterSave($context, $data, $isNew)
	{
		// Check we are handling a jDownloads content
		if ($context == 'com_jdownloads.download' || $context == 'com_jdownloads.category'){

			$db = JFactory::getDbo();
			$query = $db->getQuery(true)
				->update($db->quoteName('#__ucm_content'))
				->set($db->quoteName('core_catid') . ' = 0')
				    ->where($db->quoteName('core_type_alias').' = '.$db->quote('com_jdownloads.download'))
	                ->orWhere($db->quoteName('core_type_alias').' = '.$db->quote('com_jdownloads.category'));
			$db->setQuery($query)->execute();
			
			return true;
        
        } else {
            return true;
        }
	}
}
	