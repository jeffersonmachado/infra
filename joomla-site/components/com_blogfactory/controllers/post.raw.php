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

class BlogFactoryFrontendControllerPost extends JControllerLegacy
{
    public function quickPost()
    {
        $model = $this->getModel('PostEdit');
        $data = $this->input->post->get('quickpost', array(), 'array');
        $mode = $this->input->getString('mode');
        $response = array();

        if ($model->quickPost($data, $mode)) {
            $response['status'] = 1;
            $response['message'] = BlogFactoryText::_('post_task_quickpost_success');
            $response['portlet_posts_update'] = JHtml::_('BlogFactoryDashboard.getPortletPostsItem', $model->getState('item'));
        } else {
            $response['status'] = 0;
            $response['message'] = BlogFactoryText::_('post_task_quickpost_error');
            $response['error'] = $model->getState('error');
        }

        echo json_encode($response);

        jexit();
    }

    public function publish()
    {
        $model = $this->getModel('PostEdit');
        $id = $this->input->getInt('id');
        $published = $this->input->post->getInt('published');
        $response = array();

        if ($model->publish($id, $published)) {
            $response['status'] = 1;
            $response['message'] = BlogFactoryText::plural('post_task_publish_success', $published);
            $response['text'] = BlogFactoryText::plural('manageposts_post_publish', !$published);
            $response['date'] = JHtml::_('BlogFactoryManagePosts.date', $model->getState('item'));
        } else {
            $response['status'] = 0;
            $response['message'] = BlogFactoryText::plural('post_task_publish_error', $published);
            $response['error'] = $model->getState('error');
        }

        echo json_encode($response);

        jexit();
    }

    public function delete()
    {
        $model = $this->getModel('PostEdit');
        $id = $this->input->getInt('id');
        $response = array();

        if ($model->delete($id)) {
            $response['status'] = 1;
            $response['message'] = BlogFactoryText::_('post_task_delete_success');
        } else {
            $response['status'] = 0;
            $response['message'] = BlogFactoryText::_('post_task_delete_error');
            $response['error'] = $model->getState('error');
        }

        echo json_encode($response);

        jexit();
    }

    public function autoSave()
    {
        $data = $this->input->post->get('jform', array(), 'array');
        $model = $this->getModel('PostEdit');
        $response = array();

        if ($model->autoSave($data)) {
            $response['status'] = 1;
            $response['date'] = BlogFactoryText::sprintf('post_autosave_date', JHtml::_('date', $model->getState('item.date'), 'DATE_FORMAT_LC2'));
        } else {
            $response['status'] = 0;
        }

        echo json_encode($response);

        jexit();
    }

    public function preview()
    {
        $data = $this->input->post->get('jform', array(), 'array');
        $model = $this->getModel('PostEdit');
        $response = array();

        if ($model->previewSave($data)) {
            $response['status'] = 1;
            $response['preview'] = $model->getState('preview.id');
        } else {
            $response['status'] = 0;
        }

        echo json_encode($response);

        jexit();
    }

    public function vote()
    {
        $id = $this->input->getInt('id');
        $vote = $this->input->getString('vote');
        $model = $this->getModel('Post');
        $response = array();

        $vote = 'up' == $vote ? 1 : -1;

        if ($model->vote($id, $vote)) {
            $response['status'] = 1;
            $response['message'] = BlogFactoryText::_('post_task_vote_success');
            $response['vote'] = 1 == $vote ? 'up' : 'down';
            $response['total'] = $model->getState('votes.total');
        } else {
            $response['status'] = 0;
            $response['message'] = BlogFactoryText::_('post_task_vote_error');
            $response['error'] = $model->getState('error');
        }

        echo json_encode($response);

        jexit();
    }

    public function pingback()
    {
        function process($m)
        {
            $x1 = $m->getParam(0);
            $x2 = $m->getParam(1);
            $source = $x1->scalarval(); # their link
            $dest = $x2->scalarval(); # our link

            $source = htmlspecialchars_decode($source);

            // Check if the link is ours
            $url = @str_replace(':' . $_SERVER['SERVER_PORT'], '', $_SERVER['HTTP_HOST']);
            $position = @strpos($dest, $url);
            if ($position === false) {
                return new xmlrpcresp(0, 16, 'The link is not ours' . $url);
            }

            // Check if the source exists
            $content = file_get_contents($source);
            if ($content === false) {
                return new xmlrpcresp(0, 16, 'The source URI does not exist.');
            }

            // Check if the source has a link to our site
            $text = findLink($content, $dest);
            if ($text === false) {
                return new xmlrpcresp(0, 17, 'The source URI does not contain a link to the target URI, and so cannot be used as a source.');
            }

            // Check if the blog post exists
            $post = getPost($dest);

            if (!$post) {
                return new xmlrpcresp(0, 32, 'Target uri does not exist');
            }

            // Check if the blog post has pingbacks enabled
            if (!$post->pingbacks) {
                return new xmlrpcresp(0, 33, 'Target uri cannot be used as target');
            }

            // Get page title
            $title = getPageTitle($content, $source);

            $table = JTable::getInstance('Comment', 'BlogFactoryTable');
            $data = array(
                'post_id' => $post->id,
                'email' => 'pingback',
                'url' => $source,
            );

            // Check if the ping has been already registered
            if ($table->load($data)) {
                return new xmlrpcresp(0, 48, 'The pingback has already been registered.');
            }

            // Register the pingback
            $table = JTable::getInstance('Comment', 'BlogFactoryTable');
            $data = array(
                'post_id' => $post->id,
                'name' => $title,
                'email' => 'pingback',
                'url' => $source,
                'text' => $text,
            );

            if ($table->save($data)) {
                return new xmlrpcresp(new xmlrpcval('Pingback registered. May the force be with you.', 'string'));
            } else {
                return new xmlrpcresp(0, 49, 'Access denied.');
            }
        }

        function getPageTitle($content, $url)
        {
            if (preg_match('/(<title>)(.*)(<\/title>)/', $content, $title)) {
                return $title[2];
            }

            $url = parse_url($url);

            return $url['scheme'] . '://' . $url['host'];
        }

        function findLink($content, $dest)
        {
            $search = array(
                '@<script[^>]*?>.*?</script>@si',
                '@<style[^>]*?>.*?</style>@siU',
                '@<![\s\S]*?--[ \t\n\r]*>@'
            );
            $content = preg_replace($search, '', $content);
            $content = strip_tags($content, '<a>');
            $content = preg_replace('/[\s\r\n\t]+/', ' ', $content);
            $content = htmlspecialchars_decode($content);

            $found = false;

            if (preg_match_all('/<a\s[^>]*href=([\"\']??)([^\" >]*?)\\1[^>]*>(.*)<\/a>/siU', $content, $links, PREG_PATTERN_ORDER)) {
                foreach ($links[2] as $i => $link) {
                    if ($link == $dest) {
                        $content = str_replace($links[0][$i], '<link>' . $links[3][$i] . '</link>', $content);
                        $found = true;
                        break;
                    }
                }
            }

            if (!$found) {
                return false;
            }

            $content = strip_tags($content, '<link>');
            if (preg_match_all('/\s(.{0,50}<link>.*<\/link>.{0,50})\s/', $content, $links, PREG_PATTERN_ORDER)) {
                return '[...] ' . strip_tags(trim($links[0][0])) . ' [...]';
            }

            return false;
        }

        function getPost($url)
        {
            $router = new JRouterSite(array(
                'mode' => JFactory::getApplication()->get('sef')
            ));

            // Get the full request URI.
            $uri = new JURI($url);

            $result = $router->parse($uri);

            if (!isset($result['option']) || 'com_blogfactory' != $result['option']) {
                return false;
            }

            if (!isset($result['view']) || 'post' != $result['view']) {
                return false;
            }

            if (!isset($result['id']) || !$result['id']) {
                return false;
            }

            JTable::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . '/tables');
            $table = JTable::getInstance('Post', 'BlogFactoryTable');

            if (!$table->load($result['id'])) {
                return false;
            }

            return $table;
        }

        if ('POST' != $this->input->getMethod()) {
            die('XML-RPC server accepts POST requests only.');
        }

        require_once JPATH_COMPONENT_SITE . '/libraries/xmlrpc/xmlrpc.inc';
        require_once JPATH_COMPONENT_SITE . '/libraries/xmlrpc/xmlrpcs.inc';

        $a = array('pingback.ping' => array('function' => 'process'));

        $s = new xmlrpc_server($a, false);
        #$s->setdebug(2);
        $s->service();

        jexit();
    }
}
