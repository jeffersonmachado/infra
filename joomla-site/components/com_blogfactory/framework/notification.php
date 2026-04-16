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

class BlogFactoryNotification
{
    protected $mailer = null;
    protected $notifications = array();

    public function __construct($mailer)
    {
        $this->mailer = $mailer;
    }

    public static function getInstance($mailer)
    {
        static $instance = null;

        if (is_null($instance)) {
            $instance = new self($mailer);
        }

        return $instance;
    }

    public function send($type, $receiverId, $tokens = array())
    {
        // Initialise variables.
        $receiver = JFactory::getUser($receiverId);
        $receiverLanguage = $receiver->getParam('language', JComponentHelper::getParams('com_languages')->get('site'));
        $receiverEmail = $receiver->email;
        $receiverUsername = $receiver->username;
        $notification = $this->getNotification($type, $receiverLanguage);

        // Check if notification was found.
        if (!$notification) {
            return true;
        }

        // Add common tokens.
        $tokens['receiver_username'] = $receiverUsername;

        // Prepare subject and body.
        $subject = $this->parseTokens($notification->subject, $tokens, $receiver);
        $body = $this->parseTokens($notification->body, $tokens, $receiver);

        // Send mail.
        if (!$this->sendMail($receiverEmail, $subject, $body)) {
            return false;
        }

        return true;
    }

    public function sendGroups($type, $tokens = array())
    {
        $notification = $this->getNotification($type, '*');

        if (!$notification) {
            return true;
        }

        $groups = new JRegistry($notification->groups);
        $groups = $groups->toArray();

        if (!$groups) {
            return true;
        }

        $users = $this->getUsersFromGroups($groups);

        if (!$users) {
            return true;
        }

        foreach ($users as $id) {
            $this->send($type, $id, $tokens);
        }

        return true;
    }

    protected function getNotification($type, $language)
    {
        // Get notification.
        if (!isset($this->notifications[$type])) {
            $this->notifications[$type] = $this->getNotifications($type);
        }

        if (isset($this->notifications[$type][$language])) {
            $notification = $this->notifications[$type][$language];
        } elseif (isset($this->notifications[$type]['*'])) {
            $notification = $this->notifications[$type]['*'];
        } else {
            return false;
        }

        return $notification;
    }

    public static function getNotifications($type)
    {
        $dbo = JFactory::getDbo();
        $query = $dbo->getQuery(true)
            ->select('n.*')
            ->from('#__com_blogfactory_notifications n')
            ->where('n.published = ' . $dbo->quote(1))
            ->where('n.type = ' . $dbo->quote($type));

        $notifications = $dbo->setQuery($query)
            ->loadObjectList('lang_code');

        return $notifications;
    }

    protected function parseTokens($string, $tokens, $receiver)
    {
        // Initialise variables.
        $search = array();
        $replace = array();

        // Parse tokens.
        foreach ($tokens as $token => $value) {
            $search[] = '%%' . $token . '%%';

            // Check if value is a date.
            if (false !== strtotime($value)) {
                $offset = $receiver->getParam('timezone', JFactory::getConfig()->get('offset'));
                $value = JHtml::_('date', $value, 'DATE_FORMAT_LC2', $offset);
            }

            $replace[] = $value;
        }

        // Replace tokens.
        $string = str_replace($search, $replace, $string);

        // Replace image sources.
        $string = str_replace('src="', 'src="' . JURI::root(), $string);

        return $string;
    }

    protected function sendMail($email, $subject, $body)
    {
        $app = JFactory::getApplication();

        $this->mailer->ClearAddresses();
        $this->mailer->setSubject($subject);
        $this->mailer->setBody($body);
        $this->mailer->addRecipient($email);
        $this->mailer->setSender(array($app->get('mailfrom'), $app->get('fromname')));
        $this->mailer->isHtml(true);

        if (!$this->mailer->send()) {
            return false;
        }

        return true;
    }

    protected function getUsersFromGroups($groups)
    {
        if (!is_array($groups) || !$groups) {
            return array();
        }

        $dbo = JFactory::getDbo();

        // First find the users contained in the group
        $query = $dbo->getQuery(true)
            ->select('DISTINCT(user_id)')
            ->from('#__usergroups as ug1')
            ->join('INNER', '#__usergroups AS ug2 ON ug2.lft = ug1.lft AND ug1.rgt = ug2.rgt')
            ->join('INNER', '#__user_usergroup_map AS m ON ug2.id=m.group_id')
            ->where('ug1.id IN (' . implode(',', $groups) . ')');

        $dbo->setQuery($query);

        $result = $dbo->loadColumn();

        // Clean up any NULL values, just in case
        JArrayHelper::toInteger($result);

        return $result;
    }
}
