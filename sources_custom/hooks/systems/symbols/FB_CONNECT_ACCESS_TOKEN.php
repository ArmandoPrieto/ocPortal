<?php

class Hook_symbol_FB_CONNECT_ACCESS_TOKEN
{
	function run($param)
	{
		$value='';
		if (get_forum_type()=='ocf')
		{
			if (!is_guest()) // A little crazy, but we need to do this as FB does not expire the cookie consistently, although oauth would have failed when creating a session against it
			{
				require_code('facebook_connect');
				global $FACEBOOK_CONNECT;
				if (!is_null($FACEBOOK_CONNECT))
				{
					$value=strval($FACEBOOK_CONNECT->getAccessToken());
					if ($value=='0') $value='';
				}
			}
		}
		return $value;
	}
}
