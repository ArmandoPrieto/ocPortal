<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		oc_worrdss
 */

class Hook_addon_registry_oc_worrdss
{
	/**
	 * Get a list of file permissions to set
	 *
	 * @return array			File permissions to set
	 */
	function get_chmod_array()
	{
		return array();
	}

	/**
	 * Get the version of ocPortal this addon is for
	 *
	 * @return float			Version number
	 */
	function get_version()
	{
		return ocp_version_number();
	}

	/**
	 * Get the addon category
	 *
	 * @return string			The category
	 */
	function get_category()
	{
		return 'Fun and Games';
	}

	/**
	 * Get the addon author
	 *
	 * @return string			The author
	 */
	function get_author()
	{
		return 'Chris Graham';
	}

	/**
	 * Find other authors
	 *
	 * @return array			A list of co-authors that should be attributed
	 */
	function get_copyright_attribution()
	{
		return array(
			'Laurynas Butkus',
		);
	}

	/**
	 * Get the addon licence (one-line summary only)
	 *
	 * @return string			The licence
	 */
	function get_licence()
	{
		return 'GPL';
	}

	/**
	 * Get the description of the addon
	 *
	 * @return string			Description of the addon
	 */
	function get_description()
	{
		return 'Block to generate random crosswords, based on meta keywords and top forum posters.

The block takes the following parameters: cols (default is 15), rows (default is 15), max_words (default is 15), param (this is a name for the crossword generated; it will cache against this name for the cols/rows/max_words, so that people get a consistent crossword).

When staff view the block, a message about the answers is posted.

We suggest sites use this block in a competition and award points to the first member to get it. i.e. by posting this within a forum topic where users can reply.

Note: the crossword does not have any interactivity and requires somewhere for users to reply such as a forum topic.

Usage example: [code=\"Comcode\"][block]main_crossword[/block][/code]';
	}

	/**
	 * Get a list of tutorials that apply to this addon
	 *
	 * @return array			List of tutorials
	 */
	function get_applicable_tutorials()
	{
		return array(
		);
	}

	/**
	 * Get a mapping of dependency types
	 *
	 * @return array			File permissions to set
	 */
	function get_dependencies()
	{
		return array(
			'requires'=>array(
				'OCF',
			),
			'recommends'=>array(
			),
			'conflicts_with'=>array(
			)
		);
	}

	/**
	 * Get a list of files that belong to this addon
	 *
	 * @return array			List of files
	 */
	function get_file_list()
	{
		return array(
			'sources_custom/hooks/systems/addon_registry/oc_worrdss.php',
			'sources_custom/php-crossword/index.html',
			'sources_custom/php-crossword/COPYING',
			'sources_custom/php-crossword/php_crossword.class.php',
			'sources_custom/php-crossword/php_crossword_cell.class.php',
			'sources_custom/php-crossword/php_crossword_client.class.php',
			'sources_custom/php-crossword/php_crossword_grid.class.php',
			'sources_custom/php-crossword/php_crossword_word.class.php',
			'sources_custom/miniblocks/main_crossword.php',
			'themes/default/css_custom/crossword.css',
			'sources_custom/php-crossword/.htaccess',
		);
	}
}