<?php
/*
 * @package Joomla
 * @copyright Copyright (C) 2005 Open Source Matters. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 *
 * @component jDownloads
 * @version 2.0  
 * @copyright (C) 2007 - 2012 - Arno Betz - www.jdownloads.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 * 
 * jDownloads is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */
 // updated 2016-10-06 ColinM

defined( '_JEXEC' ) or die( 'Restricted access' );

// ***********************  1. Categories layouts  ***************************

// Standard Categories Layout 
$JLIST_BACKEND_SETTINGS_TEMPLATES_CATS_DEFAULT = '{cat_title_begin}<div class= "jd_subcats_title_text" style="">{subcats_title_text}</div>{cat_title_end}
{cat_info_begin}
<div class="jd_cat_main" style="">
<!-- Standard Categories layout main -->
    <div class="jd_categories_title_v46" style="">{cat_pic}{cat_title}
         <div class="jd_categories_inner_wrapper" style="">
             <div class= "jd_cats_sum_subcats" style="">{sum_subcats}</div>
             <div class="jd_cats_sum_files" style="">{sum_files_cat}</div>
        </div>
   </div>
    <div class="jd_clear"></div>
    <div  class="jd_cat_description" style="">{cat_description}</div>
    <div class="jd_clear_left">{tags}</div>
</div>
{cat_info_end}';

// 4 Column Categories Layout
$JLIST_BACKEND_SETTINGS_TEMPLATES_CATS_COL_DEFAULT = '{cat_info_begin}
<div class="jd_cats_4col_wrapper" style="">
  <!-- cat pics -->
   <div class="jd_cats_4col_inner_wrapper" style="">
      <div class= "jd_cats_4col">{cat_pic1}</div>
      <div class= "jd_cats_4col">{cat_pic2}</div>
      <div class= "jd_cats_4col">{cat_pic3}</div>
      <div class= "jd_cats_4col">{cat_pic4}</div>
   </div>
  <!-- cat titles -->
   <div class="jd_cats_4col_inner_wrapper" style="font-weight: bold;">
      <div class= "jd_cats_4col">{cat_title1}</div>
      <div class= "jd_cats_4col">{cat_title2}</div>
      <div class= "jd_cats_4col">{cat_title3}</div>
      <div class= "jd_cats_4col">{cat_title4}</div>
   </div>
  <!-- num subcats -->
   <div class="jd_cats_4col_inner_wrapper" style="">
      <div class= "jd_cats_4col">{sum_subcats1}&#160;</div>
      <div class= "jd_cats_4col">{sum_subcats2}&#160;</div>
      <div class= "jd_cats_4col">{sum_subcats3}&#160;</div>
      <div class= "jd_cats_4col">{sum_subcats4}&#160;</div>
   </div>
  <!-- num files -->
    <div class="jd_cats_4col_inner_wrapper" style="">
      <div class= "jd_cats_4col">{sum_files_cat1}&#160;</div>
      <div class= "jd_cats_4col">{sum_files_cat2}&#160;</div>
      <div class= "jd_cats_4col">{sum_files_cat3}&#160;</div>
      <div class= "jd_cats_4col">{sum_files_cat4}&#160;</div>
    </div>
<div class="jd_clear"></div>
</div>
{cat_info_end}';

// 2 Column Categories Layout
$JLIST_BACKEND_SETTINGS_TEMPLATES_CATS_COL2_DEFAULT = '{cat_info_begin}
<div class="jd_cats_2col_wrapper" style="">
    <div class="jd_2col_inner_wrapper" style="">
        <div class="jd_inline" style="">
            <div class= "jd_cats_2col" style="font-weight: bold;">{cat_title1}</div>
            <div class= "jd_cats_2col" style="">{sum_subcats1}&#160;</div>
            <div class= "jd_cats_2col" style="">{sum_files_cat1}&#160;</div>
        </div>
                <div class= "jd_cats_2col jd_cats_2col_left"  style="">{cat_pic1}</div>
        <div class= "jd_cats_2col"  style="width:100%;">{cat_description1}&#160;</div>
    </div>
    <div class="jd_2col_inner_wrapper_right" style="float:right;">
        <div class="jd_inline" style="">
            <div class= "jd_cats_2col"  style="font-weight: bold;">{cat_title2}</div>
            <div class= "jd_cats_2col"  style="">{sum_subcats2}&#160;</div>
            <div class= "jd_cats_2col"  style="">{sum_files_cat2}&#160;</div>
        </div>
        <div class= "jd_cats_2col jd_cats_2col_left"  style="">{cat_pic2}</div> 
        <div class= "jd_cats_2col" style="width:100%;">{cat_description2}&#160;</div>
    </div>
 </div>
<div class="jd_clear"></div>
{cat_info_end}';

// This layout is used to view the subcategories from a category with pagination. 
// it must not be 'activated' for this
// no header, subheader or footer data is required here
$JLIST_BACKEND_SETTINGS_TEMPLATES_SUBCATS_PAGINATION_BEFORE = '{cat_title_begin}
<div class="jd_subcats_title_text"  style=" width: 100%;">{subcats_title_text}</div>
<!-- Categories paginated layout title -->
<div id="pageNavPosition" class="pageNavPosition"></div>
{cat_title_end}
<div class="jd_clear"></div>
<div id="results" class="jd_subcats_main" style="">';
$JLIST_BACKEND_SETTINGS_TEMPLATES_SUBCATS_PAGINATION_DEFAULT = '{cat_info_begin}
<div class="jd_subcat_pagination_inner_wrapper" style="">
<!-- Categories paginated layout main -->
    <div class="jd_categories_title_v46" style="">{cat_pic}{cat_title}
         <div class="jd_categories_inner_wrapper" style="">
             <div class= "jd_cats_sum_subcats" style="">{sum_subcats}</div>
             <div class="jd_cats_sum_files" style="">{sum_files_cat}</div>
        </div>
   </div>
<div class="jd_clear"></div>
<div  class="jd_cat_description" style="">{cat_description}</div>
<div class="jd_clear_left">{tags}</div>
</div>
<div class="jd_clear"></div>  
{cat_info_end}';
$JLIST_BACKEND_SETTINGS_TEMPLATES_SUBCATS_PAGINATION_AFTER = '</div>';

$cats_header = '<div class="jd_top_navi" style=""><!-- Categories layout header -->
    <div class="jd_top_navi_item" style="">{home_link}</div>
    <div class="jd_top_navi_item" style="">{search_link}</div>
    <div class="jd_top_navi_item" style="">{upload_link}</div>
    <div class="jd_top_navi_catbox" style="" >{category_listbox}</div>
</div>';

$cats_subheader = '<div class="jd_cats_subheader" style=""><!--Categories layout subheader-->
  <div class="jd_cat_subheader_title" style="">{subheader_title}</div>
  <div class="jd_clear"></div>  
  <div class="jd_subcat_count" style="">{count_of_sub_categories}</div>
  <div class="jd_page_nav" style="">{page_navigation_pages_counter}{page_navigation}</div> 
</div>';

$cats_footer = '<div class="jd_footer jd_page_nav" style="">{page_navigation}</div><!-- Categories layout footer -->
<div style="" class="jd_back_button">{back_link}</div>';

// ***********************  END CATEGORIES ***************************

// ***********************  2. Category layouts  *********************

// Standard Category Layout 3.2
$JLIST_BACKEND_SETTINGS_TEMPLATES_CAT_DEFAULT = '{cat_title_begin}<div class="jd_subcats_title_text" style="">{subcats_title_text}</div>{cat_title_end}
{cat_info_begin}
<div class="jd_cat_main" style=""><!-- Category layout -->
    <div class="jd_categories_title_j46" style="font-weight: bold;">{cat_pic}{cat_title}
        <div class="jd_categories_inner_wrapper" style="">
            <div class= "jd_cats_sum_subcats" style="">{sum_subcats}</div>
            <div class="jd_cats_sum_files" style="">{sum_files_cat}</div>
        </div>
    </div>
    <div class="jd_clear"></div>
    <div  class="jd_cat_description" style="">{cat_description}</div>
    <div class="jd_clear_left">{tags}</div>
</div>
{cat_info_end}
{sub_categories}
<div class="jd_right" style="">{checkbox_top}</div>
{files}
{form_hidden}
<div style="text-align:right">{form_button}</div>';

$cat_header = '<div class="jd_top_navi" style=""><!-- Category layout header -->
    <div class="jd_top_navi_item" style="">{home_link}</div>
    <div class="jd_top_navi_item" style="">{search_link}</div>
    <div class="jd_top_navi_item" style="">{upper_link}</div>
    <div class="jd_top_navi_item" style="">{upload_link}</div>
    <div class="jd_top_navi_catbox" style="">{category_listbox}</div>
</div>';

$cat_subheader = '<div class="jd_cat_subheader" style="height:3em;"><!-- Category layout subheader -->
   <div  class="jd_cat_subheader_title" style="vertical-align:top;">{subheader_title}</div>
   <div class="jd_clear"></div>
   <div class ="jd_subcat_count" style="vertical-align:top;">{count_of_sub_categories}</div>
   <div class="jd_page_nav" style="vertical-align:top;">{page_navigation_pages_counter}{page_navigation}</div>
</div>';

$cat_footer = '<div class="jd_footer jd_page_nav">{page_navigation}</div>
<!-- Category layout footer -->
<div class="jd_back_button" style="">{back_link}</div>';

// ***********************  END CATEGORY ***************************

// ***********************  3. Files layouts  *********************

// Standard Files Layout v3.2 - files layout with mini icons - no checkboxes 
$JLIST_BACKEND_SETTINGS_TEMPLATES_FILES_DEFAULT = '{files_title_begin}<div class="jd_files_title" style="">{files_title_text}</div>{files_title_end}
<!-- standard files layout 3.2 -->
<div class= "{featured_class} jd_download_title" style="">
    <div class="jd_title_block" style="">{file_pic}{file_title}{release}{pic_is_new}{pic_is_hot}{pic_is_updated}</div>
    <div class="jd_rating" style="">{rating}</div>
    <div class="jd_featured_pic" style="">{featured_pic}</div>
    <div class="jd_clear"></div>
</div><!-- end of featured class -->
<div class="jd_clear">{tags}</div>
<div class="jd_download_wrapper" style="">
   <div class ="{featured_detail_class}  jd_description_wrapper"  style=""> 
        <div class="jd_image_right" style="">{screenshot_begin}<a href="{screenshot}" rel="lightbox"><img src="{thumbnail}" style="" /></a>{screenshot_end} </div>    
        <div class="jd_clear_left"></div>
        <div class="jd_download_description" style="">{description}</div>
    {preview_player}{mp3_id3_tag}
   </div><!-- end of featured_detail class -->
   <!-- now the details note this layout uses mini symbols -->
   <div class="jd_clear"></div>    
   <div class="jd_minipic_wrapper" style="" >
        <div class="jd_files_minipic" style="">{license_text}</div>
        <div class="jd_files_minipic" style="">{author_text}</div>
        <div class="jd_files_minipic" style="">{author_url_text}</div>
        <div class="jd_files_minipic" style="">{created_date_value}</div>
        <div class="jd_files_minipic" style="">{language_text}</div>
        <div class="jd_files_minipic" style="">{system_text}</div>
        <div class="jd_files_minipic" style="">{filesize_value}</div>
        <div class="jd_files_minipic" style="">{hits_value}</div>
     <div class="jd_left" style="">{sum_jcomments}</div>
     <div class="jd_url_download_right" style="">{url_download}</div>
   </div> 
</div>';  

// Standard Files Layout with Checkboxes v3.2 - files layout WITH checkboxes - no mini icons  
$JLIST_BACKEND_SETTINGS_TEMPLATES_FILES_DEFAULT_NEW_SIMPLE_1 = '{files_title_begin}<div class="jd_files_title" style="">{files_title_text}</div>{files_title_end}
<div class= "{featured_class}" style="width: 100%;">
      <div class="jd_clear"></div>
      <div class="jd_left" style="">{file_pic}{file_title}{release}{pic_is_new}{pic_is_hot}{pic_is_updated}</div>
<div class="jd_checkbox_wrapper" style="">
      <div class="jd_featured_pic jd_files_checkbox" style="">{featured_pic}</div>
      <div class="jd_rating_field" style="">{rating}</div>
      <div class="jd_checkbox_file" style="">{checkbox_list}</div>
</div>
</div>
<!-- now the details -->
<div class="jd_clear">{tags}</div>
<div class ="jd_content_wrapper {featured_detail_class}" style="">   
    <div class ="jd_clear jd_content_left" style=""> 
        <div class="jd_image_right" style="">{screenshot_begin}<a href="{screenshot}" rel="lightbox"><img src="{thumbnail}" style="" /></a>{screenshot_end}</div>    
        <div class="jd_clear_left" style="">{description}</div>
   </div>
 <div class="jd_clear"></div>
   <div class="jd_fields_wrapper" style="width:100%;">
           <div class="jd_fields_caption" style="">Information</div> 
           <div class="jd_fields" style="width:100%;">
             <div class="jd_field_row" style="">
                 <span class="jd_field_title">{created_date_title}</span>
                 <span class="jd_field_value">{created_date_value}</span>
             </div>
             <div class="jd_field_row" style="">
                 <span class="jd_field_title">{modified_date_title}</span>
                 <span class="jd_field_value">{modified_date_value}</span>
            </div>
            <div class="jd_field_row" style="">
                 <span class="jd_field_title">{release_title}</span>
                 <span class="jd_field_value">{release}</span>
            </div>
            <div class="jd_field_row" style="">
                 <span class="jd_field_title">{filesize_title}</span>
                 <span class="jd_field_value">{filesize_value}</span>
            </div>
            <div class="jd_field_row" style="">
                 <span class="jd_field_title">{system_title}</span>
                 <span class="jd_field_value">{system_text}</span>
            </div>
            <div class="jd_field_row" style="">
                 <span class="jd_field_title">{hits_title}</span>
                 <span class="jd_field_value">{hits_value}</span>
            </div>
        </div>
   </div>
   <div class="jd_preview" style="">{preview_player}</div>
   <div class="jd_preview" style="">{mp3_id3_tag}</div>
</div>
<div class="jd_clear"></div>';

// Standard Files Layout without Checkboxes v3.2 - no mini icons
$JLIST_BACKEND_SETTINGS_TEMPLATES_FILES_DEFAULT_NEW_SIMPLE_2 = '{files_title_begin}<div class="jd_files_title" style="">{files_title_text}</div>{files_title_end}
<div class= "{featured_class}" style="width: 100%;">
     <div class="jd_clear"></div><!-- files layout without checkboxes -->
     <div class="jd_left" style="">{file_pic}{file_title}{release}{pic_is_new}{pic_is_hot}{pic_is_updated}</div>
     <div class="jd_download_url jd_download_url_position" style="">{url_download}</div>
     <div class="jd_rating" style="">{rating}</div>
     <div class="jd_featured_pic" style="text-align:center; float:right; margin:0 10px;">{featured_pic}</div>
</div>
<!-- now the details -->
<div class="jd_clear">{tags}</div>
<div class ="jd_content_wrapper {featured_detail_class}" style="">   
    <div class ="jd_clear jd_content_left" style=""> 
        <div class="jd_image_right" style="">{screenshot_begin}<a href="{screenshot}" rel="lightbox"><img src="{thumbnail}" style="" /></a>{screenshot_end}</div>    
        <div class="jd_clear_left" style="">{description}</div>
   </div>
 <div class="jd_clear"> </div>
   <div class="jd_fields_wrapper " style="width:100%;">
          <div class="jd_fields_caption" style="">Information</div> 
          <div class="jd_fields" style="width:100%;">
            <div class="jd_field_row" style="">
                 <span class="jd_field_title">{created_date_title}</span>
                 <span class="jd_field_value">{created_date_value}</span>
            </div>
            <div class="jd_field_row" style="">
                 <span class="jd_field_title">{modified_date_title}</span>
                 <span class="jd_field_value">{modified_date_value}</span>
            </div>
            <div class="jd_field_row" style="">
                 <span class="jd_field_title">{release_title}</span>
                 <span class="jd_field_value">{release}</span>
            </div>
            <div class="jd_field_row" style="">
                 <span class="jd_field_title">{filesize_title}</span>
                 <span class="jd_field_value">{filesize_value}</span>
            </div>
            <div class="jd_field_row" style="">
                 <span class="jd_field_title">{system_title}</span>
                 <span class="jd_field_value">{system_text}</span>
            </div>
            <div class="jd_field_row" style="">
                 <span class="jd_field_title">{hits_title}</span>
                 <span class="jd_field_value">{hits_value}</span>
            </div>
          </div>
   </div>
   <div class="jd_preview" style="">{preview_player}</div>
   <div class="jd_preview" style="">{mp3_id3_tag}</div>
</div>
<div class="jd_clear"></div>';

// Alternate Files Layout v3.2 - no mini icons
$JLIST_BACKEND_SETTINGS_TEMPLATES_FILES_NEW_ALTERNATE_1_BEFORE = '';

$JLIST_BACKEND_SETTINGS_TEMPLATES_FILES_NEW_ALTERNATE_1 = '{files_title_begin}<div class="jd_files_title" style="">{files_title_text}</div>{files_title_end}
 <div class= "{featured_class} jd_download_title" style="">
     <div class="jd_left" style=""> {file_pic} {file_title} {pic_is_new} {pic_is_hot} {pic_is_updated}{featured_pic}</div>
     <div class="jd_rating" style=""> {rating} </div>
</div>
<div class="jd_clear"></div>
<div class="jd_tags">{tags}</div>
<div class="jd_download_wrapper" style="">
    <div class="{featured_detail_class} jd_clear">
       <div class="jd_words_wrapper" style="" >
           <div class="jd_words_left" style="padding-left:5px;">
                  <span style="font-weight: 600">{created_date_title}:&#160;</span><span>{created_date_value}</span></div>
           <div class="jd_words_left"><span style="font-weight: 600">{release_title}:&#160;</span><span>{release}</span></div>
           <div class="jd_words_left"><span style="font-weight: 600">{license_title}:&#160;</span><span>{license_text}</span></div>
           <div class="jd_words_left"><span style="font-weight: 600">{filesize_title}:&#160;</span><span>{filesize_value}</span></div>
         <div class="jd_words_right">{url_download}</div>
</div>
     <div class="jd_clear"></div>
     <div class ="jd_description_wrapper"  style=""> 
         <div class="jd_image_right" style="">{screenshot_begin}<a href="{screenshot}" rel="lightbox"> 
             <img src="{thumbnail}" style="" /></a>{screenshot_end}</div>    
         <div class="jd_clear_left"></div>
         <div class="jd_download_description" style="">{description}</div>
     </div>
     <div class="jd_clear"></div>
     <div class="jd_readmore">{link_to_details}</div>
    </div>
</div>';

$JLIST_BACKEND_SETTINGS_TEMPLATES_FILES_NEW_ALTERNATE_1_AFTER = '';

// New Files Layout for v3.2 - Layout with full range of data
$JLIST_BACKEND_SETTINGS_TEMPLATES_FILES_FULL_INFO = '{files_title_begin}<div class = "jd_files_title" style="">{files_title_text}</div>{files_title_end}
<div class="jd_download_wrapper">
        <!--  title section  -->
        <div class="jd_header" style="border:0px none;">
             <div class="jd_title_left"><h4>{file_pic}{file_title}{pic_is_new}{pic_is_hot}{pic_is_updated}</h4></div>
             <div class="jd_title_right jd_download_url_position" style="margin-top:15px;">{url_download}</div>
             <div class="jd_title_right">{featured_pic}</div>
        </div>
        <div class="jd_clear"></div>
        <div class="jd_tags" style="">{tags}</div>
        <!-- preview, description and screen shots section  -->
        <div class="jd_main {featured_class}" style="">
              <div class="jd_description_wrapper jd_clear">{description}</div>
              <div class="jd_preview" style="">{preview_player}</div>
              <div class="jd_preview" style="">{mp3_id3_tag}</div>
              <div class="jd_screenshot_zone jd_clear" style="">
                <span class="jd_screenshot">{screenshot_begin}<a href="{screenshot}" rel="lightbox" target="_blank"><img class="jd_image-left" src="{thumbnail}" /></a>{screenshot_end} </span>
                <span class="jd_screenshot">{screenshot_begin2}<a href="{screenshot2}" rel="lightbox" target="_blank"><img class="jd_image-left" src="{thumbnail2}" /></a>{screenshot_end2} </span>
                <span class="jd_screenshot"> {screenshot_begin3}<a href="{screenshot3}" rel="lightbox" target="_blank"><img class="jd_image-left" src="{thumbnail3}" /></a>{screenshot_end3}</span>
              </div> <!-- end of screenshot zone -->
</div><!-- end of jd_main -->
<div class="jd_clear"> </div>
<!--  information section  -->
   <div class="jd_fields_wrapper {featured_detail_class}" style="">
        <div class="jd_fields_caption" style="">Information</div> 
        <!--  set width to 50% as we have two columns -->
        <div class="jd_fields" style="width: 50%;">
             <div class="jd_field_row" style="">
                 <span class="jd_field_title">{created_date_title}</span>
                 <span class="jd_field_value">{created_date_value}</span>
             </div>
             <div class="jd_field_row" style="">
                 <span class="jd_field_title">{modified_date_title}</span>
                 <span class="jd_field_value">{modified_date_value}</span>
            </div>
            <div class="jd_field_row" style="">
                 <span class="jd_field_title">{release_title}</span>
                 <span class="jd_field_value">{release}</span>
            </div>
            <div class="jd_field_row" style="">
                 <span class="jd_field_title">{filesize_title}</span>
                 <span class="jd_field_value">{filesize_value}</span>
            </div>
            <div class="jd_field_row" style="width:100%">
                 <span class="jd_field_title">{rating_title}</span>
                 <span class="jd_field_value jd_rating_field" style="">{rating}</span>
            </div>
        </div>
        <!-- end of first jd_fields column -->
        <div class="jd_fields" style="width:50%;">
             <div class="jd_field_row" style="">
                 <span class="jd_field_title">{created_by_title}</span>
                 <span class="jd_field_value">{created_by_value}</span>
             </div>  
             <div class="jd_field_row" style="">
                 <span class="jd_field_title">{modified_by_title}</span>
                 <span class="jd_field_value">{modified_by_value}</span>
             </div> 
             <div class="jd_field_row" style=""> 
                     <span class="jd_field_title">{hits_title}</span>
                     <span class="jd_field_value">{hits_value}</span>
             </div>
             <div class="jd_field_row" style="">
                      <span class="jd_field_title">{license_title}</span>
                     <span class="jd_field_value">{license_text}</span>
             </div>
             <div class="jd_field_row" style="">
                    <span class="jd_field_title">{price_title}</span>
                    <span class="jd_field_value">{price_value}</span>
             </div>
        </div><!-- end of second jd_fields -->
  </div><!-- end of information section -->
  <div class="jd_clear"></div>
</div>';

// New Files Layout for v3.2 - Single Line
$JLIST_BACKEND_SETTINGS_TEMPLATES_FILES_SINGLE_LINE = '<div class="jd_content_wrapper">
<div class ="jd_clear {featured_class}" style="width:100%; padding:3px 0px; ">
      <div class = "jd_left" style="">{file_pic}{file_title}{release}{pic_is_new}{pic_is_hot}{pic_is_updated}</div>
      <div class = "jd_right jd_download_url_position" style="">{url_download}</div>
      <div class="jd_featured_pic" style="">{featured_pic}</div>
      <div class="jd_clear" style=""></div>
  </div>
</div>
<div class="jd_clear" style=""></div>';

// New Files Layout for v3.2 - Just a link 
$JLIST_BACKEND_SETTINGS_TEMPLATES_FILES_JUST_LINK = '<div class = "jd_files_oneline" style="">{file_title}{release}{pic_is_new}{pic_is_hot}{pic_is_updated}&#160;</div>';

$files_header = '<div class="jd_top_navi" style=""><!--Files layout header-->
    <div class="jd_top_navi_item" style="">{home_link}</div>
    <div class="jd_top_navi_item" style="">{search_link}</div>
    <div class="jd_top_navi_item" style="">{upper_link}</div>
    <div class="jd_top_navi_item" style="">{upload_link}</div>
    <div class="jd_top_navi_catbox" style="">{category_listbox}</div>
</div>';

$files_subheader = '<div class="jd_files_subheader" style=""><!--Files layout subheader-->
    <div class="jd_clear"></div>
    <div class="jd_files_subheader_title" style="">{subheader_title}</div>
    <div class="jd_page_nav" style="">{page_navigation_pages_counter}{page_navigation}</div>
    <div class="jd_clear"></div>
    <div class="jd_subcat_count" style="">{count_of_sub_categories}</div>
    <div class="jd_sort_order" style="">{sort_order}</div>
</div>
<div class="jd_clear"></div>';

$files_footer = '<div class="jd_footer jd_page_nav" style="">{page_navigation}</div><!--Files layout footer-->
<div style="" class="jd_back_button">{back_link}</div>';

// ***********************  END FILES ***************************

// ***********************  4. Details layouts  *********************

// Standard Details Layout v3.2 - Full Info 
$JLIST_BACKEND_SETTINGS_TEMPLATES_DETAILS_DEFAULT = '<div class="jd_download_details_wrapper"><!--  Details Full Info layout main -->
<!--  title section  -->
    <div class="jd_header" style ="border: 0px none;">
        <div class="jd_title_left"><h4>{file_pic} {file_title} {pic_is_new} {pic_is_hot} {pic_is_updated}</h4></div>
        <div class="jd_title_right  jd_download_url_position" style="">{url_download}</div>
        <div class="jd_title_right">{featured_pic}</div>
    </div>
    <div class="jd_clear"></div>
    <div class="jd_tags" style="">{tags}</div>
<!-- preview, description and screen shots section  -->
    <div class="jd_main {featured_class}" style="">
        <div class="jd_description_wrapper jd_clear">{description_long} </div>
        <div class="jd_preview" style="">{preview_player}</div>
        <div class="jd_preview" style="">{mp3_id3_tag}</div>
        <div class="jd_screenshot_zone jd_clear" style="">
            <span class="jd_screenshot">{screenshot_begin}<a href="{screenshot}" rel="lightbox" target="_blank"><img class="jd_image-left" src="{thumbnail}" /></a>{screenshot_end} </span>
            <span class="jd_screenshot">{screenshot_begin2}<a href="{screenshot2}" rel="lightbox" target="_blank"><img class="jd_image-left" src="{thumbnail2}" /></a>{screenshot_end2} </span>
            <span class="jd_screenshot">{screenshot_begin3}<a href="{screenshot3}" rel="lightbox" target="_blank"><img class="jd_image-left" src="{thumbnail3}" /></a>{screenshot_end3} </span>
        </div> <!-- end of screenshot zone -->
    </div><!-- end of jd_main -->
    <div class="jd_clear"></div>
<!--  information section  -->
    <div class="jd_fields_wrapper {featured_detail_class}" style="">
        <div class="jd_fields_caption" style="">Information </div> 
<!--  set width to 50% as we have two columns  -->
        <div class="jd_fields" style="width: 50%;">
            <div class="jd_field_row" style="">
                 <span class="jd_field_title">{created_date_title} </span>
                 <span class="jd_field_value">{created_date_value}</span>
            </div>
            <div class="jd_field_row" style="">
                 <span class="jd_field_title">{modified_date_title} </span>
                 <span class="jd_field_value">{modified_date_value}</span>
            </div>
            <div class="jd_field_row" style="">
                 <span class="jd_field_title">{release_title}</span>
                 <span class="jd_field_value">{release}</span>
            </div>
            <div class="jd_field_row" style="">
                 <span class="jd_field_title">{filesize_title}</span>
                 <span class="jd_field_value">{filesize_value}</span>
            </div>
            <div class="jd_field_row" style="">
                 <span class="jd_field_title">{rating_title}</span>
                 <span class="jd_field_value jd_rating_field" style="">{rating}</span>
            </div>
        </div>
 <!-- end of first jd_fields column -->
        <div class="jd_fields" style="width: 50%;">
            <div class="jd_field_row" style="">
                 <span class="jd_field_title">{created_by_title} </span>
                 <span class="jd_field_value">{created_by_value}</span>
            </div>  
            <div class="jd_field_row" style="">
                 <span class="jd_field_title">{modified_by_title} </span>
                 <span class="jd_field_value">{modified_by_value}</span>
            </div> 
            <div class="jd_field_row" style=""> 
                <span class="jd_field_title">{hits_title}</span>
                <span class="jd_field_value">{hits_value}</span>
            </div>
            <div class="jd_field_row" style="">
                <span class="jd_field_title">{license_title}</span>
                <span class="jd_field_value">{license_text}</span>
            </div>
            <div class="jd_field_row" style="">
                <span class="jd_field_title">{price_title}</span>
                <span class="jd_field_value">{price_value}</span>
            </div>
        </div><!-- end of second jd_fields column -->
    </div><!--  end of information section jd_fields_wrapper -->
    <div class="jd_clear"></div>
    <div class="jd_report_link" style="">{report_link}</div>
</div>';

// Example Details Layout v3.2 - with Tabs 
$JLIST_BACKEND_SETTINGS_TEMPLATES_DETAILS_DEFAULT_WITH_TABS = '<div class="jd_download_details_title {featured_class}" style="">{file_pic}{file_title}{pic_is_new}{pic_is_hot}{pic_is_updated}
     <div class="jd_right" style="">{featured_pic}</div>
     <div class="jd_rating" style="">{rating}</div>
</div><!-- Example Details with Tabs layout main -->
{tabs begin}
{tab description}
<div>{description_long}</div>
{tab description end}
{tab pics}
<div class="jd_download_detail_pics_wrapper" style="">
     <div class="jd_download_detail_pics" style="">{screenshot_begin}<a href="{screenshot}" rel="lightbox"><img class="jd_download_detail_img" src="{thumbnail}" /></a>{screenshot_end}</div>
     <div class="jd_download_detail_pics" style=""">{screenshot_begin2}<a href="{screenshot2}" rel="lightbox"><img class="jd_download_detail_img" src="{thumbnail2}" align="right" /></a>{screenshot_end2}</div>
     <div class="jd_download_detail_pics" style=""">{screenshot_begin3}<a href="{screenshot3}" rel="lightbox"><img class="jd_download_detail_img" src="{thumbnail3}" align="right" /></a>{screenshot_end3}</div>
     <div class="jd_download_detail_pics" style=""">{screenshot_begin4}<a href="{screenshot4}" rel="lightbox"><img class="jd_download_detail_img" src="{thumbnail4}" align="right" /></a>{screenshot_end4}</div> 
     <div class="jd_download_detail_pics" style=""">{screenshot_begin5}<a href="{screenshot5}" rel="lightbox"><img class="jd_download_detail_img" src="{thumbnail5}" align="right" /></a>{screenshot_end5}</div>
</div>
<div class="jd_clear"> </div>
{tab pics end}
{tab mp3}
<div>{preview_player}</div>
<div>{mp3_id3_tag}</div>
{tab mp3 end}
{tab data}
     <div class="jd_fields_wrapper {featured_detail_class}"  style="">
           <div class="jd_fields_caption" style="">{details_block_title}</div> 
<!--  set width to 50% as we have two columns  -->
            <div class="jd_fields" style="width: 50%;">
               <div class="jd_field_row" style="">
                 <span class="jd_field_title">{created_date_title}</span>
                 <span class="jd_field_value">{created_date_value}</span>
            </div>
            <div class="jd_field_row" style="">
                 <span class="jd_field_title">{modified_date_title}</span>
                 <span class="jd_field_value">{modified_date_value}</span>
            </div>
            <div class="jd_field_row" style="">
                 <span class="jd_field_title">{release_title}</span>
                 <span class="jd_field_value">{release}</span>
            </div>
            <div class="jd_field_row" style="">
                 <span class="jd_field_title">{filesize_title}</span>
                 <span class="jd_field_value">{filesize_value}</span>
            </div>
            <div class="jd_field_row" style="">
                 <span class="jd_field_title">{rating_title}</span>
                 <span class="jd_field_value jd_rating_field" style="">{rating}</span>
            </div>
            <div class="jd_field_row" style="">
                    <span class="jd_field_title">{author_title}</span>
                    <span class="jd_field_value">{author_text}</span>
            </div>
            <div class="jd_field_row" style="">
                    <span class="jd_field_title">{md5_title}</span>
                    <span class="jd_field_value">{md5_value}</span>
            </div>
        </div>
 <!-- end of first jd_fields column -->
        <div class="jd_fields" style="width:50%;">
            <div class="jd_field_row" style="">
                 <span class="jd_field_title">{created_by_title}</span>
                 <span class="jd_field_value">{created_by_value}</span>
            </div>  
             <div class="jd_field_row" style="">
                 <span class="jd_field_title">{modified_by_title}</span>
                 <span class="jd_field_value">{modified_by_value}</span>
            </div> 
            <div class="jd_field_row" style=""> 
                     <span class="jd_field_title">{hits_title}</span>
                     <span class="jd_field_value">{hits_value}</span>
            </div>
            <div class="jd_field_row" style="">
                <span class="jd_field_title">{license_title}</span>
                <span class="jd_field_value">{license_text}</span>
            </div>
            <div class="jd_field_row" style="">
                <span class="jd_field_title">{price_title}</span>
                <span class="jd_field_value">{price_value}</span>
            </div>
            <div class="jd_field_row" style="">
                <span class="jd_field_title">{author_url_title}</span>
                <span class="jd_field_value">{author_url_text}</span>
            </div>
            <div class="jd_field_row" style="">
                <span class="jd_field_title">{sha1_title}</span>
                <span class="jd_field_value">{sha1_value}</span>
            </div>
        </div><!-- end of second jd_fields -->
    </div>
{tab data end}
{tab download}
        <div class="jd_field_row_wide" style="">{file_name_title}:{file_name}</div> 
        <div class="jd_field_row_wide" style="">{filesize_title}:{filesize_value}</div> 
        <div class="jd_field_row_wide" style="">{url_download}{mirror_1}{mirror_2}</div> 
        <div class="jd_field_row_wide jd_report_link" style="">{report_link}</div>
{tab download end}
{tab custom1}
 
{tab custom1 end}
{tabs end}';    

// Example Details Layout v3.2 
$JLIST_BACKEND_SETTINGS_TEMPLATES_DETAILS_DEFAULT_NEW_25 = '<div class="jd_download_details_wrapper"><!-- Example Details layout main -->
    <div class="jd_download_details_title {featured_class}" style="">
    {file_pic}{file_title}{pic_is_new}{pic_is_hot}{pic_is_updated}
	<div class="jd_right" style=""> {featured_pic}</div>
		<div class="jd_rating_field" style="font-size: 11px; padding-top:7px;">{rating}</div>
    </div>
    <div class="jd_clear"></div>
    <div class="jd_tags">{tags}</div>
    <div class="jd_clear"></div>
    <div style="font-weight:normal">{description_long}</div>
    <div class="jd_video_and_images_wrapper" style="width:100%">.
        <div>{preview_player}</div>
        <div>{mp3_id3_tag}</div>
        <div class="jd_download_detail_pics_wrapper" style="">
            <div class="jd_download_detail_pics" style="">{screenshot_begin}<a href="{screenshot}" rel="lightbox"><img class="jd_download_detail_img" src="{thumbnail}" /></a>{screenshot_end}</div>
            <div class="jd_download_detail_pics" style=""">{screenshot_begin2}<a href="{screenshot2}" rel="lightbox"><img class="jd_download_detail_img" src="{thumbnail2}" align="right" /></a>{screenshot_end2}</div>
            <div class="jd_download_detail_pics" style=""">{screenshot_begin3}<a href="{screenshot3}" rel="lightbox"><img class="jd_download_detail_img" src="{thumbnail3}" align="right" /></a>{screenshot_end3}</div>
            <div class="jd_download_detail_pics" style=""">{screenshot_begin4}<a href="{screenshot4}" rel="lightbox"><img class="jd_download_detail_img" src="{thumbnail4}" align="right" /></a>{screenshot_end4}</div> 
            <div class="jd_download_detail_pics" style=""">{screenshot_begin5}<a href="{screenshot5}" rel="lightbox"><img class="jd_download_detail_img" src="{thumbnail5}" align="right" /></a>{screenshot_end5}</div>
        </div>
    </div>
    <div class="jd_clear"> </div>
<!--  information section  -->
    <div class="jd_fields_wrapper {featured_detail_class}"  style="">
        <div class="jd_fields_caption" style="">{details_block_title}</div> 
<!--  set width to 50% as we have two columns  -->
        <div class="jd_fields" style="width: 50%;">
            <div class="jd_field_row" style="">
                 <span class="jd_field_title">{created_date_title} </span>
                 <span class="jd_field_value">{created_date_value}</span>
            </div>
            <div class="jd_field_row" style="">
                 <span class="jd_field_title">{modified_date_title} </span>
                 <span class="jd_field_value">{modified_date_value}</span>
            </div>
            <div class="jd_field_row" style="">
                 <span class="jd_field_title">{release_title}</span>
                 <span class="jd_field_value">{release}</span>
            </div>
            <div class="jd_field_row" style="">
                 <span class="jd_field_title">{filesize_title}</span>
                 <span class="jd_field_value">{filesize_value}</span>
            </div>
            <div class="jd_field_row" style="">
                 <span class="jd_field_title">{rating_title}</span>
                 <span class="jd_field_value jd_rating_field" style="">{rating}</span>
            </div>
            <div class="jd_field_row" style="">
                    <span class="jd_field_title">{author_title}</span>
                    <span class="jd_field_value">{author_text}</span>
            </div>
            <div class="jd_field_row" style="">
                    <span class="jd_field_title">{md5_title}</span>
                    <span class="jd_field_value">{md5_value}</span>
            </div>
        </div>
 <!-- end of first jd_fields column -->
        <div class="jd_fields" style="width:50%;">
            <div class="jd_field_row" style="">
                 <span class="jd_field_title">{created_by_title} </span>
                 <span class="jd_field_value">{created_by_value}</span>
            </div>  
            <div class="jd_field_row" style="">
                <span class="jd_field_title">{modified_by_title} </span>
                <span class="jd_field_value">{modified_by_value}</span>
            </div> 
            <div class="jd_field_row" style=""> 
                <span class="jd_field_title">{hits_title}</span>
                <span class="jd_field_value">{hits_value}</span>
            </div>
            <div class="jd_field_row" style="">
                <span class="jd_field_title">{license_title}</span>
                <span class="jd_field_value">{license_text}</span>
            </div>
            <div class="jd_field_row" style="">
                <span class="jd_field_title">{price_title}</span>
                <span class="jd_field_value">{price_value}</span>
            </div>
            <div class="jd_field_row" style="">
                    <span class="jd_field_title">{author_url_title}</span>
                    <span class="jd_field_value">{author_url_text}</span>
            </div>
            <div class="jd_field_row" style="">
                <span class="jd_field_title">{sha1_title}</span>
                <span class="jd_field_value">{sha1_value}</span>
            </div>
        </div><!-- end of second jd_fields -->
        <div class="jd_field_row_wide" style="text-align: center; font-size:1em;">{url_download}{mirror_1}{mirror_2}</div> 
        <div class="jd_field_row_wide jd_report_link" style="">{report_link}</div>
    </div><!--  end of information section  -->
</div><!--  end of jd_download_wrapper  -->';

$details_header = '<div class="jd_top_navi" style=""><!-- Details layout header -->
    <div class="jd_top_navi_item" style="">{home_link}</div>
    <div class="jd_top_navi_item" style="">{search_link}</div>
    <div class="jd_top_navi_item" style="">{upper_link}</div>
    <div class="jd_top_navi_item" style="">{upload_link}</div>
    <div class="jd_top_navi_catbox" style="">{category_listbox}</div>
</div>';

$details_subheader = '<div class="jd_cat_subheader" style="font-weight: bold;">{detail_title}</div><!-- Details layout subheader -->';

$details_footer = '<div class="jd_back_button" style="">{back_link}</div><!-- Details layout footer -->';

// ***********************  END DETAILS ***************************

// ***********************  5. Summary layouts  *********************

// Standard Summary Layout v3.2 
$JLIST_BACKEND_SETTINGS_TEMPLATES_SUMMARY_DEFAULT = '<div class="jd_summary_title" style="">{summary_pic}{title_text}</div>
<div style="padding:5px;">{download_liste}</div>
<div>{captcha}</div>
<div>{password}</div>
<div style="padding:5px;">{aup_points_info}</div>
<div style="padding:5px; text-align:center;"><b>{license_title}</b></div>
<div>{license_text}</div>
<div style="text-align:center">{license_checkbox}</div>
<div style="text-align:center; padding:5px;">{download_link}</div>
<div style="text-align:center;">{info_zip_file_size}</div>
<div style="text-align:center;">{external_download_info}</div>
<div style="text-align:center;">{user_limitations}</div>
<div>{google_adsense}</div>';

$summary_header = '<div class="jd_top_navi" style=""><!--summary layout header-->
    <div class="jd_top_navi_item" style="">{home_link}</div>
    <div class="jd_top_navi_item" style="">{search_link}</div>
    <div class="jd_top_navi_item" style="">{upper_link}</div>
    <div class="jd_top_navi_item" style="">{upload_link}</div>
    <div class="jd_top_navi_catbox" style="">{category_listbox}</div>
</div>';

$summary_subheader = '<div class="jd_cat_subheader" style="font-weight: bold;">{summary_title}</div><!--summary layout subheader-->';

$summary_footer = '<div class="jd_back_button" style="">{back_link}</div><!--summary layout footer-->';

// ***********************  END SUMMARY ***************************

// ***********************  6. Search layouts  *********************

// Vertical Standard Search Layout
$JLIST_BACKEND_SETTINGS_TEMPLATES_SEARCH_DEFAULT='';

$search_header = '<div class="jd_top_navi" style=""><!-- Search layout header -->
    <div class="jd_top_navi_item" style="">{home_link}</div>
    <div class="jd_top_navi_item" style="">{search_link}</div>
    <div class="jd_top_navi_item" style="">{upper_link}</div>
    <div class="jd_top_navi_item" style="">{upload_link}</div>
    <div class="jd_top_navi_catbox" style="">{category_listbox}</div>
</div>';
$search_subheader = '';
$search_footer = '';

// Horizontal Search Layout
$JLIST_BACKEND_SETTINGS_TEMPLATES_SEARCH_DEFAULT_HORIZONTAL='';

$search2_header = '';
$search2_subheader = '<div class=jd_search_form_wrapper><!-- Search horizontal layout Subheader -->';
$search2_footer = '</div>  <!-- end of class jd_search_form_wrapper --><!-- Search horizontal layout footer -->';

// ***********************  END SEARCH ***************************


// Standard Layout for MP3 ID3 Tags
$JLIST_BACKEND_SETTINGS_TEMPLATES_ID3TAG = ' <div class="jd_mp3_id3tag_name">{album_title}</div>
 <div class="jd_mp3_id3tag_value">{album}</div>
 <div class="jd_mp3_id3tag_name">{name_title}</div>
 <div class="jd_mp3_id3tag_value">{name}</div>
 <div class="jd_mp3_id3tag_name">{year_title}</div>
 <div class="jd_mp3_id3tag_value">{year}</div>
 <div class="jd_mp3_id3tag_name">{artist_title}</div>
 <div class="jd_mp3_id3tag_value">{artist}</div>
 <div class="jd_mp3_id3tag_name">{genre_title}</div>
 <div class="jd_mp3_id3tag_value">{genre}</div>
 <div class="jd_mp3_id3tag_name">{length_title}</div>
 <div class="jd_mp3_id3tag_value">{length}</div>';


$JLIST_BACKEND_SETTINGS_TEMPLATES_UPLOAD_DEFAULT='';

?>