<?php
/**
* @version $Id: mod_jdownloads_admin_stats.php v3.2
* @package mod_jdownloads_admin_stats
* @copyright (C) 2016 Arno Betz
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @author Arno Betz http://www.jDownloads.com
*/

defined( '_JEXEC' ) or die( 'Restricted access' );

    // fix for Joomla 3 missing CSS tabs when creating tabs
	$document = JFactory::getDocument();
	$document->addStyleDeclaration('
		dl.tabs {
           float:left;
           margin:10px 0 -1px 0;
           z-index:50;
        }
		dl.tabs dt {
            float:left;
            padding:4px 10px;
            border:1px solid #ccc;margin-left:3px;
            background:#F9F9F9;
            color:#666;
        }
		dl.tabs dt.open {
            background:#d4ffff;
            border-bottom:1px solid #f9f9f9;
            z-index:100;
            color:#000;
        }
		div.current {
            clear:both;
            border:1px solid #ccc;
            padding:10px 10px;
        }
		dl.tabs h3 {
            font-size:12px;
            line-height:12px;
            margin:4px;
        }
        ');
        
    // Import Joomla! tabs
    jimport('joomla.html.pane');

?>

<div class="clr"></div>

<?php echo JHtml::_('tabs.start'); ?>

<?php if($params->get('view_latest', 1)){ ?>
    <?php echo JHtml::_('tabs.panel', JText::_('MOD_JDOWNLOADS_ADMIN_STATS_LATEST_ITEMS'), 'latestItemsTab'); ?>
        <table class="adminlist table table-striped">
	        <thead>
		        <tr>
			        <td class="title"><?php echo JText::_('MOD_JDOWNLOADS_ADMIN_STATS_TITLE'); ?></td>
			        <td class="title"><?php echo JText::_('MOD_JDOWNLOADS_ADMIN_STATS_CATEGORY'); ?></td>
                    <td class="title"><?php echo JText::_('MOD_JDOWNLOADS_ADMIN_STATS_CREATED'); ?></td>
			        <td class="title"><?php echo JText::_('MOD_JDOWNLOADS_ADMIN_STATS_CREATED_BY'); ?></td>
		        </tr>
	        </thead>
	        <tbody>
		        <?php foreach($latest_items as $latest): ?>
		        <tr>
			        <td><a href="<?php echo JRoute::_('index.php?option=com_jdownloads&task=download.edit&file_id='.$latest->file_id); ?>"><?php echo $latest->file_title; ?></a></td>
			        <?php if ($latest->cat_id > 1){ ?>
                        <td><a href="<?php echo JRoute::_('index.php?option=com_jdownloads&task=category.edit&id='.$latest->cat_id); ?>"><?php echo $latest->cat_title; ?></a></td>
                    <?php } else { ?>
                        <td><?php echo JText::_('MOD_JDOWNLOADS_ADMIN_STATS_CATEGORY_UNCATEGORISED'); ?></td>
                    <?php } ?>
                    <td><?php echo JHTML::_('date', $latest->date_added , JText::_('DATE_FORMAT_LC3')); ?></td>
			        <td><?php echo $latest->author; ?></td>
		        </tr>
		        <?php endforeach; ?>
	        </tbody>
        </table>
<?php } ?>

<?php if($params->get('view_popular', 1)){ ?>
    <?php echo JHtml::_('tabs.panel', JText::_('MOD_JDOWNLOADS_ADMIN_STATS_POPULAR_ITEMS'), 'popularItemsTab'); ?>
        <table class="adminlist table table-striped">
	        <thead>
		        <tr>
			        <td class="title"><?php echo JText::_('MOD_JDOWNLOADS_ADMIN_STATS_TITLE'); ?></td>
                    <td class="title"><?php echo JText::_('MOD_JDOWNLOADS_ADMIN_STATS_CATEGORY'); ?></td>
			        <td class="title"><?php echo JText::_('MOD_JDOWNLOADS_ADMIN_STATS_HITS'); ?></td>
			        <td class="title"><?php echo JText::_('MOD_JDOWNLOADS_ADMIN_STATS_CREATED'); ?></td>
			        <td class="title"><?php echo JText::_('MOD_JDOWNLOADS_ADMIN_STATS_CREATED_BY'); ?></td>
		        </tr>
	        </thead>
	        <tbody>
		        <?php foreach($popular_items as $popular): ?>
		        <tr>
			        <td><a href="<?php echo JRoute::_('index.php?option=com_jdownloads&task=download.edit&file_id='.$popular->file_id); ?>"><?php echo $popular->file_title; ?></a></td>
                    <?php if ($popular->cat_id > 1){ ?>
                        <td><a href="<?php echo JRoute::_('index.php?option=com_jdownloads&task=category.edit&id='.$popular->cat_id); ?>"><?php echo $popular->cat_title; ?></a></td>
                    <?php } else { ?>
                        <td><?php echo JText::_('MOD_JDOWNLOADS_ADMIN_STATS_CATEGORY_UNCATEGORISED'); ?></td>
                    <?php } ?>                    
			        <td><?php echo $popular->downloads; ?></td>
			        <td><?php echo JHTML::_('date', $popular->date_added , JText::_('DATE_FORMAT_LC3')); ?></td>
			        <td><?php echo $popular->author; ?></td>
		        </tr>
		        <?php endforeach; ?>
	        </tbody>
        </table>
<?php } ?>

<?php if($params->get('view_featured', 1)){ ?>
    <?php echo JHtml::_('tabs.panel', JText::_('MOD_JDOWNLOADS_ADMIN_STATS_FEATURED_ITEMS'), 'featuredItemsTab'); ?>
        <table class="adminlist table table-striped">
            <thead>
                <tr>
                    <td class="title"><?php echo JText::_('MOD_JDOWNLOADS_ADMIN_STATS_TITLE'); ?></td>
                    <td class="title"><?php echo JText::_('MOD_JDOWNLOADS_ADMIN_STATS_CATEGORY'); ?></td>
                    <td class="title"><?php echo JText::_('MOD_JDOWNLOADS_ADMIN_STATS_CREATED'); ?></td>
                    <td class="title"><?php echo JText::_('MOD_JDOWNLOADS_ADMIN_STATS_CREATED_BY'); ?></td>
                </tr>
            </thead>
            <tbody>
                <?php foreach($featured_items as $featured): ?>
                <tr>
                    <td><a href="<?php echo JRoute::_('index.php?option=com_jdownloads&task=download.edit&file_id='.$featured->file_id); ?>"><?php echo $featured->file_title; ?></a></td>
                    <?php if ($featured->cat_id > 1){ ?>
                        <td><a href="<?php echo JRoute::_('index.php?option=com_jdownloads&task=category.edit&id='.$featured->cat_id); ?>"><?php echo $featured->cat_title; ?></a></td>
                    <?php } else { ?>
                        <td><?php echo JText::_('MOD_JDOWNLOADS_ADMIN_STATS_CATEGORY_UNCATEGORISED'); ?></td>
                    <?php } ?>                    
                    <td><?php echo JHTML::_('date', $featured->date_added , JText::_('DATE_FORMAT_LC3')); ?></td>
                    <td><?php echo $featured->author; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
<?php } ?>

<?php if($params->get('view_most_rated', 1)){ ?>
    <?php echo JHtml::_('tabs.panel', JText::_('MOD_JDOWNLOADS_ADMIN_STATS_MOST_RATED_ITEMS'), 'mostRatedItemsTab'); ?>
        <table class="adminlist table table-striped">
	        <thead>
		        <tr>
                    <td class="title"><?php echo JText::_('MOD_JDOWNLOADS_ADMIN_STATS_COUNT'); ?></td>
			        <td class="title"><?php echo JText::_('MOD_JDOWNLOADS_ADMIN_STATS_TITLE'); ?></td>
                    <td class="title"><?php echo JText::_('MOD_JDOWNLOADS_ADMIN_STATS_CATEGORY'); ?></td>
                    <td class="title"><?php echo JText::_('MOD_JDOWNLOADS_ADMIN_STATS_CREATED'); ?></td>
		        </tr>
	        </thead>
	        <tbody>
		        <?php foreach($most_rated_items as $most_rated): ?>
		        <tr>
                    <td style="width:12%;"><b><?php echo $most_rated->rating_count; ?></b></td>
			        <td style="width:30%;"><a href="<?php echo JRoute::_('index.php?option=com_jdownloads&task=download.edit&file_id='.$most_rated->file_id); ?>"><?php echo $most_rated->file_title; ?></a></td>
                    <?php if ($most_rated->cat_id > 1){ ?>
                        <td style="width:30%;"><a href="<?php echo JRoute::_('index.php?option=com_jdownloads&task=category.edit&id='.$most_rated->cat_id); ?>"><?php echo $most_rated->cat_title; ?></a></td>
                    <?php } else { ?>
                        <td><?php echo JText::_('MOD_JDOWNLOADS_ADMIN_STATS_CATEGORY_UNCATEGORISED'); ?></td>
                    <?php } ?> 			        
			        <td><?php echo JHTML::_('date', $most_rated->date_added , JText::_('DATE_FORMAT_LC3')); ?></td>
		        </tr>
		        <?php endforeach; ?>
	        </tbody>
        </table>
<?php } ?>

<?php if($params->get('view_top_rated', 1)){ ?>
    <?php echo JHtml::_('tabs.panel', JText::_('MOD_JDOWNLOADS_ADMIN_STATS_TOP_RATED_ITEMS'), 'topRatedItemsTab'); ?>
        <table class="adminlist table table-striped">
	        <thead>
		        <tr>
                    <td class="title"><?php echo JText::_('MOD_JDOWNLOADS_ADMIN_STATS_TITLE'); ?></td>
                    <td class="title"><?php echo JText::_('MOD_JDOWNLOADS_ADMIN_STATS_CATEGORY'); ?></td>
                    <td class="title"><?php echo JText::_('MOD_JDOWNLOADS_ADMIN_STATS_CREATED'); ?></td>
                    <td class="title"><?php echo JText::_('MOD_JDOWNLOADS_ADMIN_STATS_CREATED_BY'); ?></td>
		        </tr>
	        </thead>
	        <tbody>
		        <?php foreach($top_rated_items as $top_rated): ?>
                <tr>
                    <td><a href="<?php echo JRoute::_('index.php?option=com_jdownloads&task=download.edit&file_id='.$top_rated->file_id); ?>"><?php echo $top_rated->file_title; ?></a></td>
                    <?php if ($top_rated->cat_id > 1){ ?>
                        <td><a href="<?php echo JRoute::_('index.php?option=com_jdownloads&task=category.edit&id='.$top_rated->cat_id); ?>"><?php echo $top_rated->cat_title; ?></a></td>
                    <?php } else { ?>
                        <td><?php echo JText::_('MOD_JDOWNLOADS_ADMIN_STATS_CATEGORY_UNCATEGORISED'); ?></td>
                    <?php } ?>                     
                    <td><?php echo JHTML::_('date', $top_rated->date_added , JText::_('DATE_FORMAT_LC3')); ?></td>
                    <td><?php echo $top_rated->author; ?></td>
                </tr>
		        <?php endforeach; ?>
	        </tbody>
        </table>
<?php } ?>

<?php if($params->get('view_statistics', 1)){ ?>
    <?php echo JHtml::_('tabs.panel', JText::_('MOD_JDOWNLOADS_ADMIN_STATS_STATISTICS'), 'statsTab'); ?>
        <table class="adminlist table table-striped">
	        <thead>
		        <tr>
			        <td class="title"><?php echo JText::_('MOD_JDOWNLOADS_ADMIN_STATS_TYPE'); ?></td>
			        <td class="title"><?php echo JText::_('MOD_JDOWNLOADS_ADMIN_STATS_AMOUNT_ITEMS_LABEL'); ?></td>
		        </tr>
	        </thead>
	        <tbody>
                <tr>
                    <td><?php echo JText::_('MOD_JDOWNLOADS_ADMIN_STATS_CATEGORIES'); ?></td>
                    <td><?php echo ($statistics->num_categories - 1); ?> (<?php echo $statistics->num_unpublished_categories.' '.JText::_('MOD_JDOWNLOADS_ADMIN_STATS_UNPUBLISHED'); ?>)</td>
                </tr>
		        <tr>
			        <td><?php echo JText::_('MOD_JDOWNLOADS_ADMIN_STATS_DOWNLOADS'); ?></td>
			        <td><?php echo $statistics->num_downloads; ?> (<?php echo $statistics->num_unpublished_downloads.' '.JText::_('MOD_JDOWNLOADS_ADMIN_STATS_UNPUBLISHED'); ?>)</td>
		        </tr>
                <tr>
                    <td><?php echo JText::_('MOD_JDOWNLOADS_ADMIN_STATS_FEATURED'); ?></td>
                    <td><?php echo $statistics->num_featured; ?></td>
                </tr>

		        <tr>
			        <td><?php echo JText::_('MOD_JDOWNLOADS_ADMIN_STATS_TAGS'); ?></td>
			        <td><?php echo $statistics->num_tags; ?></td>
		        </tr>
	        </tbody>
        </table>
<?php } ?>
<?php echo JHtml::_('tabs.end'); ?>
