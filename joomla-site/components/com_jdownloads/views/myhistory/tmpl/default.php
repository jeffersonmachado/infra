<?php
/**
 * @package jDownloads
 * @version 3.2  
 * @copyright (C) 2007 - 2017 - Arno Betz - www.jdownloads.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 * 
 * jDownloads is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

 defined('_JEXEC') or die('Restricted access');

 setlocale(LC_ALL, 'C.UTF-8', 'C');
 
    global $jlistConfig;

    JHtml::addIncludePath(JPATH_COMPONENT . '/helpers');

    $date_format = JDHelper::getDateFormat();

    $html           = '';
    $is_admin       = false;
    
    if (JDHelper::checkGroup('8', true) || JDHelper::checkGroup('7', true)){
        $is_admin = true;
    }
    
    $files = $this->items;

    $list_header = '<div class="jd_history_cols_titles" style="">{col_title}</div>';
    
    // Layout parts with placeholder to build later the output
    $subheader = '<div class="jd_files_subheader" style="">
                    <div class="jd_clear"></div>
                    <div class="jd_files_subheader_title" style="">{subheader_title}</div>
                    <div class="jd_page_nav" style="">{page_navigation_pages_counter}{page_navigation}</div>
					<br />
                    <div class="jd_subcat_count" style="">{amount_of_files}</div>
                  </div>
                  <div class="jd_clear"></div>';
    
    $files_body = '<div class="jd_history_content_wrapper">
                     <div class="jd_clear" style="width:100%;">
                          <div class="jd_left" style="">{log_file_title}</div>
                          <div class="jd_right" style="">{log_date}</div>
                          <div class="jd_clear" style=""></div>
                     </div>
                   </div>
                   <div class="jd_clear" style=""></div>';


    $footer_area = '<div class="jd_footer jd_page_nav" style="">{page_navigation}</div>';
    
    $html = '<div class="jd-item-page'.$this->pageclass_sfx.'">';
    
    if ($this->params->get('show_page_heading')) {
        $html .= '<h1>'.$this->escape($this->params->get('page_heading')).'</h1>';
    } 
    
    // ==========================================
    // HEADER SECTION
    // ==========================================

    $total_downloads  = $this->pagination->get('total');

    // display number of sub categories only when > 0 
    if ($total_downloads == 0){
        $total_files_text = '';
    } else {
        $total_files_text = JText::sprintf('COM_JDOWNLOADS_MY_DOWNLOAD_HISTORY_AMOUNT_DOWNLOADED_FILES', (int)$total_downloads);
    }
    
    $subheader = str_replace('{subheader_title}', JText::_('COM_JDOWNLOADS_MY_DOWNLOAD_HISTORY_TITLE'), $subheader);
    
    // display pagination
    $page_navi_pages   = '';
    $page_navi_counter = '';
    $page_limit_box    = '';
                
    if ($jlistConfig['option.navigate.top'] && $this->pagination->get('pages.total') > 1 && $this->params->get('show_pagination') != '0' 
        || (!$jlistConfig['option.navigate.top'] && $this->pagination->get('pages.total') > 1 && $this->params->get('show_pagination') == '1') )
    {            
        $page_navi_links = $this->pagination->getPagesLinks(); 
    
        if ($page_navi_links){
            $page_navi_pages   = $this->pagination->getPagesCounter();
            $page_navi_counter = $this->pagination->getResultsCounter(); 
            $page_limit_box    = $this->pagination->getLimitBox();  
        }    
    
        $subheader = str_replace('{page_navigation}', $page_navi_links, $subheader);
        $subheader = str_replace('{page_navigation_results_counter}', $page_navi_counter, $subheader);
    
        if ($this->params->get('show_pagination_results') == null || $this->params->get('show_pagination_results') == '1'){
            $subheader = str_replace('{page_navigation_pages_counter}', $page_navi_pages, $subheader); 
        } else {
            $subheader = str_replace('{page_navigation_pages_counter}', '', $subheader);                
        }
    
        $subheader = str_replace('{amount_of_files}', $total_files_text, $subheader);                                                   
    } else {
        $subheader = str_replace('{page_navigation}', '', $subheader);
        $subheader = str_replace('{page_navigation_results_counter}', '', $subheader);
        $subheader = str_replace('{page_navigation_pages_counter}', '', $subheader);
        $subheader = str_replace('{amount_of_files}', $total_files_text, $subheader);                                
    }

    $html .= $subheader;            
    
    // ==========================================
    // BODY SECTION - VIEW THE LOGS DATA
    // ==========================================
    
    $html_files = '';
    
    for ($i = 0; $i < count($files); $i++) {
        
        $html_file = $files_body;
        
        $file_id = $files[$i]->id;
        
        // log date
        if ($files[$i]->log_datetime != '0000-00-00 00:00:00') {
             if ($this->params->get('show_date') == 0){ 
                 $filedate_data = JHtml::_('date',$files[$i]->log_datetime, $date_format['long']);
             } else {
                 $filedate_data = JHtml::_('date',$files[$i]->log_datetime, $date_format['short']);
             }    
        } else {
             $filedate_data = '';
        }

        $html_file = str_replace('{log_file_title}', '<b>'.$files[$i]->log_title.'</b> ('.basename($files[$i]->log_file_name).')', $html_file);
        $html_file = str_replace('{log_date}', $filedate_data, $html_file);
        
        $html_files .= $html_file;
    }
        
    $html .= $html_files;   
  
    // ==========================================
    // FOOTER SECTION  
    // ==========================================

    $footer = '';
    
    // display pagination            
    if ($jlistConfig['option.navigate.bottom'] && $this->pagination->get('pages.total') > 1 && $this->params->get('show_pagination') != '0' 
        || (!$jlistConfig['option.navigate.bottom'] && $this->pagination->get('pages.total') > 1 && $this->params->get('show_pagination') == '1') )
    {
        $page_navi_links = $this->pagination->getPagesLinks(); 
        if ($page_navi_links){
            $page_navi_pages   = $this->pagination->getPagesCounter();
            $page_navi_counter = $this->pagination->getResultsCounter(); 
            $page_limit_box    = $this->pagination->getLimitBox();  
        }    

        $footer = str_replace('{page_navigation}', $page_navi_links, $footer);
        $footer = str_replace('{page_navigation_results_counter}', $page_navi_counter, $footer);
        
        if ($this->params->get('show_pagination_results') == null || $this->params->get('show_pagination_results') == '1'){
            $footer = str_replace('{page_navigation_pages_counter}', $page_navi_pages, $footer); 
        } else {
            $footer = str_replace('{page_navigation_pages_counter}', '', $footer);                
        }             
    } else {
        $footer = str_replace('{page_navigation}', '', $footer);
        $footer = str_replace('{page_navigation_results_counter}', '', $footer);
        $footer = str_replace('{page_navigation_pages_counter}', '', $footer);                
    }

    $footer .= JDHelper::checkCom();
    $html   .= $footer; 
    $html   .= '</div>';

    // ==========================================
    // VIEW THE BUILDED OUTPUT
    // ==========================================

    if ( !$jlistConfig['offline'] ) {
            echo $html;
    } else {
        // admins can view it always
        if ($is_admin) {
            echo $html;     
        } else {
            // build the offline message
            $html = '';
            // offline message
            if ($jlistConfig['offline.text'] != '') {
                $html .= JDHelper::getOnlyLanguageSubstring($jlistConfig['offline.text']);
            }
            echo $html;    
        }
    }     
    
?>