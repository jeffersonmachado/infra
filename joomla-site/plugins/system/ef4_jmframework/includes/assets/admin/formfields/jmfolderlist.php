<?php
/**
 * @version $Id: jmfolderlist.php 38 2014-10-29 07:42:48Z michal $
 * @package JMFramework
 * @copyright Copyright (C) 2012 DJ-Extensions.com LTD, All rights reserved.
 * @license http://www.gnu.org/licenses GNU/GPL
 * @author url: http://dj-extensions.com
 * @author email contact@dj-extensions.com
 * @developer Michal Olczyk - michal.olczyk@design-joomla.eu
 *
 * JMFramework is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * JMFramework is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with JMFramework. If not, see <http://www.gnu.org/licenses/>.
 *
 */
defined('JPATH_PLATFORM') or die;

jimport('joomla.filesystem.folder');
JFormHelper::loadFieldClass('folderlist');

/**
 * Lists folders within a template
 */
 
class JFormFieldJMFolderList extends JFormFieldFolderList
{
    protected $type = 'JMFolderList';


    protected function getOptions()
    {
        $options = array();
        
        $path = '';
        if (defined('JMF_TPL_PATH')) {
            $path = (!empty($this->directory)) ? JMF_TPL_PATH . JPath::clean('/' . $this->directory) : JMF_TPL_PATH;   
        } else {
            return false;
        }

        // Prepend some default options based on field attributes.
        /*if (!$this->hideNone)
        {
            $options[] = JHtml::_('select.option', '-1', JText::alt('JOPTION_DO_NOT_USE', preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname)));
        }*/

        if (!$this->hideDefault)
        {
            $options[] = JHtml::_('select.option', '', JText::alt('JOPTION_USE_DEFAULT', preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname)));
        }

        // Get a list of folders in the search path with the given filter.
        $folders = JFolder::folders($path, $this->filter);

        // Build the options list from the list of folders.
        if (is_array($folders))
        {
            foreach ($folders as $folder)
            {
                // Check to see if the file is in the exclude mask.
                if ($this->exclude)
                {
                    if (preg_match(chr(1) . $this->exclude . chr(1), $folder))
                    {
                        continue;
                    }
                }

                $options[] = JHtml::_('select.option', $folder, $folder);
            }
        }

        // Merge any additional options in the XML definition.
        $options = array_merge($this->getListOptions(), $options);

        return $options;
    }

    protected function getListOptions()
    {
        $options = array();

        foreach ($this->element->children() as $option)
        {
            // Only add <option /> elements.
            if ($option->getName() != 'option')
            {
                continue;
            }

            // Filter requirements
            if ($requires = explode(',', (string) $option['requires']))
            {
                // Requires multilanguage
                if (in_array('multilanguage', $requires) && !JLanguageMultilang::isEnabled())
                {
                    continue;
                }

                // Requires associations
                if (in_array('associations', $requires) && !JLanguageAssociations::isEnabled())
                {
                    continue;
                }
            }

            $value = (string) $option['value'];

            $disabled = (string) $option['disabled'];
            $disabled = ($disabled == 'true' || $disabled == 'disabled' || $disabled == '1');

            $disabled = $disabled || ($this->readonly && $value != $this->value);

            // Create a new option object based on the <option /> element.
            $tmp = JHtml::_(
                'select.option', $value,
                JText::alt(trim((string) $option), preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname)), 'value', 'text',
                $disabled
            );

            // Set some option attributes.
            $tmp->class = (string) $option['class'];

            // Set some JavaScript option attributes.
            $tmp->onclick = (string) $option['onclick'];

            // Add the option object to the result set.
            $options[] = $tmp;
        }

        reset($options);

        return $options;
    }
}
