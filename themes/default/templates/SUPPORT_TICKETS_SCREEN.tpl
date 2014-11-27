{TITLE}

{+START,IF_NON_EMPTY,{MESSAGE}}
	<p>{MESSAGE}</p>
{+END}

{+START,IF,{$NOT,{$IS_GUEST}}}
	<div class="box box___support_tickets_screen"><div class="box_inner vertical_alignment">
		<form title="{!FILTER}" class="float_surrounder" id="ticket_type_form" action="{$URL_FOR_GET_FORM*,{$SELF_URL,0,1}}" method="get" onsubmit="try { window.scrollTo(0,0); } catch(e) {}">
			{$HIDDENS_FOR_GET_FORM,{$SELF_URL,0,1},ticket_type_id}
			<label class="field_name" for="ticket_type_id">{!TICKET_TYPE}:</label>
			<select id="ticket_type_id" name="ticket_type_id" class="input_list_required">
				<option value="">&mdash;</option>
				{+START,LOOP,TYPES}
					<option value="{TICKET_TYPE_ID*}"{+START,IF,{SELECTED}} selected="selected"{+END}>{NAME*}</option>{$,You can also use {LEAD_TIME} to get the ticket type's lead time}
				{+END}
			</select>
			<input onclick="disable_button_just_clicked(this);" class="buttons__filter button_screen_item" type="submit" value="{!FILTER}" />
		</form>
	</div></div>

	{+START,IF_EMPTY,{LINKS}}
		{$?,{$HAS_PRIVILEGE,support_operator},<p class="nothing_here">{!NO_ENTRIES}</p>,<p>{!SUPPORT_NO_TICKETS}</p>}
	{+END}
	{+START,IF_NON_EMPTY,{LINKS}}
		<div class="wide_table_wrap"><table class="columned_table results_table wide_table support_tickets autosized_table">
			<thead>
				<tr>
					<th>
						{!SUPPORT_TICKET}
					</th>
					<th>
						{!TICKET_TYPE}
					</th>
					{+START,IF,{$NOT,{$MOBILE}}}
						<th>
							{!COUNT_POSTS}
						</th>
					{+END}
					<th>
						{!BY}
					</th>
					<th>
						{!LAST_POST}
					</th>
					<th>
						{!ASSIGNED_TO}
					</th>
				</tr>
			</thead>
			<tbody>
				{LINKS}
			</tbody>
		</table></div>
	{+END}
{+END}

<p class="buttons_group">
	<a class="buttons__add_ticket button_screen" rel="add" href="{ADD_TICKET_URL*}"><span>{!ADD_TICKET}</span></a>
</p>

