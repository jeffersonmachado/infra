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

class JFormFieldBlogFactoryPostTags extends JFormField
{
    public $type = 'BlogFactoryTags';

    protected function getLabel()
    {
        return '';
    }

    protected function getInput()
    {
        JHtml::script('components/com_blogfactory/assets/js/fields/tags.js');

        $html = array();
        $postId = $this->form->getValue('id');
        $tags = $this->getTags($postId);

        $html[] = '<input id="tag" type="text" />';
        $html[] = '<a href="#" class="btn button-add-tag">' . BlogFactoryText::_('post_edit_tag_tag') . '</a>';

        $html[] = '<div class="muted info-tag" style="margin: 5px 0 10px;">' . BlogFactoryText::_('post_edit_tags_info') . '</div>';

        $html[] = '<div class="tags">';

        foreach ($tags as $tag) {
            $html[] = '<a href="#">';
            $html[] = '<span class="label label-info">' . $tag . '</span>';
            $html[] = '<input type="hidden" name="' . $this->name . '[]" value="' . $tag . '" />';
            $html[] = '</a>';
        }
        $html[] = '</div>';

        return implode("\n", $html);
    }

    protected function getTags($postId)
    {
        if (!$postId) {
            return array();
        }

        $dbo = JFactory::getDbo();
        $query = $dbo->getQuery(true)
            ->select('t.name')
            ->from('#__com_blogfactory_post_tag_map m')
            ->leftJoin('#__com_blogfactory_tags t ON t.id = m.tag_id')
            ->where('m.post_id = ' . $dbo->quote($postId));
        $results = $dbo->setQuery($query)
            ->loadObjectList('name');

        return array_keys($results);
    }
}
