<a title="{TITLE*}{+START,IF,{$NOT,{VALIDATED}}} &ndash; {!event_purchase:_NOT_YET}{+END}" href="{URL*}"><img src="{$IMG*,{ICON}}" title="{+START,IF_NON_EMPTY,{TIME}}{TIME*} &ndash; {+END}{TITLE*}{+START,IF,{$NOT,{VALIDATED}}} &ndash; {!event_purchase:_NOT_YET}{+END}" alt="{+START,IF_NON_EMPTY,{TIME}}{TIME*} &ndash; {+END}{TITLE*}" /></a>{+START,IF,{RECURRING}} {!REPEAT_SUFFIX}{+END}
