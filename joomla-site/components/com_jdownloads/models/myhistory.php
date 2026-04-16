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

require_once JPATH_SITE.'/components/com_jdownloads/helpers/query.php';
jimport('joomla.application.component.modellist');

/**
 * This models supports retrieving lists of downloads.
 *
 * @package		Joomla.Site
 * @subpackage	com_content
 */
class jdownloadsModelMyHistory extends JModelList
{

	/**
	 * Constructor.
	 *
	 * @param	array	An optional associative array of configuration settings.
	 * @see		JController
	 */
	public function __construct($config = array())
	{
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'id', 'a.id',
                'type', 'a.type',
                'log_file_id', 'a.log_file_id',
                'log_file_size', 'a.log_file_size',
                'log_file_name', 'a.log_file_name',
                'log_title', 'a.log_title',
                'log_ip', 'a.log_ip',
                'log_datetime', 'a.log_datetime',
                'log_user', 'a.log_user',
                'log_browser', 'a.log_browser',
                'language', 'a.language',
                'ordering', 'a.ordering',
            );
        }

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @return	void
	 */
	protected function populateState($ordering = 'ordering', $direction = 'ASC')
	{
        global $jlistConfig;
        
        $app = JFactory::getApplication();
        $jinput = JFactory::getApplication()->input;

        // Load the parameters. Merge Global and Menu Item params into new object
        $params = $app->getParams();
        $menuParams = new JRegistry;

        if ($menu = $app->getMenu()->getActive()) {
            $menuParams->loadString($menu->params);
        }

        $mergedParams = clone $menuParams;
        $mergedParams->merge($params);

        $this->setState('params', $mergedParams);
        $user        = JFactory::getUser();
        
        $listOrderNew = false;
                
        // Create a new query object.
        $db        = $this->getDbo();
        $query     = $db->getQuery(true);
        $menu_params = $this->state->params;

        // filter.order
        $orderCol = $app->getUserStateFromRequest('com_jdownloads.downloads.filter_order', 'filter_order', '', 'string');
        
        if (!in_array($orderCol, $this->filter_fields) || $orderCol == '') {
            // use order from menu settings 
            $filesOrderby = $params->get('orderby_sec', 'order');
            $orderCol    = JDContentHelperQuery::orderHistoryBy($filesOrderby) . ' ';
            $order_array  = explode(' ', $orderCol);
            if (count($order_array) > 2){
                $orderCol       = $order_array[0];
                $listOrderNew   = $order_array[1];
            }

        }
        $this->setState('list.ordering', $orderCol);

        $listOrder = $app->getUserStateFromRequest('com_jdownloads.downloads.filter_order_Dir', 'filter_order_Dir', '', 'cmd');
        if (!in_array(strtoupper($listOrder), array('ASC', 'DESC', ''))) {
            $listOrder = 'ASC';
        }
        if (!$listOrderNew){
            $this->setState('list.direction', $listOrder);
        } else {
            $this->setState('list.direction', $listOrderNew);
        }    

        $this->setState('list.start', JRequest::getUInt('limitstart', 0));

        $limit= $app->input->get('limit', false, 'uint');
        
        if ($limit === false){
            if ((int)$menu_params->get('display_num') > 0) {
                $limit = (int)$menu_params->get('display_num');
            } else {
                $limit = (int)$jlistConfig['files.per.side'];
            }
        }
        
        $this->setState('list.limit', $limit);

        $this->setState('filter.language', $app->getLanguageFilter());

        $this->setState('layout', $jinput->get('layout'));
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param	string		$id	A prefix for the store id.
	 *
	 * @return	string		A store id.
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.date_field');

		return parent::getStoreId($id);
	}

	/**
	 * Get the master query for retrieving a list of downloads subject to the model state.
	 *
	 * @return	JDatabaseQuery
	 */
	function getListQuery()
	{
		global $jlistConfig; 
        
        // Create a new query object.
		$db = $this->getDbo();
		$query = $db->getQuery(true);
        $user    = JFactory::getUser();
        $groups  = implode (',', $user->getAuthorisedViewLevels());
        
		// Select the required fields from the table.
		$query->select(
            $this->getState(
                'list.select',
                'a.id, a.type, a.log_file_id, a.log_file_size, a.log_file_name, a.log_title, a.log_ip, a.log_datetime, a.log_user, a.log_browser, '  .
                'a.language, a.ordering'
            )
        );
                
        $query->from('`#__jdownloads_logs` AS a');
        
		// Filter by user id
        $query->where('a.log_user = '.$db->Quote($user->id)); 
        
		// Add the list ordering clause.
        $order = $this->getState('list.ordering', 'a.ordering').' '.$this->getState('list.direction', 'ASC');
        $order = str_replace('DESC   DESC','DESC', $order);
        $query->order($order);
		
		return $query;
	}

	/**
	 * Method to get a list of downloads.
	 *
	 * Overriden to inject convert the attribs field into a JParameter object.
	 *
	 * @return	mixed	An array of objects on success, false on failure.
	 */
	public function getItems()
	{
		$items	= parent::getItems();

		// Get the global params
		$globalParams = JComponentHelper::getParams('com_jdownloads', true);

		// Convert the parameter fields into objects.
		foreach ($items as &$item)
		{
			$downloadParams = new JRegistry;
			$item->layout = $downloadParams->get('layout');
			$item->params = clone $this->getState('params');
		}

		return $items;
	}

	public function getStart()
	{
		return $this->getState('list.start');
	}
}
