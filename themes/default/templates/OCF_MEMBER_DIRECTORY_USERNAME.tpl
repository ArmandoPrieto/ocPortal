<span class="vertical_alignment">
	<img onmouseover="if (typeof window.activate_tooltip!='undefined') activate_tooltip(this,event,'{$OCF_MEMBER_HTML;^*,{ID}}','auto');" src="{$THUMBNAIL*,{$?,{$IS_EMPTY,{AVATAR_URL}},{$IMG,ocf_default_avatars/default},{AVATAR_URL}},18x18}" alt="" />

	<a href="{URL*}">{$DISPLAYED_USERNAME*,{USERNAME}}</a>

	{+START,IF,{$NOT,{VALIDATED}}}
		<span>{!MEMBER_IS_UNVALIDATED}</span>
	{+END}

	{+START,IF,{$NOT,{CONFIRMED}}}
		<span>{!MEMBER_IS_UNCONFIRMED}</span>
	{+END}
</span>
