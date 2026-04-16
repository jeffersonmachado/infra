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

class BlogFactoryForm extends JForm
{
    public static function getInstance($name, $data = null, $options = array(), $replace = true, $xpath = false)
    {
        // Reference to array with form instances
        $forms = &self::$forms;

        // Only instantiate the form if it does not already exist.
        if (!isset($forms[$name])) {
            $data = trim($data);

            if (empty($data)) {
                throw new InvalidArgumentException(sprintf('JForm::getInstance(name, *%s*)', gettype($data)));
            }

            // Instantiate the form.
            $forms[$name] = new self($name, $options);

            // Load the data.
            if (substr(trim($data), 0, 1) == '<') {
                if ($forms[$name]->load($data, $replace, $xpath) == false) {
                    throw new RuntimeException('JForm::getInstance could not load form');
                }
            } else {
                if ($forms[$name]->loadFile($data, $replace, $xpath) == false) {
                    throw new RuntimeException('JForm::getInstance could not load file');
                }
            }
        }

        return $forms[$name];
    }

    public function load($data, $replace = true, $xpath = false)
    {
        if (!parent::load($data, $replace, $xpath)) {
            return false;
        }

        $this->setLabelsAndDescriptions();

        return true;
    }

    public function getError()
    {
        $errors = array();

        foreach ($this->getErrors() as $error) {
            $errors[] = $error->getMessage();
        }

        return $errors;
    }

    protected function setLabelsAndDescriptions()
    {
        $formName = str_replace('.', '_', $this->getName());

        foreach ($this->getFieldsets() as $fieldset) {
            foreach ($this->getFieldset($fieldset->name) as $field) {
                $fieldName = ($field->group ? $field->group . '_' : '') . $field->fieldname;

                $label = $this->getFieldAttribute($field->fieldname, 'label', '', $field->group);

                if ('' == $label) {
                    $label = JText::_(strtoupper($formName . '_form_' . $fieldName . '_label'));
                    $this->setFieldAttribute($field->fieldname, 'label', $label, $field->group);
                }

                $desc = $this->getFieldAttribute($field->fieldname, 'description', '', $field->group);

                if ('' == $desc) {
                    $desc = JText::_(strtoupper($formName . '_form_' . $fieldName . '_desc'));
                    $this->setFieldAttribute($field->fieldname, 'description', $desc, $field->group);
                }
            }
        }

        return true;
    }
}
