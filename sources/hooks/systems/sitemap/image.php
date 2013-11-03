<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		galleries
 */

class Hook_sitemap_image extends Hook_sitemap_content
{
	protected $content_type='image';
	protected $screen_type='image';

	// If we have a different content type of entries, under this content type
	protected $entry_content_type=NULL;
	protected $entry_sitetree_hook=NULL;

	/**
	 * Find details of a position in the sitemap.
	 *
	 * @param  ID_TEXT  		The page-link we are finding.
	 * @param  ?string  		Callback function to send discovered page-links to (NULL: return).
	 * @param  ?array			List of node types we will return/recurse-through (NULL: no limit)
	 * @param  ?integer		How deep to go from the sitemap root (NULL: no limit).
	 * @param  integer		Our recursion depth (used to limit recursion, or to calculate importance of page-link, used for instance by Google sitemap [deeper is typically less important]).
	 * @param  boolean		Only go so deep as needed to find nodes with permission-support (typically, stopping prior to the entry-level).
	 * @param  ID_TEXT		The zone we will consider ourselves to be operating in (needed due to transparent redirects feature)
	 * @param  boolean		Whether to filter out non-validated content.
	 * @param  boolean		Whether to consider secondary categorisations for content that primarily exists elsewhere.
	 * @param  integer		A bitmask of SITEMAP_GATHER_* constants, of extra data to include.
	 * @param  ?array			Database row (NULL: lookup).
	 * @return ?array			Node structure (NULL: working via callback).
	 */
	function get_node($pagelink,$callback=NULL,$valid_node_types=NULL,$max_recurse_depth=NULL,$recurse_level=0,$require_permission_support=false,$zone='_SEARCH',$consider_secondary_categories=false,$consider_validation=false,$meta_gather=0,$row=NULL)
	{
		$_=$this->_create_partial_node_structure($pagelink,$callback,$valid_node_types,$max_recurse_depth,$recurse_level,$require_permission_support,$zone,$consider_secondary_categories,$consider_validation,$meta_gather,$row);
		if ($_===NULL) return array();
		list($content_id,$row,$partial_struct)=$_;

		$struct=array(
			'sitemap_priority'=>SITEMAP_IMPORTANCE_LOW,
			'sitemap_refreshfreq'=>'yearly',

			'permission_page'=>'cms_galleries', // Where privileges are overridden on
		)+$partial_struct;

		if ($callback!==NULL)
			call_user_func($callback,$struct);

		// Categories done after node callback, to ensure sensible ordering
		$children=$this->_get_children_nodes($content_id,$pagelink,$callback,$valid_node_types,$max_recurse_depth,$recurse_level,$require_permission_support,$zone,$consider_secondary_categories,$consider_validation,$meta_gather,$row);
		$struct['children']=$children;

		return ($callback===NULL)?$struct:NULL;
	}
}
