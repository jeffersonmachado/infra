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

class BlogFactoryFrontendViewMedia extends BlogFactoryFrontendView
{
    protected
        $variables = array('folders', 'files', 'maxUploadSize', 'freeSpace');

    public function __construct($config = array())
    {
        if (!JComponentHelper::getParams('com_blogfactory')->get('user_folder.general.enabled', 1)) {
            throw new Exception('Media manager not enabled!', 403);
        }

        parent::__construct($config);
    }

    protected function tree($name, $folder)
    {
        $html = array();

        $html[] = '<li>';

        $html[] = '<a href="#' . $folder['path'] . '" class="' . ($folder['folders'] ? 'foldable' : '') . '">';

        $html[] = '<i class="' . ($folder['folders'] ? 'factory-icon-toggle-small-expand' : 'factory-icon-blank') . '" rel="fold-handle"></i>';
        $html[] = '<i class="' . ($folder['base'] ? 'factory-icon-blue-folder' : 'factory-icon-folder') . '"></i>';

        $html[] = $name;
        $html[] = '</a>';

        $html[] = '<ul style="display: none;">';

        foreach ($folder['folders'] as $subfolder => $subsubfolders) {
            $html[] = $this->tree($subfolder, $subsubfolders);
        }

        $html[] = '</ul>';
        $html[] = '</li>';

        return implode('', $html);
    }
}
