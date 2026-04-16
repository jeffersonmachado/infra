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

JFormHelper::loadRuleClass('Captcha');

class JFormRuleBlogFactoryCaptcha extends JFormRuleCaptcha
{
    public function test(SimpleXMLElement $element, $value, $group = null, JRegistry $input = null, JForm $form = null)
    {
        $plugin = $element['plugin'] ?: JFactory::getApplication()->getParams()->get('captcha', JFactory::getConfig()->get('captcha', 0));
        $namespace = $element['namespace'] ?: $form->getName();

        // Use 0 for none
        if ($plugin === 0 || $plugin === '0') {
            return true;
        } else {
            $captcha = JCaptcha::getInstance((string)$plugin, array('namespace' => (string)$namespace));
        }

        // Test the value.
        if (!$captcha->checkAnswer($value)) {
            /** @noinspection PhpDeprecationInspection */
            $error = $captcha->getError();

            if ($error instanceof Exception) {
                return $error;
            } else {
                throw new RuntimeException($error);
            }
        }

        return true;
    }
}
