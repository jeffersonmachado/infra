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

class BlogFactoryFrontendModelPostEdit extends JModelLegacy
{
    public function getForm($loadData = true)
    {
        BlogFactoryForm::addFormPath(JPATH_SITE . '/components/com_blogfactory/models/forms');
        BlogFactoryForm::addFieldPath(JPATH_SITE . '/components/com_blogfactory/models/fields');

        /* @var $form JForm */
        $form = BlogFactoryForm::getInstance('com_blogfactory.post', 'post', array('control' => 'jform'));

        if ($loadData) {
            $item = $this->getItem();
            $form->bind($item);

            if (!$item->id) {
                $form->setValue('published', null, 0);
            }

            if (!$item->published) {
                $form->setFieldAttribute('published', 'disabled', 'true');
            }
        }

        $settings = JComponentHelper::getParams('com_blogfactory');

        if (!$settings->get('general.enable.categories', 1)) {
            $form->removeField('category_id');
        }

        if (!$settings->get('post.enable.guest_view', 1)) {
            $form->removeField('visibility');
        }

        return $form;
    }

    public function save($data, $mode = 'draft')
    {
        $tags = isset($data['tags']) ? $data['tags'] : array();

        switch ($mode) {
            case 'publish':
                $data['published'] = 1;
                break;

            case 'draft':
                $data['published'] = 0;
                break;
        }

        // Get form.
        $form = $this->getForm(false);
        $data = $form->filter($data);

        // Validate input data.
        if (!$form->validate($data)) {
            $errors = array();

            foreach ($form->getErrors() as $error) {
                $errors[] = $error->getMessage();
            }

            $this->setState('error', implode('<br />', $errors));
            return false;
        }

        // Set blog id.
        $blog = $this->getTable('Blog', 'BlogFactoryTable');
        $blog->load(array('user_id' => $data['user_id']));

        $data['blog_id'] = $blog->id;

        // Check if post is published and there is no title set.
        if ('' == $data['title'] && $data['published']) {
            $data['published'] = 0;
        }

        $table = $this->getTable('Post', 'BlogFactoryTable');

        $metadata = new JRegistry($data['metadata']);
        $data['metadata'] = $metadata->toString();

        if (!$table->save($data)) {
            return false;
        }

        $this->saveTags($table->id, $tags);

        // Save revision.
        $this->saveRevision($table->getProperties(), 'save');

        // Send pings and notifications.
        $dbo = $this->getDbo();
        $date = JFactory::getDate();

        if ($table->published &&
            ($table->publish_up == $dbo->getNullDate() || $table->publish_up < $dbo->quote($date->toSql()))
        ) {
            // Send pings to update services.
            $this->updateServices($table);

            // Send pingbacks.
            $this->sendPingbacks($table);

            // Send notifications.
            $this->sendNotifications($table);

            // Export content.
            $this->exportContent($table, $blog);
        }

        $this->setState('item.id', $table->id);

        // Trigger new post published event.
        if ($table->published) {
            JPluginHelper::importPlugin('blogfactory');
            JEventDispatcher::getInstance()->trigger('onPostPublished', array(
                'com_blogfactory',
                $table,
            ));
        }

        return true;
    }

    public function autoSave($data)
    {
        if (!$this->saveRevision($data, 'autosave')) {
            return false;
        }

        $this->setState('item.date', $this->getState('revision')->created_at);

        return true;
    }

    public function previewSave($data)
    {
        if (!$this->saveRevision($data, 'preview')) {
            return false;
        }

        $this->setState('preview.id', $this->getState('revision')->id);

        return true;
    }

    public function getItem()
    {
        $id = JFactory::getApplication()->input->getInt('id');
        $table = $this->getTable('Post', 'BlogFactoryTable');
        $table->load($id);

        $table->isRevision = false;
        $table->isNewerAutosave = false;

        $revisionId = JFactory::getApplication()->input->getInt('revision');
        $revision = $this->getTable('Revision', 'BlogFactoryTable');

        if ($revisionId && $revision->load($revisionId)) {
            if ($revision->post_id == $table->id) {
                $data = array('title' => $revision->title, 'content' => $revision->content);
                $table->bind($data);

                $table->isRevision = true;
                $table->revisionDate = $revision->created_at;
                $table->revisionId = $revisionId;
            }
        }

        // Search for a newer autosave.
        if (!$table->isRevision) {
            $dbo = $this->getDbo();
            $query = $dbo->getQuery(true)
                ->select('r.*')
                ->from('#__com_blogfactory_revisions r')
                ->where('r.post_id = ' . $dbo->quote($id))
                ->where('r.created_at > ' . $dbo->quote($table->updated_at));
            $result = $dbo->setQuery($query)
                ->loadObject();

            if ($result) {
                $table->isNewerAutosave = $result;
            }
        }

        $properties = $table->getProperties(1);
        $item = JArrayHelper::toObject($properties, 'JObject');

        $metadata = new JRegistry($item->metadata);
        $item->metadata = $metadata->toArray();

        return $item;
    }

    public function quickPost($data, $mode)
    {
        // Initialise variables.
        $user = JFactory::getUser();
        $blog = $this->getTable('Blog', 'BlogFactoryTable');
        $post = $this->getTable('Post', 'BlogFactoryTable');

        // Check if title is set.
        if (!isset($data['title']) || '' == $data['title']) {
            $this->setState('error', BlogFactoryText::_('quickpost_save_error_title_not_set'));
            return false;
        }

        // Check if content is set.
        if (!isset($data['content']) || '' == $data['content']) {
            $this->setState('error', BlogFactoryText::_('quickpost_save_error_content_not_set'));
            return false;
        }

        // Load user blog.
        if (!$blog->load(array('user_id' => $user->id))) {
            $this->setState('error', BlogFactoryText::_('post_quickpost_error_blog_not_found'));
            return false;
        }

        $data['blog_id'] = $blog->id;
        $data['user_id'] = $blog->user_id;

        if ('publish' == $mode) {
            $data['published'] = 1;
            $data['publish_up'] = JFactory::getDate()->toSql();
        }

        if (!$post->save($data)) {
            return false;
        }

        $this->setState('item', $post);

        return true;
    }

    public function publish($ids, $published = 0)
    {
        // Initialise variables.
        $user = JFactory::getUser();
        $dbo = $this->getDbo();
        $ids = (array)$ids;

        foreach ($ids as $id) {
            $table = $this->getTable('Post', 'BlogFactoryTable');

            // Load post.
            if (!$id || !$table->load($id)) {
                $this->setState('error', BlogFactoryText::_('postedit_publish_error_post_not_found'));
                return false;
            }

            // Check if the user is allowed to edit the post.
            if ($table->user_id != $user->id) {
                $this->setState('error', BlogFactoryText::_('postedit_publish_error_not_allowed'));
                return false;
            }

            // Check if post is already published / unpublished.
            if (count($ids) == 1 && $table->published != $published) {
                $this->setState('error', BlogFactoryText::plural('postedit_publish_error_post_already_published', $published));
                return false;
            }

            // Post is published, we're trying to unpublish it.
            if ($published) {
                $table->published = 0;
            } // Post is unpublished, we're trying to publish it.
            else {
                $table->published = 1;

                if (is_null($table->publish_up) || $table->publish_up == $dbo->getNullDate()) {
                    $table->publish_up = JFactory::getDate()->toSql();
                }
            }

            if (!$table->store()) {
                return false;
            }
        }

        $this->setState('item', $table);

        return true;
    }

    public function delete($ids)
    {
        // Initialise variables.
        $user = JFactory::getUser();
        $ids = (array)$ids;

        foreach ($ids as $id) {
            $table = $this->getTable('Post', 'BlogFactoryTable');

            // Load post.
            if (!$id || !$table->load($id)) {
                $this->setState('error', BlogFactoryText::_('postedit_error_post_not_found'));
                return false;
            }

            // Check if the user is allowed to edit the post.
            if ($table->user_id != $user->id) {
                $this->setState('error', BlogFactoryText::_('postedit_error_not_allowed'));
                return false;
            }

            if (!$table->delete()) {
                return false;
            }
        }

        return true;
    }

    public function getRevisions()
    {
        $id = JFactory::getApplication()->input->getInt('id');

        $dbo = $this->getDbo();
        $query = $dbo->getQuery(true)
            ->select('r.*')
            ->from('#__com_blogfactory_revisions r')
            ->where('r.post_id = ' . $dbo->quote($id))
            ->order('r.created_at DESC');
        $results = $dbo->setQuery($query)
            ->loadObjectList();

        if ($results && 'autosave' != $results[0]->type) {
            unset($results[0]);
        }

        return $results;
    }

    public function getTags()
    {
        $item = $this->getItem();

        if (!$item->id) {
            return array();
        }

        $dbo = $this->getDbo();
        $query = $dbo->getQuery(true)
            ->select('t.name')
            ->from('#__com_blogfactory_post_tag_map m')
            ->leftJoin('#__com_blogfactory_tags t ON t.id = m.tag_id')
            ->where('m.post_id = ' . $dbo->quote($item->id));
        $results = $dbo->setQuery($query)
            ->loadObjectList('name');

        return array_keys($results);
    }

    public function getExternalSources()
    {
        $settings = JComponentHelper::getParams('com_blogfactory');

        return $settings->get('integrations.extensions', array());
    }

    public function getMediaManagerEnabled()
    {
        $settings = JComponentHelper::getParams('com_blogfactory');

        return $settings->get('user_folder.general.enabled', 1);
    }

    public function saveTags($postId, $tags)
    {
        $array = array();
        $dbo = $this->getDbo();

        // Remove all tags for post.
        $query = $dbo->getQuery(true)
            ->delete()
            ->from('#__com_blogfactory_post_tag_map')
            ->where('post_id = ' . $dbo->quote($postId));
        $dbo->setQuery($query)
            ->execute();

        // Prepare new tags.
        foreach ($tags as $i => $tag) {
            $tag = strtolower(trim(strip_tags($tag)));

            if ('' == $tag) {
                unset($tags[$i]);
            } else {
                $tags[$i] = $tag;
                $array[] = $dbo->quote($tags[$i]);
            }
        }

        if (!$tags) {
            return true;
        }

        $query = $dbo->getQuery(true)
            ->select('t.id, t.name')
            ->from('#__com_blogfactory_tags t')
            ->where('t.name IN (' . implode(',', $array) . ')');
        $results = $dbo->setQuery($query)
            ->loadObjectList('name');

        foreach ($tags as $tag) {
            if (!in_array($tag, array_keys($results))) {
                $table = BlogFactoryTable::getInstance('Tag', 'BlogFactoryTable');
                $table->save(array('name' => $tag));

                $results[$tag] = (object)array('id' => $table->id, 'name' => $table->name);
            }
        }

        // Insert tags for post.
        foreach ($tags as $tag) {
            $query = $dbo->getQuery(true)
                ->insert('#__com_blogfactory_post_tag_map')
                ->set('post_id = ' . $dbo->quote($postId) . ', tag_id = ' . $dbo->quote($results[$tag]->id));
            $dbo->setQuery($query)
                ->execute();
        }

        return true;
    }

    protected function updateServices($table)
    {
        // Check if we already sent the pings.
        if ($table->sent_pings) {
            return true;
        }

        require_once JPATH_COMPONENT_SITE . '/libraries/xmlrpc/xmlrpc.inc';
        require_once JPATH_COMPONENT_SITE . '/libraries/xmlrpc/xmlrpcs.inc';

        $route = BlogFactoryRoute::view('post&alias=' . $table->alias . '&id=' . $table->id, false, -1);
        $settings = JComponentHelper::getParams('com_blogfactory');

        if ($settings->get('comments.pingbacks.update_services.enabled', 1)) {
            $services = $settings->get('comments.pingbacks.update_services.list', 'http://rpc.pingomatic.com');

            foreach (explode("\n", $services) as $service) {
                $service = trim($service);

                if ('' == $service) {
                    continue;
                }

                $service = parse_url($service);

                $m = new xmlrpcmsg('weblogUpdates.ping', array(new xmlrpcval($table->title, 'string'), new xmlrpcval($route, 'string')));
                $c = new xmlrpc_client(isset($service['path']) ? $service['path'] : '/', $service['host'], 80);
                $c->setRequestCompression(null);
                $c->setAcceptedCompression(null);

                //$c->setDebug(2);
                $r = $c->send($m);
                //print $r->faultCode();
            }

            $table->sent_pings = 1;
            $table->store();
        }

        return true;
    }

    protected function sendPingbacks($table)
    {
        $ltrs = '\w';
        $gunk = '/#~:.?+=&%@!\-';
        $punc = '.:?\-;';
        $any = $ltrs . $gunk . $punc;

        preg_match_all("{\b http : [$any] +? (?= [$punc] * [^$any] | $)}x", $table->content, $ping);
        $url = BlogFactoryRoute::view('post&alias=' . $table->alias . '&id=' . $table->id, false, -1);

        $pingbacks = explode("\n", $table->sent_pingbacks);
        $pinged = array();

        foreach ($ping[0] as $link) {
            $link = str_replace('&amp;', '&', $link);

            if (in_array($link, $pingbacks)) {
                continue;
            }

            if ($this->sendPingback($link, $url)) {
                $pinged[] = $link;
            }
        }

        $table->sent_pingbacks = implode("\n", $pinged);
        $table->store();

        return true;
    }

    protected function sendPingback($url, $content)
    {
        $parts = parse_url($url);

        if (!isset($parts['scheme'])) {
            #print "do_send_pingback: failed to get url scheme [".$url."]<br />\n";
            return false;
        }

        if (!in_array($parts['scheme'], array('http', 'https'))) {
            #print "do_send_pingback: url scheme is not http or https [".$url."]<br />\n";
            return false;
        }

        if (!isset($parts['host'])) {
            #print "do_send_pingback: could not get host [".$url."]<br />\n";
            return false;
        }

        $host = $parts['host'];
        $port = 80;

        if (isset($parts['port'])) {
            $port = $parts['port'];
        }

        $path = "/";

        if (isset($parts['path'])) {
            $path = $parts['path'];
        }

        if (isset($parts['query'])) {
            $path .= "?" . $parts['query'];
        }

        if (isset($parts['fragment'])) {
            $path .= "#" . $parts['fragment'];
        }

        $fp = fsockopen($host, $port);
        fwrite($fp, "GET $path HTTP/1.0\r\nHost: $host\r\n\r\n");
        $response = "";

        while (is_resource($fp) && $fp && (!feof($fp))) {
            $response .= fread($fp, 1024);
        }

        fclose($fp);

        $lines = explode("\r\n", $response);
        foreach ($lines as $line) {
            if (false !== strpos($line, "X-Pingback: ")) {
                list($pburl) = sscanf($line, "X-Pingback: %s");
            }
        }

        if (empty($pburl)) {
            #print "Could not get pingback url from [$url].<br />\n";
            return false;
        }

        if (!isset($parts['scheme'])) {
            #print "do_send_pingback: failed to get pingback url scheme [".$pburl."]<br />\n";
            return false;
        }

        if (!in_array($parts['scheme'], array('http', 'https'))) {
            #print "do_send_pingback: pingback url scheme is not http[".$pburl."]<br />\n";
            return false;
        }

        if (!isset($parts['host'])) {
            #print "do_send_pingback: could not get pingback host [".$pburl."]<br />\n";
            return false;
        }

        $parts = parse_url($pburl);

        $host = $parts['host'];
        $port = 80;

        if (isset($parts['port'])) {
            $port = $parts['port'];
        }

        $path = "/";

        if (isset($parts['path'])) {
            $path = $parts['path'];
        }

        if (isset($parts['query'])) {
            $path .= "?" . $parts['query'];
        }

        if (isset($parts['fragment'])) {
            $path .= "#" . $parts['fragment'];
        }

        require_once JPATH_COMPONENT_SITE . '/libraries/xmlrpc/xmlrpc.inc';
        require_once JPATH_COMPONENT_SITE . '/libraries/xmlrpc/xmlrpcs.inc';

        $m = new xmlrpcmsg("pingback.ping", array(new xmlrpcval($content, "string"), new xmlrpcval($url, "string")));
        $c = new xmlrpc_client($path, $host, $port);

        $c->setRequestCompression(null);
        $c->setAcceptedCompression(null);
        #$c->setDebug(2);

        $r = $c->send($m);
        if (!$r->faultCode()) {
            #print "Pingback to $url succeeded.<br >\n\n";
            return true;
        } else {
            #$err = "code ".$r->faultCode()." message ".$r->faultString();
            #print "Pingback to $url failed with error $err.<br >\n\n";

            switch ($r->faultCode()) {
                // Ping has been already sent
                case 48:
                    return true;
                    break;
            }

            return false;
        }
    }

    protected function sendNotifications($table)
    {
        $post = $this->getTable('Post', 'BlogFactoryTable');
        $post->load($table->id);

        if ($post->sent_notifications) {
            return true;
        }

        $notification = BlogFactoryNotification::getInstance(JFactory::getMailer());
        $dbo = $this->getDbo();

        $blog = $this->getTable('Blog', 'BlogFactoryTable');
        $blog->load($table->blog_id);

        $tokens = array(
            'post_link' => '<a href="' . BlogFactoryRoute::view('post&id=' . $table->id . '&alias=' . $table->alias, false, -1) . '">' . $table->title . '</a>',
            'post_title' => $table->title,
            'post_date' => ($table->publish_up == $dbo->getNullDate() || '' == $table->publish_up) ? $table->created_at : $table->publish_up,
            'blog_title' => $blog->title,
            'blog_link' => '<a href="' . BlogFactoryRoute::view('blog&id=' . $blog->id . '&alias=' . $blog->alias, false, -1) . '">' . $blog->title . '</a>',
        );

        // Send notification to administrators.
        $notification->sendGroups('post.add.admins', $tokens);

        // Get subscriptions.
        $subscriptions = $this->getSubscriptions($table->blog_id);

        // Send notification to followers.
        foreach ($subscriptions as $subscription) {
            $notification->send('post.add.followers', $subscription, $tokens);
        }

        $table->sent_notifications = 1;
        $table->store();

        return true;
    }

    protected function getSubscriptions($blogId)
    {
        $dbo = $this->getDbo();

        $query = $dbo->getQuery(true)
            ->select('DISTINCT s.user_id')
            ->from('#__com_blogfactory_subscriptions s')
            ->where('s.blog_id = ' . $dbo->quote($blogId));

        $query->leftJoin('#__com_blogfactory_profiles p ON p.id = s.user_id')
            ->where('(p.subscription_notifications = ' . $dbo->quote(1) . ' OR p.id IS NULL )');

        $results = $dbo->setQuery($query)
            ->loadColumn();

        return $results;
    }

    protected function saveRevision($post, $type = 'save')
    {
        // Initialise variables.
        $revision = $this->getTable('Revision', 'BlogFactoryTable');

        // Check if post id is set if we're autosaving.
        if (in_array($type, array('autosave', 'preview')) && !$post['id']) {
            return false;
        }

        // Check if another revision with same content and type already exists.
        $dbo = $this->getDbo();
        $query = $dbo->getQuery(true)
            ->select('r.id, r.title, r.content')
            ->from('#__com_blogfactory_revisions r')
            ->where('r.post_id = ' . $dbo->quote((int)$post['id']))
            ->where('r.type = ' . $dbo->quote($type))
            ->order('r.id DESC');
        $result = $dbo->setQuery($query)
            ->loadObject();

        if ('preview' != $type && $result && $post['title'] == $result->title && $post['content'] == $result->content) {
            return false;
        }

        // Prepare data for save.
        $data = array(
            'post_id' => $post['id'],
            'type' => $type,
            'title' => $post['title'],
            'content' => $post['content'],
            'created_at' => 'save' == $type ? $post['updated_at'] : JFactory::getDate()->toSql(),
        );

        // If we're autosaving or previewing, overwrite the previous autosave.
        if (in_array($type, array('autosave', 'preview')) && $result) {
            $data['id'] = $result->id;
        }

        // Save revision.
        if (!$revision->save($data)) {
            return false;
        }

        $this->setState('revision', $revision);

        return true;
    }

    public function exportContent($table, $blog)
    {
        // Initialise variables.
        $settings = JComponentHelper::getParams('com_blogfactory');
        $settingsExport = (array)$settings->get('export');
        $blogExport = new JRegistry($blog->export ? $blog->export : '{"enable":""}');
        $options = new stdClass();

        // Get export settings.
        foreach ($blogExport->toArray() as $option => $value) {
            $options->$option = '' === $value ? $settingsExport[$option] : $value;
        }

        // Check if export is enabled for this blog.
        if (!$options->enable) {
            return true;
        }

        JLoader::register('BlogFactoryHelper', JPATH_COMPONENT_ADMINISTRATOR . '/helpers/blogfactory.php');

        return BlogFactoryHelper::exportPostToContent($table->id, $options);
    }
}
