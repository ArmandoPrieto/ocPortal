<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2012

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		ecommerce
 */

class Hook_secpay
{

	/**
	 * Get the gateway username.
	 *
	 * @return string			The answer.
	 */
	function _get_username()
	{
		return ecommerce_test_mode()?get_option('ipn_test'):get_option('ipn');
	}

	/**
	 * Get the IPN URL.
	 *
	 * @return URLPATH		The IPN url.
	 */
	function get_ipn_url()
	{
		return 'https://www.secpay.com/java-bin/ValCard';
	}

	/**
	 * Generate a transaction ID.
	 *
	 * @return string			A transaction ID.
	 */
	function generate_trans_id()
	{
		return md5(uniqid(strval((mt_rand(0,32000))),true));
	}

	/**
	 * Make a transaction (payment) button.
	 *
	 * @param  ID_TEXT		The product codename.
	 * @param  SHORT_TEXT	The human-readable product title.
	 * @param  ID_TEXT		The purchase ID.
	 * @param  float			A transaction amount.
	 * @param  ID_TEXT		The currency to use.
	 * @return tempcode		The button
	 */
	function make_transaction_button($product,$item_name,$purchase_id,$amount,$currency)
	{
		$username=$this->_get_username();
		$ipn_url=$this->get_ipn_url();
		$trans_id=$this->generate_trans_id();
		$GLOBALS['SITE_DB']->query_insert('trans_expecting',array(
			'id'=>$trans_id,
			'e_purchase_id'=>$purchase_id,
			'e_item_name'=>$item_name,
			'e_member_id'=>get_member(),
			'e_amount'=>float_to_raw_string($amount),
			'e_ip_address'=>get_ip_address(),
			'e_session_id'=>get_session_id(),
			'e_time'=>time(),
			'e_length'=>NULL,
			'e_length_units'=>'',
		));
		$digest=md5($trans_id.float_to_raw_string($amount).get_option('ipn_password'));
		return do_template('ECOM_BUTTON_VIA_SECPAY',array('_GUID'=>'e68e80cb637f8448ef62cd7d73927722','PRODUCT'=>$product,'DIGEST'=>$digest,'TEST'=>ecommerce_test_mode(),'TRANS_ID'=>$trans_id,'ITEM_NAME'=>$item_name,'PURCHASE_ID'=>strval($purchase_id),'AMOUNT'=>float_to_raw_string($amount),'CURRENCY'=>$currency,'USERNAME'=>$username,'IPN_URL'=>$ipn_url));
	}

	/**
	 * Find details for a subscription in secpay format.
	 *
	 * @param  integer	The subscription length in the units.
	 * @param  ID_TEXT	The length units.
	 * @set    d w m y
	 * @return array		A tuple: the period in secpay units, the date of the first repeat
	 */
	function _translate_subscription_details($length,$length_units)
	{
		switch ($length_units)
		{
			case 'd':
				$length_units_2='daily';
				$single_length=60*60*24;
				break;
			case 'w':
				$length_units_2='weekly';
				$single_length=60*60*24*7;
				break;
			case 'm':
				$length_units_2='monthly';
				$single_length=60*60*24*31;
				break;
			case 'y':
				$length_units_2='yearly';
				$single_length=60*60*24*365;
				break;
		}
		if (($length_units=='m') && ($length==3))
		{
			$length_units_2='quarterly';
			$single_length=60*60*24*92;
		}
		$first_repeat=date('Ymd',time()+$single_length);

		return array($length_units_2,$first_repeat);
	}

	/**
	 * Make a subscription (payment) button.
	 *
	 * @param  ID_TEXT		The product codename.
	 * @param  SHORT_TEXT	The human-readable product title.
	 * @param  ID_TEXT		The purchase ID.
	 * @param  float			A transaction amount.
	 * @param  integer		The subscription length in the units.
	 * @param  ID_TEXT		The length units.
	 * @set    d w m y
	 * @param  ID_TEXT		The currency to use.
	 * @return tempcode		The button
	 */
	function make_subscription_button($product,$item_name,$purchase_id,$amount,$length,$length_units,$currency)
	{
		$username=$this->_get_username();
		$ipn_url=$this->get_ipn_url();
		$trans_id=$this->generate_trans_id();
		$digest=md5($trans_id.float_to_raw_string($amount).get_option('ipn_password'));
		list($length_units_2,$first_repeat)=$this->_translate_subscription_details($length,$length_units);
		$GLOBALS['SITE_DB']->query_insert('trans_expecting',array(
			'id'=>$trans_id,
			'e_purchase_id'=>$purchase_id,
			'e_item_name'=>$item_name,
			'e_member_id'=>get_member(),
			'e_amount'=>float_to_raw_string($amount),
			'e_ip_address'=>get_ip_address(),
			'e_session_id'=>get_session_id(),
			'e_time'=>time(),
			'e_length'=>$length,
			'e_length_units'=>$length_units,
		));
		return do_template('ECOM_SUBSCRIPTION_BUTTON_VIA_SECPAY',array('_GUID'=>'e5e6d6835ee6da1a6cf02ff8c2476aa6','PRODUCT'=>$product,'DIGEST'=>$digest,'TEST'=>ecommerce_test_mode(),'TRANS_ID'=>$trans_id,'FIRST_REPEAT'=>$first_repeat,'LENGTH'=>strval($length),'LENGTH_UNITS_2'=>$length_units_2,'ITEM_NAME'=>$item_name,'PURCHASE_ID'=>strval($purchase_id),'AMOUNT'=>float_to_raw_string($amount),'CURRENCY'=>$currency,'USERNAME'=>$username,'IPN_URL'=>$ipn_url));
	}

	/**
	 * Make a subscription cancellation button.
	 *
	 * @param  ID_TEXT		The purchase ID.
	 * @return tempcode		The button
	 */
	function make_cancel_button($purchase_id)
	{
		$cancel_url=build_url(array('page'=>'subscriptions','type'=>'cancel','id'=>$purchase_id),get_module_zone('subscriptions'));
		return do_template('ECOM_CANCEL_BUTTON_VIA_SECPAY',array('_GUID'=>'bd02018c985e2345be71eed537b2f841','CANCEL_URL'=>$cancel_url,'PURCHASE_ID'=>$purchase_id));
	}

	/**
	 * Find whether the hook auto-cancels (if it does, auto cancel the given trans-id).
	 *
	 * @param  string		Transaction ID to cancel
	 * @return ?boolean	True: yes. False: no. (NULL: cancels via a user-URL-directioning)
	 */
	function auto_cancel($trans_id)
	{
		require_lang('ecommerce');
		$username=$this->_get_username();
		$password=get_option('ipn_password');
		$password_2=get_option('vpn_password');
		$result=$this->_xml_rpc('https://www.secpay.com:443/secxmlrpc/make_call','SECVPN.repeatCardFullAddr',array($username,$password_2,$trans_id,-1,$password,'','','','','','repeat_change=true,repeat=false'),true);
		if (is_null($result)) return false;
		return (strpos($result,'&code=A&')!==false);
	}

	/**
	 * Find a transaction fee from a transaction amount. Regular fees aren't taken into account.
	 *
	 * @param  float	A transaction amount.
	 * @return float	The fee
	 */
	function get_transaction_fee($amount)
	{
		return 0.39; // the fee for <60 transactions per month. If it's more, I'd hope ocPortal's simplistic accountancy wasn't being relied on!
	}

	/**
	 * Get a list of card types.
	 *
	 * @param  ?string	The card type to select by default (NULL: don't care)
	 * @return tempcode	The list
	 */
	function nice_get_card_types($it=NULL)
	{
		$list=new ocp_tempcode();
		$array=array('Visa','Master Card','Switch','UK Maestro','Maestro','Solo','Delta','American Express','Diners Card','JCB');
		foreach ($array as $x)
		{
			$list->attach(form_input_list_entry($x,$it==$x));
		}
		return $list;
	}

	/**
	 * Perform a transaction.
	 *
	 * @param  ?ID_TEXT		The transaction ID (NULL: generate one)
	 * @param  SHORT_TEXT	Cardholder name
	 * @param  SHORT_TEXT	Card number
	 * @param  SHORT_TEXT	Transaction amount
	 * @param  SHORT_TEXT	Card Expiry date
	 * @param  integer		Card Issue number
	 * @param  SHORT_TEXT	Card Start date
	 * @param  SHORT_TEXT	Card Type
	 * @set    "Visa" "Master Card" "Switch" "UK Maestro" "Maestro" "Solo" "Delta" "American Express" "Diners Card" "JCB"
	 * @param  SHORT_TEXT	Card CV2 number (security number)
	 * @param  ?integer		The subscription length in the units. (NULL: not a subscription)
	 * @param  ?ID_TEXT		The length units. (NULL: not a subscription)
	 * @set    d w m y
	 * @return array			A tuple: success (boolean), trans-id (string), message (string), raw message (string)
	 */
	function do_transaction($trans_id,$name,$card_number,$amount,$expiry_date,$issue_number,$start_date,$card_type,$cv2,$length=NULL,$length_units=NULL)
	{
		if (is_null($trans_id)) $trans_id=$this->generate_trans_id();
		$username=$this->_get_username();
		$password_2=get_option('vpn_password');
		$digest=md5($trans_id.strval($amount).get_option('ipn_password'));
		$options='currency='.get_option('currency').',card_type='.str_replace(',','',$card_type).',digest='.$digest.',cv2='.strval(intval($cv2));
		if (ecommerce_test_mode()) $options.=',test_status=true';
		if (!is_null($length))
		{
			list($length_units_2,$first_repeat)=$this->_translate_subscription_details($length,$length_units);
			$options.=',repeat='.$first_repeat.'/'.$length_units_2.'/0/'.$amount;
		}

		require_lang('ecommerce');
		require_code('xmlrpc');
		$result=xml_rpc('https://www.secpay.com:443/secxmlrpc/make_call','SECVPN.validateCardFull',array($username,$password_2,$trans_id,get_ip_address(),$name,$card_number,$amount,$expiry_date,$issue_number,$start_date,'','','',$options));
		$pos_1=strpos($result,'<value>');
		if ($pos_1===false) fatal_exit(do_lang('INTERNAL_ERROR'));
		$pos_2=strpos($result,'</value>');
		$value=@html_entity_decode(trim(substr($result,$pos_1+7,$pos_2-$pos_1-7)),ENT_QUOTES,get_charset());
		if (substr($value,0,1)=='?') $value=substr($value,1);
		$_map=explode('&',$value);
		$map=array();
		foreach ($_map as $x)
		{
			$explode=explode('=',$x);
			if (count($explode)==2)
				$map[$explode[0]]=$explode[1];
		}

		$success=((array_key_exists('code',$map)) && (($map['code']=='A') || ($map['code']=='P:P')));
		$message_raw=array_key_exists('message',$map)?$map['message']:'';
		$message=$success?do_lang('ACCEPTED_MESSAGE',$message_raw):do_lang('DECLINED_MESSAGE',$message_raw);

		$purchase_id=post_param_integer('customfld1','-1');
		if(addon_installed('shopping'))
		{
			$this->store_shipping_address($purchase_id);
		}

		return array($success,$trans_id,$message,$message_raw);
	}

	/**
	 * Handle IPN's. The function may produce output, which would be returned to the Payment Gateway. The function may do transaction verification.
	 *
	 * @return array	A long tuple of collected data.
	 */
	function handle_transaction()
	{
		/*$myfile=fopen(get_file_base().'/data_custom/ecommerce.log','at');
		fwrite($myfile,serialize($_POST));
		fclose($myfile);*/

		$txn_id=post_param('trans_id');
		if (substr($txn_id,0,7)=='subscr_')
		{
			$subscription=true;
			$txn_id=substr($txn_id,7);
		} else
		{
			$subscription=false;
		}

		$transaction_rows=$GLOBALS['SITE_DB']->query_select('trans_expecting',array('*'),array('id'=>$txn_id),'',1);
		if (!array_key_exists(0,$transaction_rows)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
		$transaction_row=$transaction_rows[0];

		$member_id=$transaction_row['e_member_id'];
		$item_name=$subscription?'':$transaction_row['e_item_name'];
		$purchase_id=$transaction_row['e_purchase_id'];

		$code=post_param('code');
		$success=($code=='A');
		$message=post_param('message');
		if ($message=='')
		{
			switch ($code)
			{
				case 'P:A':
					$message=do_lang('PGE_A');
					break;
				case 'P:X':
					$message=do_lang('PGE_X');
					break;
				case 'P:P':
					$message=do_lang('PGE_P');
					break;
				case 'P:S':
					$message=do_lang('PGE_S');
					break;
				case 'P:E':
					$message=do_lang('PGE_E');
					break;
				case 'P:I':
					$message=do_lang('PGE_I');
					break;
				case 'P:C':
					$message=do_lang('PGE_C');
					break;
				case 'P:T':
					$message=do_lang('PGE_T');
					break;
				case 'P:N':
					$message=do_lang('PGE_N');
					break;
				case 'P:M':
					$message=do_lang('PGE_M');
					break;
				case 'P:B':
					$message=do_lang('PGE_B');
					break;
				case 'P:D':
					$message=do_lang('PGE_D');
					break;
				case 'P:V':
					$message=do_lang('PGE_V');
					break;
				case 'P:R':
					$message=do_lang('PGE_R');
					break;
				case 'P:#':
					$message=do_lang('PGE_HASH');
					break;
				case 'C':
					$message=do_lang('PGE_COMM');
					break;
				default:
					$message=do_lang('UNKNOWN');
			}
		}

		$payment_status=$success?'Completed':'Failed';
		$reason_code='';
		$pending_reason='';
		$memo='';
		$mc_gross=post_param('amount');
		$mc_currency=post_param('currency',''); // May be blank for subscription
		$email=$GLOBALS['FORUM_DRIVER']->get_member_email_address($member_id);

		// Validate
		$hash=post_param('hash');
		if ($subscription)
		{
			$my_hash=md5('trans_id='.$txn_id.'&'.'req_cv2=true'.'&'.get_option('ipn_digest'));
		} else
		{
			$repeat=$this->_translate_subscription_details($transaction_row['e_length'],$transaction_row['e_length_units']);
			$my_hash=md5('trans_id='.$txn_id.'&'.'req_cv2=true'.'&'.'repeat='.$repeat.'&'.get_option('ipn_digest'));
		}
		if ($hash!=$my_hash) my_exit(do_lang('IPN_UNVERIFIED'));

		if ($success)
		{
			require_code('notifications');
			dispatch_notification('payment_received',NULL,do_lang('PAYMENT_RECEIVED_SUBJECT',$txn_id,NULL,NULL,get_lang($member_id)),do_lang('PAYMENT_RECEIVED_BODY',float_format(floatval($mc_gross)),$mc_currency,get_site_name(),get_lang($member_id)),array($member_id),A_FROM_SYSTEM_PRIVILEGED);
		}

		// Subscription stuff
		if (get_param_integer('subc',0)==1)
		{
			if (!$success) $payment_status='SCancelled';
		}

		if ($success) $_url=build_url(array('page'=>'purchase','type'=>'finish','product'=>get_param('product',NULL)),get_module_zone('purchase'));
		else $_url=build_url(array('page'=>'purchase','type'=>'finish','cancel'=>1,'message'=>do_lang_tempcode('DECLINED_MESSAGE',$message)),get_module_zone('purchase'));
		$url=$_url->evaluate();

		echo http_download_file($url);

		if(addon_installed('shopping'))
		{
			$this->store_shipping_address($purchase_id);
		}

		return array($purchase_id,$item_name,$payment_status,$reason_code,$pending_reason,$memo,$mc_gross,$mc_currency,$txn_id,'');
	}

	/**
	 * Store shipping address for orders
	 *
	 * @param  AUTO_LINK		Order id
	 * @return ?mixed			Address id (NULL: No address record found)
	 */
	function store_shipping_address($order_id)
	{
		if(is_null(post_param('first_name',NULL))) return;

		if(is_null($GLOBALS['SITE_DB']->query_value_null_ok('shopping_order_addresses','id',array('order_id'=>$order_id))))
		{
			$shipping_address=array();
			$shipping_address['order_id']=$order_id;
			$shipping_address['address_name']=post_param('first_name','')." ".post_param('last_name','');
			$shipping_address['address_street']=post_param('ship_addr_1','');
			$shipping_address['address_zip']=post_param('ship_post_code','');
			$shipping_address['address_city']=post_param('ship_city','');
			$shipping_address['address_country']=	post_param('ship_country','');
			$shipping_address['receiver_email']='';//post_param('receiver_email','');

			return $GLOBALS['SITE_DB']->query_insert('shopping_order_addresses',$shipping_address,true);	
		}
	}

}


