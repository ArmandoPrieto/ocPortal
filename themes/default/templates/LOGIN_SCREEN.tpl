{TITLE}

{$SET,login_screen,1}

<div class="login_page">
	{+START,BOX,,,light}
		{+START,IF_NON_EMPTY,{JOIN_LINK}}
			{!LOGIN_TEXT,<a href="{JOIN_LINK*}"><strong>{!JOIN_HERE}</strong></a>}
		{+END}
		{+START,IF_EMPTY,{JOIN_LINK}}
			{!LOGIN_TEXT_NO_JOIN}
		{+END}
	{+END}

	<form title="{!_LOGIN}" onsubmit="if (checkFieldForBlankness(this.elements['login_username'],event)) { disable_button_just_clicked(this); return true; } return false;" action="{LOGIN_URL*}" method="post" class="autocomplete">
		<div>
			{PASSION}

			<div class="float_surrounder">
				{+START,IF,{$MOBILE}}
					<p class="constrain_field">
						<label for="login_username">{!USERNAME}{+START,IF,{$AND,{$OCF},{$CONFIG_OPTION,one_per_email_address}}} / {!EMAIL_ADDRESS}{+END}</label>
						<input maxlength="80" class="wide_field" accesskey="l" type="text" value="{USERNAME*}" id="login_username" name="login_username" size="25" />
					</p>

					<p class="constrain_field">
						<label for="password">{!PASSWORD}</label>
						<input maxlength="255" class="wide_field" type="password" id="password" name="password" size="25" />
					</p>
				{+END}

				{+START,IF,{$NOT,{$MOBILE}}}
					<table summary="{!MAP_TABLE}" class="variable_table login_page_form">
						<tbody>
							<tr>
								<th class="de_th">{!USERNAME}:</th>
								<td>
									<div class="accessibility_hidden"><label for="login_username">{!USERNAME}</label></div>
									<input maxlength="80" accesskey="l" type="text" value="{USERNAME*}" id="login_username" name="login_username" size="25" />
								</td>
							</tr>
							<tr>
								<th class="de_th">{!PASSWORD}:</th>
								<td>
									<div class="accessibility_hidden"><label for="password">{!PASSWORD}</label></div>
									<input maxlength="255" type="password" id="password" name="password" size="25" />
								</td>
							</tr>
							<tr>
								<td colspan="2">&nbsp;</td>
							</tr>
						</tbody>
					</table>
				{+END}

				<div class="login_page_options">
					<p><label for="remember">
					  <input id="remember" type="checkbox" value="1" name="remember" {+START,IF,{$OR,{$EQ,{$_POST,remember},1},{$CONFIG_OPTION,remember_me_by_default}}}checked="checked" {+END}{+START,IF,{$NOT,{$CONFIG_OPTION,remember_me_by_default}}}onclick="if (this.checked) { var t=this; window.fauxmodal_confirm('{!REMEMBER_ME_COOKIE;}',function(answer) { if (!answer) t.checked=false; } ); }" {+END}/>
					  <span class="field_name">{!REMEMBER_ME}</span>
					</label><br />
					<span class="associated_details">{!REMEMBER_ME_TEXT}</span></p>

					{+START,IF,{$CONFIG_OPTION,is_on_invisibility}}
						<p><label for="login_invisible">
							<input id="login_invisible" type="checkbox" value="1" name="login_invisible" />
							<span class="field_name">{!INVISIBLE}</span>
						</label><br />
						<span class="associated_details">{!INVISIBLE_TEXT}</span></p>
					{+END}
				</div>
			</div>

			<div class="proceed_button">
				<input class="button_page" type="submit" value="{!_LOGIN}" />
			</div>
		</div>
	</form>

	{+START,IF_NON_EMPTY,{EXTRA}}
	<p class="login_note">
		{EXTRA}
	</p>
	{+END}
</div>

<script type="text/javascript">// <![CDATA[
addEventListenerAbstract(window,'real_load',function () {
	if ((typeof document.activeElement=='undefined') || (document.activeElement!=document.getElementById('password')))
		document.getElementById('login_username').focus();
} );
//]]></script>
