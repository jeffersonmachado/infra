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

?>

<table class="table">
    <tbody>
    <tr>
        <td><?php echo BlogFactoryText::plural('dashboard_statistics_blogs_this_week', $this->statistics['blogs_this_week']); ?></td>
        <td><?php echo BlogFactoryText::plural('dashboard_statistics_blogs_total', $this->statistics['blogs_total']); ?></td>
    </tr>

    <tr>
        <td><?php echo BlogFactoryText::plural('dashboard_statistics_posts_this_week', $this->statistics['posts_this_week']); ?></td>
        <td><?php echo BlogFactoryText::plural('dashboard_statistics_posts_total', $this->statistics['posts_total']); ?></td>
    </tr>

    <tr>
        <td><?php echo BlogFactoryText::plural('dashboard_statistics_comments_this_week', $this->statistics['comments_this_week']); ?></td>
        <td><?php echo BlogFactoryText::plural('dashboard_statistics_comments_total', $this->statistics['comments_total']); ?></td>
    </tr>
    </tbody>
</table>
