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

function getBlogFactoryRoutes()
{
    $routes = array(
        'blog' => array('view' => 'blog', 'params' => array('id', 'alias')),
        'post' => array('view' => 'post', 'params' => array('id', 'alias')),
        'report' => array('view' => 'report', 'params' => array('type', 'id')),
        'blog-edit' => array('view' => 'blogedit'),
        'post-edit' => array('view' => 'postedit', 'params' => array('id')),
        'post-write' => array('view' => 'postedit'),
        'manage-posts' => array('view' => 'manageposts'),
        'manage-comments' => array('view' => 'managecomments'),
        'manage-comment' => array('view' => 'managecomment', 'params' => array('id')),
        'manage-bookmarks' => array('view' => 'managebookmarks'),
        'manage-followers' => array('view' => 'managefollowers'),

        //'comment-delete' => array('task' => 'comment.delete', 'params' => array('id')),
        'comment-delete' => array('task' => 'managecomment.delete', 'params' => array('id')),
        'comment-update' => array('task' => 'managecomment.save', 'params' => array('id')),
        'send-report' => array('task' => 'report.send'),
        'comment-save' => array('task' => 'comment.save'),
        'blog-save' => array('task' => 'blog.save'),
        'post-save' => array('task' => 'post.save'),
        'blog-bookmark' => array('task' => 'blog.bookmark', 'params' => array('id')),
        'comment-approve' => array('task' => 'comment.approve', 'params' => array('id')),
        'comment-resolve' => array('task' => 'comment.resolve', 'params' => array('id')),
        'quickpost-save' => array('task' => 'post.quickpost', 'params' => array('mode')),
        'post-publish' => array('task' => 'post.publish', 'params' => array('id')),
        'post-delete' => array('task' => 'post.delete', 'params' => array('id')),
        'posts-delete' => array('task' => 'manageposts.delete'),
        'posts-publish' => array('task' => 'manageposts.publish'),
        'posts-unpublish' => array('task' => 'manageposts.unpublish'),
        'bookmark-delete' => array('task' => 'managebookmark.delete', 'params' => array('id')),
        'bookmark-subscribe' => array('task' => 'managebookmark.subscribe', 'params' => array('id')),
        'post-vote' => array('task' => 'post.vote', 'params' => array('id', 'vote')),
        'profile-save' => array('task' => 'profile.save'),
    );

    return $routes;
}

function BlogFactoryBuildRoute(&$query)
{
    if (!isset($query['view']) && !isset($query['task'])) {
        return array();
    }

    $routes = getBlogFactoryRoutes();
    $segments = array();

    foreach ($routes as $alias => $route) {
        if (isset($query['view'])) {
            if (!isset($route['view']) || $route['view'] != $query['view']) {
                continue;
            }
        } elseif (isset($query['task'])) {
            if (!isset($route['task']) || $route['task'] != $query['task']) {
                continue;
            }
        }

        $valid = true;

        if (isset($route['params'])) {
            foreach ($route['params'] as $type => $param) {
                if (!isset($query[$param])) {
                    if ('optional' !== $type) {
                        $valid = false;
                        break;
                    }
                }
            }
        }

        if (!$valid) {
            continue;
        }

        $segments[] = $alias;
        if (isset($query['view'])) {
            unset($query['view']);
        } else {
            unset($query['task']);
        }

        if (isset($route['params'])) {
            foreach ($route['params'] as $param) {
                if (isset($query[$param])) {
                    $segments[] = $query[$param];
                    unset($query[$param]);
                }
            }
        }

        break;
    }

    // Set default values if no route found.
    if (!$segments) {
        // Only do this for views.
        if (isset($query['view'])) {
            $segments[] = $query['view'];
            unset($query['view']);
        }
    }

    return $segments;
}

function BlogFactoryParseRoute($segments)
{
    $routes = getBlogFactoryRoutes();
    $vars = array();

    foreach ($segments as $i => $segment) {
        $segments[$i] = str_replace(':', '-', $segment);
    }

    if (array_key_exists($segments[0], $routes)) {
        $route = $routes[$segments[0]];

        if (isset($route['view'])) {
            $vars['view'] = $route['view'];
        } else {
            $vars['task'] = $route['task'];
        }

        if (isset($route['params'])) {
            $i = 1;
            foreach ($route['params'] as $type => $param) {
                if (isset($segments[$i])) {
                    $vars[$param] = $segments[$i];
                }

                $i++;
            }
        }
    }

    // Set default values if no route found.
    if (!$vars) {
        // We're assuming the route is a view.
        $vars['view'] = $segments[0];
    }

    return $vars;
}
