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
 * @package		pointstore
 */

class Hook_addon_registry_pointstore
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
	 * Get the description of the addon
	 *
	 * @return string			Description of the addon
	 */
	function get_description()
	{
		return 'Provide a number of virtual products to your members in exchange for the points they have accumulated by their activity';
	}

	/**
	 * Get a list of tutorials that apply to this addon
	 *
	 * @return array			List of tutorials
	 */
	function get_applicable_tutorials()
	{
		return array(
			'tut_points',
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
			'requires'=>array('points'),
			'recommends'=>array(),
			'conflicts_with'=>array(),
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
			'sources/hooks/systems/notifications/pointstore_request_custom.php',
			'sources/hooks/systems/notifications/pointstore_request_forwarding.php',
			'sources/hooks/systems/notifications/pointstore_request_pop3.php',
			'sources/hooks/systems/notifications/pointstore_request_quota.php',
			'sources/hooks/systems/config/average_gamble_multiplier.php',
			'sources/hooks/systems/config/banner_hit.php',
			'sources/hooks/systems/config/banner_imp.php',
			'sources/hooks/systems/config/banner_setup.php',
			'sources/hooks/systems/config/forw_url.php',
			'sources/hooks/systems/config/highlight_name.php',
			'sources/hooks/systems/config/initial_banner_hits.php',
			'sources/hooks/systems/config/initial_quota.php',
			'sources/hooks/systems/config/is_on_banner_buy.php',
			'sources/hooks/systems/config/is_on_forw_buy.php',
			'sources/hooks/systems/config/is_on_gambling_buy.php',
			'sources/hooks/systems/config/is_on_highlight_name_buy.php',
			'sources/hooks/systems/config/is_on_pop3_buy.php',
			'sources/hooks/systems/config/is_on_topic_pin_buy.php',
			'sources/hooks/systems/config/mail_server.php',
			'sources/hooks/systems/config/max_quota.php',
			'sources/hooks/systems/config/maximum_gamble_amount.php',
			'sources/hooks/systems/config/maximum_gamble_multiplier.php',
			'sources/hooks/systems/config/minimum_gamble_amount.php',
			'sources/hooks/systems/config/pop_url.php',
			'sources/hooks/systems/config/quota.php',
			'sources/hooks/systems/config/quota_url.php',
			'sources/hooks/systems/config/topic_pin.php',
			'sources/hooks/systems/addon_registry/pointstore.php',
			'sources/hooks/modules/admin_import_types/pointstore.php',
			'sources/hooks/systems/cron/topic_pin.php',
			'sources/hooks/systems/config/topic_pin_max_days.php',
			'themes/default/templates/POINTSTORE_PRICES_FORM_WRAP.tpl',
			'themes/default/templates/POINTSTORE_CUSTOM.tpl',
			'themes/default/templates/POINTSTORE_CUSTOM_ITEM_SCREEN.tpl',
			'themes/default/templates/POINTSTORE_GAMBLING.tpl',
			'themes/default/templates/POINTSTORE_HIGHLIGHT_NAME.tpl',
			'themes/default/templates/POINTSTORE_HIGHLIGHT_NAME_SCREEN.tpl',
			'themes/default/templates/POINTSTORE_PERMISSION.tpl',
			'themes/default/templates/POINTSTORE_TOPIC_PIN.tpl',
			'themes/default/templates/POINTSTORE_SCREEN.tpl',
			'themes/default/templates/POINTSTORE_CONFIRM_SCREEN.tpl',
			'themes/default/templates/POINTSTORE_FORWARDER_MAIL.tpl',
			'themes/default/templates/POINTSTORE_ITEM.tpl',
			'themes/default/templates/POINTSTORE_LOG_SCREEN.tpl',
			'themes/default/templates/POINTSTORE_MAIL.tpl',
			'themes/default/templates/POINTSTORE_MFORWARDING_LINK.tpl',
			'themes/default/templates/POINTSTORE_MPOP3_LINK.tpl',
			'themes/default/templates/POINTSTORE_POP3_SCREEN.tpl',
			'themes/default/templates/POINTSTORE_POP3_ACTIVATE.tpl',
			'themes/default/templates/POINTSTORE_POP3_MAIL.tpl',
			'themes/default/templates/POINTSTORE_POP3_QUOTA.tpl',
			'themes/default/templates/POINTSTORE_PRICE_SCREEN.tpl',
			'themes/default/templates/POINTSTORE_QUOTA.tpl',
			'themes/default/templates/POINTSTORE_QUOTA_MAIL.tpl',
			'adminzone/pages/modules/admin_pointstore.php',
			'lang/EN/pointstore.ini',
			'site/pages/modules/pointstore.php',
			'sources/hooks/blocks/main_staff_checklist/pointstore.php',
			'sources/hooks/modules/pointstore/.htaccess',
			'sources/hooks/modules/pointstore/custom.php',
			'sources/hooks/modules/pointstore/forwarding.php',
			'sources/hooks/modules/pointstore/gambling.php',
			'sources/hooks/modules/pointstore/highlight_name.php',
			'sources/hooks/modules/pointstore/index.html',
			'sources/hooks/modules/pointstore/permission.php',
			'sources/hooks/modules/pointstore/pop3.php',
			'sources/hooks/modules/pointstore/topic_pin.php',
			'sources/hooks/systems/do_next_menus/pointstore.php',
			'sources/pointstore.php',
		);
	}


	/**
	* Get mapping between template names and the method of this class that can render a preview of them
	*
	* @return array			The mapping
	*/
	function tpl_previews()
	{
		return array(
				'POINTSTORE_LOG_SCREEN.tpl'=>'administrative__pointstore_log_screen',
				'POINTSTORE_PRICES_FORM_WRAP.tpl'=>'administrative__pointstore_price_screen',
				'POINTSTORE_PRICE_SCREEN.tpl'=>'administrative__pointstore_price_screen',
				'POINTSTORE_CONFIRM_SCREEN.tpl'=>'pointstore_confirm_screen',
				'POINTSTORE_FORWARDER_MAIL.tpl'=>'pointstore_forwarder_mail',
				'POINTSTORE_POP3_ACTIVATE.tpl'=>'pointstore_pop3_screen',
				'POINTSTORE_POP3_QUOTA.tpl'=>'pointstore_pop3_screen',
				'POINTSTORE_POP3_SCREEN.tpl'=>'pointstore_pop3_screen',
				'POINTSTORE_POP3_MAIL.tpl'=>'pointstore_pop3_mail',
				'POINTSTORE_QUOTA.tpl'=>'pointstore_quota',
				'POINTSTORE_QUOTA_MAIL.tpl'=>'pointstore_quota_mail',
				'POINTSTORE_CUSTOM_ITEM_SCREEN.tpl'=>'pointstore_custom_item_screen',
				'POINTSTORE_HIGHLIGHT_NAME_SCREEN.tpl'=>'pointstore_highlight_name_screen',
				'POINTSTORE_ITEM.tpl'=>'pointstore_screen',
				'POINTSTORE_MFORWARDING_LINK.tpl'=>'pointstore_screen',
				'POINTSTORE_MPOP3_LINK.tpl'=>'pointstore_screen',
				'POINTSTORE_MAIL.tpl'=>'pointstore_screen',
				'POINTSTORE_SCREEN.tpl'=>'pointstore_screen',
				'POINTSTORE_CUSTOM.tpl'=>'pointstore_custom',
				'POINTSTORE_GAMBLING.tpl'=>'pointstore_gambling',
				'POINTSTORE_HIGHLIGHT_NAME.tpl'=>'pointstore_highlight_name',
				'POINTSTORE_PERMISSION.tpl'=>'pointstore_permission',
				'POINTSTORE_TOPIC_PIN.tpl'=>'pointstore_topic_pin',
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__administrative__pointstore_log_screen()
	{
		$cells=new ocp_tempcode();
		foreach (placeholder_array() as $k=>$v)
		{
			$cells->attach(do_lorem_template('RESULTS_TABLE_FIELD',array('VALUE'=>lorem_word()),NULL,false,'RESULTS_TABLE_FIELD'));
		}
		$header_row=do_lorem_template('RESULTS_TABLE_ENTRY',array('VALUES'=>$cells),NULL,false,'RESULTS_TABLE_ENTRY');

		$out=new ocp_tempcode();
		foreach (placeholder_array() as $k=>$v)
		{
			$cells=new ocp_tempcode();
			foreach (placeholder_array() as $_k=>$_v)
			{
				$cells->attach(do_lorem_template('COLUMNED_TABLE_ROW_CELL',array('VALUE'=>$_v)));
			}

			$out->attach(do_lorem_template('COLUMNED_TABLE_ROW',array('CELLS'=>$cells)));
		}

		$content=do_lorem_template('COLUMNED_TABLE',array('HEADER_ROW'=>$header_row,'ROWS'=>$out));

		return array(
			lorem_globalise(
				do_lorem_template('POINTSTORE_LOG_SCREEN',array(
					'TITLE'=>lorem_title(),
					'CONTENT'=>$content
				)
			),NULL,'',true),
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__administrative__pointstore_price_screen()
	{
		//This is for getting the do_ajax_request() javascript function.
		require_javascript('javascript_ajax');

		$warning_details=do_lorem_template('WARNING_BOX',array('WARNING'=>lorem_phrase()));

		$add_forms=new ocp_tempcode();
		foreach (placeholder_array() as $k=>$v)
		{
			$add_forms->attach(do_lorem_template('POINTSTORE_PRICES_FORM_WRAP',array('TITLE'=>lorem_phrase(),'FORM'=>placeholder_form())));
		}

		return array(
			lorem_globalise(
				do_lorem_template('POINTSTORE_PRICE_SCREEN',array(
					'PING_URL'=>placeholder_url(),
					'WARNING_DETAILS'=>$warning_details,
					'TITLE'=>lorem_title(),
					'EDIT_FORM'=>placeholder_form(),
					'ADD_FORMS'=>$add_forms,
				)
			),NULL,'',true),
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__pointstore_confirm_screen()
	{
		return array(
			lorem_globalise(
				do_lorem_template('POINTSTORE_CONFIRM_SCREEN',array(
					'TITLE'=>lorem_title(),
					'KEEP'=>'',
					'ACTION'=>lorem_phrase(),
					'COST'=>lorem_phrase(),
					'POINTS_AFTER'=>lorem_phrase(),
					'PROCEED_URL'=>placeholder_url(),
					'MESSAGE'=>lorem_phrase(),
					'CANCEL_URL'=>placeholder_url(),
					'page'=>lorem_phrase(),
						)
			),NULL,'',true),
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__pointstore_forwarder_mail()
	{
		$temp=do_lorem_template('POINTSTORE_FORWARDER_MAIL',array('ENCODED_REASON'=>lorem_phrase(),'EMAIL'=>lorem_word(),'PREFIX'=>lorem_phrase(),'SUFFIX'=>lorem_phrase(),'FORW_URL'=>placeholder_url(),'SUFFIX_PRICE'=>lorem_phrase()));

		return array(
			lorem_globalise($temp,NULL,'',true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__pointstore_pop3_screen()
	{
		$activate=do_lorem_template('POINTSTORE_POP3_ACTIVATE',array('ACTIVATE_URL'=>placeholder_url(),'INITIAL_QUOTA'=>placeholder_number()));

		$quota=do_lorem_template('POINTSTORE_POP3_QUOTA',array('MAX_QUOTA'=>placeholder_number(),'QUOTA_URL'=>placeholder_url()));

		return array(
			lorem_globalise(
				do_lorem_template('POINTSTORE_POP3_SCREEN',array(
					'TITLE'=>lorem_title(),
					'ACTIVATE'=>$activate,
					'QUOTA'=>$quota,
					'INITIAL_QUOTA'=>placeholder_number()
				)
			),NULL,'',true),
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__pointstore_pop3_mail()
	{
		$temp=do_lorem_template('POINTSTORE_POP3_MAIL',array('EMAIL'=>lorem_word(),'ENCODED_REASON'=>lorem_phrase(),'LOGIN'=>lorem_phrase(),'QUOTA'=>placeholder_number(),'MAIL_SERVER'=>lorem_phrase(),'PASSWORD'=>lorem_phrase(),'PREFIX'=>lorem_phrase(),'SUFFIX'=>lorem_phrase(),'POP3_URL'=>placeholder_url(),'SUFFIX_PRICE'=>placeholder_number()));

// 		$out=comcode_to_temcode($temp);

		return array(
			lorem_globalise(
				$temp,NULL,'',true
			),
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__pointstore_quota()
	{
		require_css('forms');

		$input=do_lorem_template('FORM_SCREEN_INPUT_INTEGER',array('TABINDEX'=>placeholder_number(),'REQUIRED'=>'_required','NAME'=>lorem_word(),'DEFAULT'=>lorem_word()));
		$fields=do_lorem_template('FORM_SCREEN_FIELD',array('REQUIRED'=>true,'SKIP_LABEL'=>false,'NAME'=>lorem_word(),'PRETTY_NAME'=>lorem_word(),'DESCRIPTION'=>lorem_sentence_html(),'DESCRIPTION_SIDE'=>'','INPUT'=>$input,'COMCODE'=>''));

		$text=do_lorem_template('POINTSTORE_QUOTA',array('POINTS_LEFT'=>placeholder_number(),'PRICE'=>placeholder_number(),'TOP_AMOUNT'=>placeholder_number(),'EMAIL'=>lorem_word()));

		return array(
			lorem_globalise(
				do_lorem_template('FORM_SCREEN',array(
					'GET'=>true,
					'HIDDEN'=>'',
					'URL'=>placeholder_url(),
					'TITLE'=>lorem_title(),
					'FIELDS'=>$fields,
					'TEXT'=>$text,
					'SUBMIT_NAME'=>lorem_word()
				)
			),NULL,'',true),
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__pointstore_quota_mail()
	{
		return array(
			lorem_globalise(
				do_lorem_template('POINTSTORE_QUOTA_MAIL',array(
					'ENCODED_REASON'=>lorem_phrase(),
					'QUOTA'=>placeholder_number(),
					'EMAIL'=>lorem_word(),
					'QUOTA_URL'=>placeholder_url(),
					'PRICE'=>placeholder_number()
				)
			),NULL,'',true),
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__pointstore_custom_item_screen()
	{
		return array(
			lorem_globalise(
				do_lorem_template('POINTSTORE_CUSTOM_ITEM_SCREEN',array(
					'TITLE'=>lorem_title(),
					'COST'=>placeholder_number(),
					'REMAINING'=>placeholder_number(),
					'NEXT_URL'=>placeholder_url(),
					'ONE_PER_MEMBER'=>true,
				)
			),NULL,'',true),
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__pointstore_highlight_name_screen()
	{
		return array(
			lorem_globalise(
				do_lorem_template('POINTSTORE_HIGHLIGHT_NAME_SCREEN',array(
					'TITLE'=>lorem_title(),
					'COST'=>placeholder_number(),
					'REMAINING'=>placeholder_number(),
					'NEXT_URL'=>placeholder_url(),
				)
			),NULL,'',true),
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__pointstore_screen()
	{
		$items=new ocp_tempcode();
		foreach (placeholder_array() as $k=>$v)
		{
			$item=do_lorem_template('POINTSTORE_ITEM',array('ITEM'=>lorem_phrase()));
			$items->attach($item);
		}

		$pointstore_mail_pop3_link=do_lorem_template('POINTSTORE_MPOP3_LINK',array('POP3_URL'=>placeholder_url()));

		$pointstore_mail_forwarding_link=do_lorem_template('POINTSTORE_MFORWARDING_LINK',array('FORWARDING_URL'=>placeholder_url()));

		$mail_tpl=do_lorem_template('POINTSTORE_MAIL',array('POINTSTORE_MAIL_POP3_LINK'=>$pointstore_mail_pop3_link,'POINTSTORE_MAIL_FORWARDING_LINK'=>$pointstore_mail_forwarding_link));

		$items->attach(do_lorem_template('POINTSTORE_ITEM',array('ITEM'=>$mail_tpl)));

		return array(
			lorem_globalise(
				do_lorem_template('POINTSTORE_SCREEN',array(
					'TITLE'=>lorem_title(),
					'ITEMS'=>$items,
					'POINTS_LEFT'=>placeholder_number(),
					'USERNAME'=>lorem_phrase(),
				)
			),NULL,'',true),
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__pointstore_custom()
	{
		return array(
			lorem_globalise(
				do_lorem_template('POINTSTORE_CUSTOM',array(
					'TITLE'=>lorem_phrase(),
					'DESCRIPTION'=>lorem_sentence(),
					'NEXT_URL'=>placeholder_url(),
				)
			),NULL,'',true),
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__pointstore_gambling()
	{
		return array(
			lorem_globalise(
				do_lorem_template('POINTSTORE_GAMBLING',array(
					'NEXT_URL'=>placeholder_url()
				)
			),NULL,'',true),
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__pointstore_highlight_name()
	{
		return array(
			lorem_globalise(
				do_lorem_template('POINTSTORE_HIGHLIGHT_NAME',array(
					'NEXT_URL'=>placeholder_url()
				)
			),NULL,'',true),
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__pointstore_permission()
	{
		return array(
			lorem_globalise(
				do_lorem_template('POINTSTORE_PERMISSION',array(
					'TITLE'=>lorem_phrase(),
					'DESCRIPTION'=>lorem_sentence(),
					'NEXT_URL'=>placeholder_url()
				)
			),NULL,'',true),
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__pointstore_topic_pin()
	{
		return array(
			lorem_globalise(
				do_lorem_template('POINTSTORE_TOPIC_PIN',array(
					'NEXT_URL'=>placeholder_url()
				)
			),NULL,'',true),
		);
	}
}
