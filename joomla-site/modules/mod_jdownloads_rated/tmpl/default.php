<?php
/**
* @version $Id: mod_jdownloads_rated.php
* @package mod_jdownloads_rated
* @copyright (C) 2016 Arno Betz
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @author Arno Betz http://www.jDownloads.com
*/

defined('_JEXEC') or die;

    $document = JFactory::getDocument();
    //$document->addScript(JURI::base().'components/com_jdownloads/assets/rating/js/ajaxvote.js');
    $document->addStyleSheet( JURI::base()."components/com_jdownloads/assets/rating/css/ajaxvote.css", 'text/css', null, array() );    

    $html = '<table class="moduletable'.$moduleclass_sfx.'" style="width: 100%;">';
    
    if ($files){
        if ($text_before <> ''){
            $html .= '<tr><td>'.$text_before.'</td></tr>';
        }
        for ($i=0; $i<count($files); $i++) {
            
            $has_no_file = false;
            
            if (!$files[$i]->url_download && !$files[$i]->other_file_id && !$files[$i]->extern_file){
               // only a document without file
               $has_no_file = true;           
            }
            
            $version = $short_version; 
            if ($sum_char > 0){
                $total = strlen($files[$i]->file_title) + strlen($files[$i]->release) + strlen($short_version) +1;
                if ($total > $sum_char){
                   $files[$i]->file_title = JString::substr($files[$i]->file_title, 0, $sum_char).$short_char;
                   $files[$i]->release = '';
                }    
            }

            $db->setQuery("SELECT id from #__menu WHERE link = 'index.php?option=com_jdownloads&view=category&catid=".$files[$i]->cat_id."' and published = 1");
            $Itemid = $db->loadResult();
            if (!$Itemid){
                $Itemid = $root_itemid;
            } 
                            
            // create the link
            if ($files[$i]->link == '-'){
                // the user have the access to view this item
                if ($detail_view == '1'){
                    if ($detail_view_config == 0){                    
                        // the details view is deactivated in jD config so the
                        // link must start directly the download process
                        if ($direct_download_config == 1){
                            if (!$has_no_file){
                                $link = JRoute::_('index.php?option='.$option.'&amp;task=download.send&amp;id='.$files[$i]->slug.'&amp;catid='.$files[$i]->cat_id.'&amp;m=0');                    
                            } else {
                                // create a link to the Downloads category as this download has not a file
                                if ($files[$i]->menu_cat_itemid){
                                    $link = JRoute::_('index.php?option='.$option.'&amp;view=category&catid='.$files[$i]->cat_id.'&amp;Itemid='.$files[$i]->menu_cat_itemid);
                                } else {
                                    $link = JRoute::_('index.php?option='.$option.'&amp;view=category&catid='.$files[$i]->cat_id.'&amp;Itemid='.$Itemid);
                                }
                            }   
                        } else {
                            // link to the summary page
                            if (!$has_no_file){
                                $link = JRoute::_('index.php?option='.$option.'&amp;view=summary&amp;id='.$files[$i]->slug.'&amp;catid='.$files[$i]->cat_id);
                            } else {
                                // create a link to the Downloads category as this download has not a file
                                if ($files[$i]->menu_cat_itemid){
                                    $link = JRoute::_('index.php?option='.$option.'&amp;view=category&catid='.$files[$i]->cat_id.'&amp;Itemid='.$files[$i]->menu_cat_itemid);
                                } else {
                                    $link = JRoute::_('index.php?option='.$option.'&amp;view=category&catid='.$files[$i]->cat_id.'&amp;Itemid='.$Itemid);
                                }
                            }   
                        }    
                    } else {
                        // create a link to the details view
                        if ($files[$i]->menu_itemid){
                            $link = JRoute::_('index.php?option='.$option.'&amp;view=download&id='.$files[$i]->slug.'&catid='.$files[$i]->cat_id.'&amp;Itemid='.$files[$i]->menu_itemid);                    
                        } else {
                            $link = JRoute::_('index.php?option='.$option.'&amp;view=download&id='.$files[$i]->slug.'&catid='.$files[$i]->cat_id.'&amp;Itemid='.$Itemid);                    
                        }
                    }                       
                } else {    
                    // create a link to the Downloads category
                    if ($files[$i]->menu_cat_itemid){
                        $link = JRoute::_('index.php?option='.$option.'&amp;view=category&catid='.$files[$i]->cat_id.'&amp;Itemid='.$files[$i]->menu_cat_itemid);
                    } else {
                        $link = JRoute::_('index.php?option='.$option.'&amp;view=category&catid='.$files[$i]->cat_id.'&amp;Itemid='.$Itemid);
                    }
                }    
            } else {
                $link = $files[$i]->link;
            }            
            
            if (!$files[$i]->release) $version = '';

            // build file pic
            $size = 0;
            $files_pic = '';
            $number = '';
            if ($view_pics){
                $size = (int)$view_pics_size;
                $files_pic = '<img src="'.JURI::base().'images/jdownloads/fileimages/'.$files[$i]->file_pic.'" align="top" width="'.$size.'" height="'.$size.'" border="0" alt="" /> '; 
            }
            
            // build number list
            if ($view_numerical_list){
                $num = $i+1;
                $number = "$num. ";
            }
            
            $link_text = '<a href="'.$link.'">'.$files[$i]->file_title.' '.$version.$files[$i]->release.'</a>';
            
            // view star rating values
            $rating_value ='
                    <div class="jwajaxvote-inline-rating">
                    <ul class="jwajaxvote-star-rating">
                    <li class="current-rating" style="width:'.$files[$i]->ratenum.'%;"></li>
                    <li><a href="javascript:void(null)" onclick="" title="1 '.JText::_('MOD_JDOWNLOADS_RATED_JDVOTE_STAR').' 5" class="one-star"></a></li>
                    <li><a href="javascript:void(null)" onclick="" title="2 '.JText::_('MOD_JDOWNLOADS_RATED_JDVOTE_STARS').' 5" class="two-stars"></a></li>
                    <li><a href="javascript:void(null)" onclick="" title="3 '.JText::_('MOD_JDOWNLOADS_RATED_JDVOTE_STARS').' 5" class="three-stars"></a></li>
                    <li><a href="javascript:void(null)" onclick="" title="4 '.JText::_('MOD_JDOWNLOADS_RATED_JDVOTE_STARS').' 5" class="four-stars"></a></li>
                    <li><a href="javascript:void(null)" onclick="" title="5 '.JText::_('MOD_JDOWNLOADS_RATED_JDVOTE_STARS').' 5" class="five-stars"></a></li>
                    </ul>
                    <div class="jwajaxvote-box">
                    ';
                          
            if ($view_stars_rating_count){
                if ($files[$i]->rating_count != 1) {
                    $rating_value .= "<small>(".$files[$i]->rating_count." ".JText::_('MOD_JDOWNLOADS_RATED_JDVOTE_VOTES').")</small>";
                } else { 
                    $rating_value .= "<small>(".$files[$i]->rating_count." ".JText::_('MOD_JDOWNLOADS_RATED_JDVOTE_VOTE').")</small>";
                }
            } 
            $rating_value .= '
                </div>
                </div>
                <div class="jwajaxvote-clr"></div>
                ';
            
            $html .= '<tr style="vertical-align:top"><td style="text-align:'.$alignment.'">'.$number.$files_pic.$link_text.'</td>';
            if ($view_stars){
                if ($view_stars_new_line){
                    $html .= '</tr><tr><td>'.$rating_value.'</td></tr>';
                } else { 
                    $html .= '<td>'.$rating_value.'</td></tr>';
                }    
            } else {
                $html .= '</tr>';
            }   
        }
        if ($text_after <> ''){
            $html .= '<tr><td>'.$text_after.'</td></tr>'; 
        }
    } else {
        $html .= '</table>'; 
    }    
    echo $html.'</table>';
         
?>