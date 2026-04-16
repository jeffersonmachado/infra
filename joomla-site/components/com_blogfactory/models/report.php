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

class BlogFactoryFrontendModelReport extends JModelLegacy
{
    public function getForm()
    {
        $input = JFactory::getApplication()->input;
        $data = array(
            'type' => $input->getString('type'),
            'item_id' => $input->getString('id'),
        );

        JForm::addFormPath(JPATH_COMPONENT_SITE . '/models/forms');
        $form = JForm::getInstance('com_blogfactory.report', 'report', array(
            'control' => 'jform'
        ));

        $form->bind($data);

        $formName = 'COM_BLOGFACTORY_REPORT';

        foreach ($form->getFieldsets() as $fieldset) {
            foreach ($form->getFieldset($fieldset->name) as $field) {
                $fieldName = ($field->group ? $field->group . '_' : '') . $field->fieldname;

                $label = $form->getFieldAttribute($field->fieldname, 'label', '', $field->group);

                if ('' == $label) {
                    $label = JText::_(strtoupper($formName . '_form_field_' . $fieldName . '_label'));
                    $form->setFieldAttribute($field->fieldname, 'label', $label, $field->group);
                }

                $desc = $form->getFieldAttribute($field->fieldname, 'description', '', $field->group);

                if ('' == $desc) {
                    $desc = JText::_(strtoupper($formName . '_form_field_' . $fieldName . '_desc'));
                    $form->setFieldAttribute($field->fieldname, 'description', $desc, $field->group);
                }
            }
        }

        return $form;
    }

    public function send($data)
    {
        // Check if report type is valid.
        if (!isset($data['type']) || !in_array($data['type'], array('comment'))) {
            $this->setState('error', BlogFactoryText::_('report_send_error_type_unknown'));
            return false;
        }

        $table = JTable::getInstance($data['type'], 'BlogFactoryTable');

        // Check if reported item exists.
        if (!$data['item_id'] || !$table->load($data['item_id'])) {
            $this->setState('error', BlogFactoryText::_('report_send_error_item_not_found'));
            return false;
        }

        $data['item_user_id'] = $table->user_id;

        $report = JTable::getInstance('Report', 'BlogFactoryTable');

        if (!$report->save($data)) {
            $this->setState('error', $table->getError());
            return false;
        }

        $this->setState('post.owner_id', $table->user_id);

        $this->sendNotifications($report);

        return true;
    }

    protected function sendNotifications($report)
    {
        $notification = BlogFactoryNotification::getInstance(JFactory::getMailer());

        $tokens = array(
            'report_type' => $report->type,
            'report_text' => $report->text,
            'report_date' => $report->created_at,
        );

        $notification->sendGroups('report.add.admins', $tokens);

        // Send notification to post owner.
        $blog = JTable::getInstance('Blog', 'BlogFactoryTable');
        $blog->load(array('user_id' => $this->getState('post.owner_id')));

        if ($blog->notification_comment) {
            $notification->send('report.add.owner', $this->getState('post.owner_id'), $tokens);
        }
    }
}
