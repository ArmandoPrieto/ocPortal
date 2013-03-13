<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2013

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		ocf_forum
 */

/**
 * Module page class.
 */
class Module_topicview
{
	var $id;

	/**
	 * Standard modular info function.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
	function info()
	{
		$info=array();
		$info['author']='Chris Graham';
		$info['organisation']='ocProducts';
		$info['hacked_by']=NULL;
		$info['hack_version']=NULL;
		$info['version']=2;
		$info['locked']=false;
		return $info;
	}

	/**
	 * Standard modular entry-point finder function.
	 *
	 * @return ?array	A map of entry points (type-code=>language-code) (NULL: disabled).
	 */
	function get_entry_points()
	{
		return array();
	}

	/**
	 * Standard modular run function.
	 *
	 * @return tempcode	The result of execution.
	 */
	function run()
	{
		if (get_forum_type()!='ocf') warn_exit(do_lang_tempcode('NO_OCF')); else ocf_require_all_forum_stuff();
		require_code('ocf_topicview');
		require_css('ocf');

		inform_non_canonical_parameter('threaded');

		$start=get_param_integer('topic_start',0);
		$default_max=intval(get_option('forum_posts_per_page'));
		$max=get_param_integer('topic_max',$default_max);
		if ($max==0) $max=$default_max;
		if ($max==0) $max=1;
		$first_unread_id=-1;

		foreach (array_keys($_GET) as $key)
			if (substr($key,0,3)=='kfs') inform_non_canonical_parameter($key);

		$type=get_param('type','misc');

		$id=get_param_integer('id',NULL);
		if ((is_guest()) && (is_null($id))) access_denied('NOT_AS_GUEST');

		if ($type=='findpost')
		{
			$post_id=get_param_integer('id');
			$redirect=find_post_id_url($post_id);
			require_code('site2');
			assign_refresh($redirect,0.0);
			return do_template('REDIRECT_SCREEN',array('_GUID'=>'76e6d34c20a4f5284119827e41c7752f','URL'=>$redirect,'TITLE'=>get_screen_title('VIEW_TOPIC'),'TEXT'=>do_lang_tempcode('REDIRECTING')));
		} else
		{
			if ($type=='first_unread')
			{
				$redirect=find_first_unread_url($id);
				require_code('site2');
				assign_refresh($redirect,0.0);
				return do_template('REDIRECT_SCREEN',array('_GUID'=>'12c5d16f60e8c4df03536d9a7a932528','URL'=>$redirect,'TITLE'=>get_screen_title('VIEW_TOPIC'),'TEXT'=>do_lang_tempcode('REDIRECTING')));
			}
		}

		if (!is_null($id))
			set_feed_url('?mode=ocf_topicview&filter='.strval($id));

		$view_poll_results=get_param_integer('view_poll_results',0);

		// Mark as read
		if (!is_null($id))
		{
			$this->id=$id;
			register_shutdown_function(array($this,'_update_read_status')); // done at end after output in case of locking (don't make the user wait)
		}

		// Load up topic info
		$topic_info=ocf_read_in_topic($id,$start,$max,$view_poll_results==1);
		set_extra_request_metadata($topic_info['meta_data']);
		global $SEO_TITLE;
		$SEO_TITLE=do_lang('_VIEW_TOPIC',$topic_info['title']);

		// Render posts according to whether threaded or not
		$threaded=($topic_info['is_threaded']==1);
		$may_reply=(array_key_exists('may_reply',$topic_info)) && (($topic_info['is_open']) || (array_key_exists('may_post_closed',$topic_info)));
		if (!$threaded)
		{
			$GLOBALS['META_DATA']['description']=$topic_info['description'];

			// Poster detail hooks
			$hooks=find_all_hooks('modules','topicview');
			$hook_objects=array();
			foreach (array_keys($hooks) as $hook)
			{
				require_code('hooks/modules/topicview/'.filter_naughty_harsh($hook));
				$object=object_factory('Hook_'.filter_naughty_harsh($hook),true);
				if (is_null($object)) continue;
				$hook_objects[$hook]=$object;
			}

			// Render non-threaded
			$posts=new ocp_tempcode();
			$replied=false;
			if (is_null($topic_info['forum_id']))
			{
				decache('side_ocf_private_topics',array(get_member()));
				decache('_new_pp',array(get_member()));
			}
			$second_poster=$topic_info['first_poster'];
			foreach ($topic_info['posts'] as $array_id=>$_postdetails)
			{
				if ($array_id==0)
				{
					$description=$topic_info['description'];
				} else $description=NULL;

				if ($_postdetails['poster']==get_member()) $replied=true;

				if (($array_id==1 && $start==0) || ($array_id==0 && $start!=0)) $second_poster=$_postdetails['poster'];

				if (array_key_exists('last_edit_time',$_postdetails))
				{
					$last_edited=do_template('OCF_TOPIC_POST_LAST_EDITED',array(
						'_GUID'=>'77a28e8bc3cf2ec2211aafdb5ba192bf',
						'LAST_EDIT_DATE_RAW'=>is_null($_postdetails['last_edit_time'])?'':strval($_postdetails['last_edit_time']),
						'LAST_EDIT_DATE'=>$_postdetails['last_edit_time_string'],
						'LAST_EDIT_PROFILE_URL'=>$GLOBALS['FORUM_DRIVER']->member_profile_url($_postdetails['last_edit_by'],false,true),
						'LAST_EDIT_USERNAME'=>$_postdetails['last_edit_by_username'],
					));
				} else $last_edited=new ocp_tempcode();
				$last_edited_raw=(array_key_exists('last_edit_time',$_postdetails))?(is_null($_postdetails['last_edit_time'])?'':strval($_postdetails['last_edit_time'])):'0';

				$is_spacer_post=$_postdetails['is_spacer_post'];

				// Post buttons
				$buttons=new ocp_tempcode();
				if (!$is_spacer_post)
				{
					$buttons=ocf_render_post_buttons($topic_info,$_postdetails,$may_reply);
				}

				// User online status
				$poster_online=mixed();
				if ((get_option('is_on_show_online')=='1') && (!is_guest($_postdetails['poster'])))
				{
					$poster_online=member_is_online($_postdetails['poster']);
				}

				// Avatar
				if ((array_key_exists('poster_avatar',$_postdetails)) && ($_postdetails['poster_avatar']!=''))
				{
					$post_avatar=do_template('OCF_TOPIC_POST_AVATAR',array('_GUID'=>'d647ada9c11d56eedc0ff7894d33e83c','AVATAR'=>$_postdetails['poster_avatar']));
				} else $post_avatar=new ocp_tempcode();

				// Rank images
				$rank_images=new ocp_tempcode();
				if (!$is_spacer_post)
				{
					$posters_groups=$GLOBALS['FORUM_DRIVER']->get_members_groups($_postdetails['poster'],true);
					foreach ($posters_groups as $group)
					{
						$rank_image=ocf_get_group_property($group,'rank_image');
						$group_leader=ocf_get_group_property($group,'group_leader');
						$group_name=ocf_get_group_name($group);
						$rank_image_pri_only=ocf_get_group_property($group,'rank_image_pri_only');
						if (($rank_image!='') && (($rank_image_pri_only==0) || ($group==$GLOBALS['FORUM_DRIVER']->get_member_row_field($_postdetails['poster'],'m_primary_group'))))
						{
							$rank_images->attach(do_template('OCF_RANK_IMAGE',array('_GUID'=>'0ff7855482b901be95591964d4212c44','GROUP_NAME'=>$group_name,'USERNAME'=>$GLOBALS['FORUM_DRIVER']->get_username($_postdetails['poster']),'IMG'=>$rank_image,'IS_LEADER'=>$group_leader==$_postdetails['poster'])));
						}
					}
				}

				// Poster details
				if (!$is_spacer_post)
				{
					if (!is_guest($_postdetails['poster']))
					{
						require_code('ocf_members2');
						$poster_details=render_member_box($_postdetails,false,$hooks,$hook_objects,false,NULL,false);
					} else
					{
						$custom_fields=new ocp_tempcode();
						if (array_key_exists('ip_address',$_postdetails))
						{
							$custom_fields->attach(do_template('OCF_MEMBER_BOX_CUSTOM_FIELD',array('_GUID'=>'d85be094dff0d039a64120d6f8f381bb','NAME'=>do_lang_tempcode('IP_ADDRESS'),'VALUE'=>($_postdetails['ip_address']))));
							$poster_details=do_template('OCF_GUEST_DETAILS',array('_GUID'=>'e43534acaf598008602e8da8f9725f38','CUSTOM_FIELDS'=>$custom_fields));
						} else
						{
							$poster_details=new ocp_tempcode();
						}
					}
				} else
				{
					$poster_details=new ocp_tempcode();
				}

				if (!is_guest($_postdetails['poster']))
				{
					$poster=do_template('OCF_POSTER_MEMBER',array(
						'_GUID'=>'dbbed1850b6c01a6c9601d85c6aee43f',
						'ONLINE'=>member_is_online($_postdetails['poster']),
						'ID'=>strval($_postdetails['poster']),
						'POSTER_DETAILS'=>$poster_details,
						'PROFILE_URL'=>$GLOBALS['FORUM_DRIVER']->member_profile_url($_postdetails['poster'],false,true),
						'POSTER_USERNAME'=>$_postdetails['poster_username'],
						'HIGHLIGHT_NAME'=>array_key_exists('poster_highlighted_name',$_postdetails)?strval($_postdetails['poster_highlighted_name']):NULL,
					));
				} else
				{
					$ip_link=((addon_installed('securitylogging')) && (array_key_exists('ip_address',$_postdetails)) && (has_actual_page_access(get_member(),'admin_lookup')))?build_url(array('page'=>'admin_lookup','param'=>$_postdetails['ip_address']),get_module_zone('admin_lookup')):new ocp_tempcode();
					$poster=do_template('OCF_POSTER_GUEST',array('_GUID'=>'36a8e550222cdac5165ef8f722be3def','LOOKUP_IP_URL'=>$ip_link,'POSTER_DETAILS'=>$poster_details,'POSTER_USERNAME'=>$_postdetails['poster_username']));
				}

				// Signature
				$signature=new ocp_tempcode();
				if ((array_key_exists('signature',$_postdetails)) && (!$_postdetails['signature']->is_empty()))
				{
					$signature=$_postdetails['signature'];
				}

				$post_title=$_postdetails['title'];

				$first_unread=(($_postdetails['id']==$first_unread_id) || (($first_unread_id<0) && ($array_id==count($topic_info['posts'])-1)))?do_template('OCF_TOPIC_FIRST_UNREAD'):new ocp_tempcode();

				$unvalidated=(($_postdetails['validated']==0) && (addon_installed('unvalidated')))?do_lang_tempcode('UNVALIDATED'):new ocp_tempcode();

				$post_url=$GLOBALS['FORUM_DRIVER']->post_url($_postdetails['id'],is_null($topic_info['forum_id'])?'':strval($topic_info['forum_id']),true);

				if (array_key_exists('intended_solely_for',$_postdetails))
				{
					decache('side_ocf_private_topics',array(get_member()));
					decache('_new_pp',array(get_member()));
				}

				$emphasis=ocf_get_post_emphasis($_postdetails);

				require_code('feedback');
				if (!array_key_exists('intended_solely_for',$_postdetails))
				{
					actualise_rating(true,'post',strval($_postdetails['id']),get_self_url(),$_postdetails['title']);
					$rating=display_rating(get_self_url(),$_postdetails['title'],'post',strval($_postdetails['id']),'RATING_INLINE_DYNAMIC',$_postdetails['poster']);
				} else
				{
					$rating=new ocp_tempcode();
				}

				$rendered_post=do_template('OCF_TOPIC_POST',array(
							'_GUID'=>'sacd09wekfofpw2f',
							'GIVE_CONTEXT'=>false,
							'ID'=>$is_spacer_post?'':strval($_postdetails['id']),
							'TOPIC_FIRST_POST_ID'=>is_null($topic_info['first_post_id'])?'':strval($topic_info['first_post_id']),
							'TOPIC_FIRST_POSTER'=>is_null($topic_info['first_poster'])?'':strval($topic_info['first_poster']),
							'POST_ID'=>$is_spacer_post?'':((get_value('seq_post_ids')==='1')?strval($start+$array_id+1):strval($_postdetails['id'])),
							'URL'=>$post_url,
							'CLASS'=>$_postdetails['is_emphasised']?'ocf_post_emphasis':(array_key_exists('intended_solely_for',$_postdetails)?'ocf_post_personal':''),
							'EMPHASIS'=>$emphasis,
							'FIRST_UNREAD'=>$first_unread,
							'POSTER_TITLE'=>$is_spacer_post?'':$_postdetails['poster_title'],
							'POST_TITLE'=>$post_title,
							'POST_DATE_RAW'=>strval($_postdetails['time']),
							'POST_DATE'=>$_postdetails['time_string'],
							'POST'=>$_postdetails['post'],
							'TOPIC_ID'=>is_null($id)?'':strval($id),
							'LAST_EDITED_RAW'=>$last_edited_raw,
							'LAST_EDITED'=>$last_edited,
							'POSTER_ID'=>strval($_postdetails['poster']),
							'POSTER'=>$is_spacer_post?'':$poster,
							'POSTER_DETAILS'=>$poster_details,
							'POST_AVATAR'=>$post_avatar,
							'RANK_IMAGES'=>$rank_images,
							'BUTTONS'=>$buttons,
							'SIGNATURE'=>$signature,
							'UNVALIDATED'=>$unvalidated,
							'DESCRIPTION'=>$description,
							'RATING'=>$rating,
							'POSTER_ONLINE'=>$poster_online,
				));
				$posts->attach($rendered_post);
			}

			$serialized_options=mixed();
			$hash=mixed();
		} else // Threaded
		{
			require_code('topics');
			$threaded_topic_ob=new OCP_Topic();

			// Load some settings into the renderer
			$threaded_topic_ob->first_post_id=$topic_info['first_post_id'];
			$threaded_topic_ob->topic_description=$topic_info['description'];
			$threaded_topic_ob->topic_description_link=$topic_info['description_link'];
			$threaded_topic_ob->topic_title=$topic_info['title'];
			$threaded_topic_ob->topic_info=$topic_info;

			// Other settings we need
			$max_thread_depth=intval(get_option('max_thread_depth'));
			$num_to_show_limit=get_param_integer('max_comments',intval(get_option('comments_to_show_in_thread')));

			// Load posts
			$threaded_topic_ob->load_from_topic($id,$num_to_show_limit,$start,false,NULL,true);
			$threaded_topic_ob->is_threaded=true;

			// Render posts
			list($posts,$serialized_options,$hash)=$threaded_topic_ob->render_posts($num_to_show_limit,$max_thread_depth,$may_reply,$topic_info['first_poster'],array(),$topic_info['forum_id'],NULL,false);

			$GLOBALS['META_DATA']['description']=$threaded_topic_ob->topic_description;

			// Get other gathered details
			$replied=$threaded_topic_ob->replied;
			if (!is_null($threaded_topic_ob->topic_title)) // Updated topic title
				$topic_info['title']=$threaded_topic_ob->topic_title;
			$topic_info['max_rows']=$threaded_topic_ob->total_posts;
		}

		// Buttons for topic as whole
		$button_array=array();
		if (!is_null($id))
		{
			if (get_value('no_threaded_buttons')!=='1')
			{
				if ($threaded)
				{
					$view_as_linear_url=get_self_url(false,false,array('threaded'=>0));
					$button_array[]=array('immediate'=>true,'title'=>do_lang_tempcode('VIEW_AS_LINEAR'),'url'=>$view_as_linear_url,'img'=>'linear');
				} else
				{
					$view_as_threaded_url=get_self_url(false,false,array('threaded'=>1));
					$button_array[]=array('immediate'=>true,'title'=>do_lang_tempcode('VIEW_AS_THREADED'),'url'=>$view_as_threaded_url,'img'=>'threaded');
				}
			}

			if (!is_guest())
			{
				$too_old=$topic_info['last_time']<time()-60*60*24*intval(get_option('post_history_days'));
				if ((get_value('disable_mark_topic_unread')!=='1') && (!$too_old))
				{
					$map=array('page'=>'topics','type'=>'mark_unread_topic','id'=>$id);
					$test=get_param_integer('kfs'.(is_null($topic_info['forum_id'])?'':strval($topic_info['forum_id'])),-1);
					if (($test!=-1) && ($test!=0)) $map['kfs'.(is_null($topic_info['forum_id'])?'':strval($topic_info['forum_id']))]=$test;
					$test=get_param_integer('threaded',-1);
					if ($test!=-1) $map['threaded']=$test;
					$mark_unread_url=build_url($map,get_module_zone('topics'));
					$button_array[]=array('immediate'=>true,'title'=>do_lang_tempcode('MARK_UNREAD'),'url'=>$mark_unread_url,'img'=>'mark_unread');
				}
			}

			if (($may_reply) && (is_null(get_bot_type())))
			{
				$reply_prevented=false;

				// "Staff-only" reply for support tickets
				if (($GLOBALS['FORUM_DRIVER']->is_staff(get_member())) && (addon_installed('tickets')))
				{
					require_code('tickets');
					if (is_ticket_forum($topic_info['forum_id']))
					{
						if (is_guest($second_poster))
							$reply_prevented=true;

						require_lang('tickets');
						$map=array('page'=>'topics','type'=>'new_post','id'=>$id,'intended_solely_for'=>$GLOBALS['FORUM_DRIVER']->get_guest_id());
						$test=get_param_integer('kfs'.(is_null($topic_info['forum_id'])?'':strval($topic_info['forum_id'])),-1);
						if (($test!=-1) && ($test!=0)) $map['kfs'.(is_null($topic_info['forum_id'])?'':strval($topic_info['forum_id']))]=$test;
						$test=get_param_integer('threaded',-1);
						if ($test!=-1) $map['threaded']=$test;
						$new_post_url=build_url($map,get_module_zone('topics'));
						$button_array[]=array('immediate'=>false,'rel'=>'add','title'=>do_lang_tempcode('TICKET_STAFF_ONLY_REPLY'),'url'=>$new_post_url,'img'=>'staff_only_reply');
					}
				}

				if (!$reply_prevented)
				{
					if ($topic_info['is_threaded']==0) // For threaded ones (i.e. not this) we want to encourage people to click the reply button by a post
					{
						$map=array('page'=>'topics','type'=>'new_post','id'=>$id);
						$test=get_param_integer('kfs'.(is_null($topic_info['forum_id'])?'':strval($topic_info['forum_id'])),-1);
						if (($test!=-1) && ($test!=0)) $map['kfs'.(is_null($topic_info['forum_id'])?'':strval($topic_info['forum_id']))]=$test;
						$test=get_param_integer('threaded',-1);
						if ($test!=-1) $map['threaded']=$test;
						$new_post_url=build_url($map,get_module_zone('topics'));
						$button_array[]=array('immediate'=>false,'rel'=>'add','title'=>do_lang_tempcode($topic_info['is_open']?'REPLY':'CLOSED'),'url'=>$new_post_url,'img'=>$topic_info['is_open']?'reply':'closed');
					}
				} else
				{
					unset($topic_info['may_use_quick_reply']);
				}
			}
			elseif (((is_null($topic_info['forum_id'])) || (has_privilege(get_member(),'submit_lowrange_content','topics',array('forums',$topic_info['forum_id'])))) && ($topic_info['last_poster']==get_member()) && (!is_guest()) && (ocf_may_edit_post_by(get_member(),$topic_info['forum_id'])))
			{
				$map=array('page'=>'topics','type'=>'edit_post','id'=>$topic_info['last_post_id']);
				$test=get_param_integer('kfs'.strval($topic_info['forum_id']),-1);
				if (($test!=-1) && ($test!=0)) $map['kfs'.strval($topic_info['forum_id'])]=$test;
				$test=get_param_integer('threaded',-1);
				if ($test!=-1) $map['threaded']=$test;
				$new_post_url=build_url($map,get_module_zone('topics'));
				$button_array[]=array('immediate'=>false,'rel'=>'edit','title'=>do_lang_tempcode('LAST_POST'),'url'=>$new_post_url,'img'=>'amend');
			}

			if (!is_null($topic_info['forum_id']))
			{
				if (get_value('disable_add_topic_btn_in_topic')!=='1')
				{
					if (ocf_may_post_topic($topic_info['forum_id'],get_member()))
					{
						$new_topic_url=build_url(array('page'=>'topics','type'=>'new_topic','id'=>$topic_info['forum_id']),get_module_zone('topics'));
						$button_array[]=array('immediate'=>false,'rel'=>'add','title'=>do_lang_tempcode('ADD_TOPIC'),'url'=>$new_topic_url,'img'=>'new_topic');
					}
				}
			} else
			{
				$invite_url=build_url(array('page'=>'topics','type'=>'invite_member','id'=>$id),get_module_zone('topics'));
				$button_array[]=array('immediate'=>false,'title'=>do_lang_tempcode('INVITE_MEMBER_TO_PT'),'url'=>$invite_url,'img'=>'invite_member');
			}
		}
		$buttons=ocf_screen_button_wrap($button_array);

		// Poll
		if (array_key_exists('poll',$topic_info))
		{
			$_poll=$topic_info['poll'];
			$voted_already=$_poll['voted_already'];
			$poll_results=(array_key_exists(0,$_poll['answers'])) && (array_key_exists('num_votes',$_poll['answers'][0]));
			$answers=new ocp_tempcode();
			$real_button=false;
			if ($_poll['is_open'])
			{
				if ($poll_results)
				{
					$button=new ocp_tempcode();
				}
				elseif (($_poll['requires_reply']) && (!$replied))
				{
					$button=do_lang_tempcode('POLL_REQUIRES_REPLY');
				} else
				{
					if ((!has_privilege(get_member(),'vote_in_polls')) || (is_guest()))
					{
						$button=do_lang_tempcode('VOTE_DENIED');
					} else
					{
						if (!is_null($voted_already))
						{
							$button=do_lang_tempcode('NOVOTE');
						} else
						{
							require_lang('polls');
							$map=array('page'=>'topicview','id'=>$id,'view_poll_results'=>1,'topic_start'=>($start==0)?NULL:$start,'topic_max'=>($max==$default_max)?NULL:$max);
							$test=get_param_integer('kfs'.(is_null($topic_info['forum_id'])?'':strval($topic_info['forum_id'])),-1);
							if (($test!=-1) && ($test!=0)) $map['kfs'.(is_null($topic_info['forum_id'])?'':strval($topic_info['forum_id']))]=$test;
							$test=get_param_integer('threaded',-1);
							if ($test!=-1) $map['threaded']=$test;
							$results_url=build_url($map,get_module_zone('topics'));
							$button=do_template('OCF_TOPIC_POLL_BUTTON',array('_GUID'=>'94b932fd01028df8f67bb5864d9235f9','RESULTS_URL'=>$results_url));
							$real_button=true;
						}
					}
				}
			} else $button=do_lang_tempcode('TOPIC_POLL_CLOSED');
			foreach ($_poll['answers'] as $answer)
			{
				if (($poll_results) && (($_poll['requires_reply']==0) || ($replied)))
				{
					$num_votes=$answer['num_votes'];
					$total_votes=$_poll['total_votes'];
					if ($total_votes!=0)
						$width=intval(round(70.0*floatval($num_votes)/floatval($total_votes)));
					else $width=0;
					$answer_tpl=do_template('OCF_TOPIC_POLL_ANSWER_RESULTS',array('_GUID'=>'b32f4c526e147abf20ca0d668e40d515','ID'=>strval($_poll['id']),'NUM_VOTES'=>integer_format($num_votes),'WIDTH'=>strval($width),'ANSWER'=>$answer['answer'],'I'=>strval($answer['id'])));
				} else
				{
					$answer_tpl=do_template('OCF_TOPIC_POLL_ANSWER'.($_poll['maximum_selections']==1?'_RADIO':''),array('REAL_BUTTON'=>$real_button,'ID'=>strval($_poll['id']),'ANSWER'=>$answer['answer'],'I'=>strval($answer['id'])));
				}
				$answers->attach($answer_tpl);
			}
			$map=array('page'=>'topics','type'=>'vote_poll','id'=>$id,'topic_start'=>($start==0)?NULL:$start,'topic_max'=>($max==$default_max)?NULL:$max);
			$test=get_param_integer('kfs'.(is_null($topic_info['forum_id'])?'':strval($topic_info['forum_id'])),-1);
			if (($test!=-1) && ($test!=0)) $map['kfs'.(is_null($topic_info['forum_id'])?'':strval($topic_info['forum_id']))]=$test;
			$test=get_param_integer('threaded',-1);
			if ($test!=-1) $map['threaded']=$test;
			$vote_url=build_url($map,get_module_zone('topics'));
			if ($_poll['is_private']) $private=paragraph(do_lang_tempcode('TOPIC_POLL_IS_PRIVATE'),'dfgsdgdsgs'); else $private=new ocp_tempcode();
			if ($_poll['maximum_selections']>1) $num_choices=paragraph(($_poll['minimum_selections']==$_poll['maximum_selections'])?do_lang_tempcode('POLL_NOT_ENOUGH_ERROR_2',integer_format($_poll['minimum_selections'])):do_lang_tempcode('POLL_NOT_ENOUGH_ERROR',integer_format($_poll['minimum_selections']),integer_format($_poll['maximum_selections'])),'dsfsdfsdfs'); else $num_choices=new ocp_tempcode();

			$poll=do_template('OCF_TOPIC_POLL'.($poll_results?'_VIEW_RESULTS':''),array(
				'ID'=>strval($_poll['id']),
				'NUM_CHOICES'=>$num_choices,
				'PRIVATE'=>$private,
				'QUESTION'=>$_poll['question'],
				'ANSWERS'=>$answers,
				'REAL_BUTTON'=>$real_button,
				'BUTTON'=>$button,
				'VOTE_URL'=>$vote_url,
				'MINIMUM_SELECTIONS'=>integer_format($_poll['minimum_selections']),
				'MAXIMUM_SELECTIONS'=>integer_format($_poll['maximum_selections']),
			));
		} else $poll=new ocp_tempcode();

		// Forum breadcrumbs
		if (!is_null($topic_info['forum_id']))
		{
			$breadcrumbs=ocf_forum_breadcrumbs($topic_info['forum_id'],NULL,NULL,false);
		} else
		{
			$breadcrumbs=new ocp_tempcode();
			$breadcrumbs->attach(hyperlink(build_url(array('page'=>'members'),get_module_zone('members')),do_lang_tempcode('MEMBERS'),false,false,do_lang_tempcode('GO_BACKWARDS_TO',do_lang_tempcode('MEMBERS')),NULL,NULL,'up'));
			$breadcrumbs->attach(do_template('BREADCRUMB_SEPARATOR'));
			if (has_privilege(get_member(),'view_other_pt'))
			{
				$of_member=($topic_info['pt_from']==get_member())?$topic_info['pt_from']:$topic_info['pt_to'];
			} else $of_member=get_member();
			$of_username=$GLOBALS['FORUM_DRIVER']->get_username($of_member);
			if (is_null($of_username)) $of_username=do_lang('UNKNOWN');
			$private_topic_url=build_url(array('page'=>'members','type'=>'view','id'=>$of_member),get_module_zone('members'),NULL,true,false,false,'tab__pts');
			$breadcrumbs->attach(hyperlink($private_topic_url,do_lang_tempcode('MEMBER_PROFILE',escape_html($of_username)),false,false,do_lang_tempcode('GO_BACKWARDS_TO',do_lang_tempcode('MEMBERS')),NULL,NULL,'up'));
		}

		// Quick reply
		if ((array_key_exists('may_use_quick_reply',$topic_info)) && ($may_reply) && (!is_null($id)))
		{
			$map=array('page'=>'topics','type'=>'_add_reply','topic_id'=>$id);
			$test=get_param_integer('kfs'.(is_null($topic_info['forum_id'])?'':strval($topic_info['forum_id'])),-1);
			if (($test!=-1) && ($test!=0)) $map['kfs'.(is_null($topic_info['forum_id'])?'':strval($topic_info['forum_id']))]=$test;
			$test=get_param_integer('threaded',-1);
			if ($test!=-1) $map['threaded']=$test;
			$_post_url=build_url($map,get_module_zone('topics'));
			$post_url=$_post_url->evaluate();
			$map=array('page'=>'topics','type'=>'new_post','id'=>$id);
			if (($test!=-1) && ($test!=0)) $map['kfs'.(is_null($topic_info['forum_id'])?'':strval($topic_info['forum_id']))]=$test;
			$more_url=build_url($map,get_module_zone('topics'));
			$_postdetails=array_key_exists('first_post',$topic_info)?get_translated_tempcode($topic_info['first_post'],$GLOBALS['FORUM_DB']):new ocp_tempcode();
			$first_post=$_postdetails;
			$first_post_url=$GLOBALS['FORUM_DRIVER']->post_url($topic_info['first_post_id'],is_null($topic_info['forum_id'])?'':strval($topic_info['forum_id']),true);
			$display='block';
			$expand_type='contract';
			if ($topic_info['max_rows']>$start+$max)
			{
				$display='none';
				$expand_type='expand';
			}
			$em=$GLOBALS['FORUM_DRIVER']->get_emoticon_chooser();
			require_javascript('javascript_editing');
			require_javascript('javascript_validation');
			if (addon_installed('captcha'))
			{
				require_code('captcha');
				$use_captcha=use_captcha();
				if ($use_captcha)
				{
					generate_captcha();
				}
			} else $use_captcha=false;

			$post_warning='';
			if ($topic_info['is_really_threaded']==1)
				$post_warning=do_lang('THREADED_REPLY_NOTICE',$post_warning);

			$quick_reply=do_template('COMMENTS_POSTING_FORM',array(
				'_GUID'=>'4c532620f3eb68d9cc820b18265792d7',
				'JOIN_BITS'=>'',
				'USE_CAPTCHA'=>$use_captcha,
				'GET_EMAIL'=>false,
				'EMAIL_OPTIONAL'=>true,
				'GET_TITLE'=>false,
				'POST_WARNING'=>$post_warning,
				'COMMENT_TEXT'=>'',
				'EM'=>$em,
				'EXPAND_TYPE'=>$expand_type,
				'DISPLAY'=>$display,
				'FIRST_POST_URL'=>$first_post_url,
				'FIRST_POST'=>$first_post,
				'MORE_URL'=>$more_url,
				'COMMENT_URL'=>$post_url,
				'TITLE'=>do_lang_tempcode('QUICK_REPLY'),
				'SUBMIT_NAME'=>do_lang_tempcode('MAKE_POST'),
			));
		} else $quick_reply=new ocp_tempcode();

		$action_url=build_url(array('page'=>'topics','id'=>$id),get_module_zone('topics'));
		if (!is_null($id))
		{
			// Moderation options
			$moderator_actions='';
			if (is_null($topic_info['forum_id']))
			{
				$moderator_actions.='<option value="categorise_pts">'.do_lang('_CATEGORISE_PTS').'</option>';
			}
			if ((array_key_exists('may_multi_moderate',$topic_info)) && (array_key_exists('forum_id',$topic_info)))
			{
				$multi_moderations=ocf_list_multi_moderations($topic_info['forum_id']);
				if (count($multi_moderations)!=0)
				{
					$moderator_actions.='<optgroup label="'.do_lang('MULTI_MODERATIONS').'">';
					foreach ($multi_moderations as $mm_id=>$mm_name)
						$moderator_actions.='<option value="mm_'.strval($mm_id).'">'.$mm_name.'</option>';
					$moderator_actions.='</optgroup>';
				}
			}
			if (array_key_exists('may_move_topic',$topic_info))
				$moderator_actions.='<option value="move_topic">'.do_lang('MOVE_TOPIC').'</option>';
			if (array_key_exists('may_edit_topic',$topic_info))
				$moderator_actions.='<option value="edit_topic">'.do_lang('EDIT_TOPIC').'</option>';
			if (array_key_exists('may_delete_topic',$topic_info))
				$moderator_actions.='<option value="delete_topic">'.do_lang('DELETE_TOPIC').'</option>';
			if (array_key_exists('may_pin_topic',$topic_info))
				$moderator_actions.='<option value="pin_topic">'.do_lang('PIN_TOPIC').'</option>';
			if (array_key_exists('may_unpin_topic',$topic_info))
				$moderator_actions.='<option value="unpin_topic">'.do_lang('UNPIN_TOPIC').'</option>';
			if (array_key_exists('may_sink_topic',$topic_info))
				$moderator_actions.='<option value="sink_topic">'.do_lang('SINK_TOPIC').'</option>';
			if (array_key_exists('may_unsink_topic',$topic_info))
				$moderator_actions.='<option value="unsink_topic">'.do_lang('UNSINK_TOPIC').'</option>';
			if (array_key_exists('may_cascade_topic',$topic_info))
				$moderator_actions.='<option value="cascade_topic">'.do_lang('CASCADE_TOPIC').'</option>';
			if (array_key_exists('may_uncascade_topic',$topic_info))
				$moderator_actions.='<option value="uncascade_topic">'.do_lang('UNCASCADE_TOPIC').'</option>';
			if (array_key_exists('may_open_topic',$topic_info))
				$moderator_actions.='<option value="open_topic">'.do_lang('OPEN_TOPIC').'</option>';
			if (array_key_exists('may_close_topic',$topic_info))
				$moderator_actions.='<option value="close_topic">'.do_lang('CLOSE_TOPIC').'</option>';
			if (array_key_exists('may_edit_poll',$topic_info))
				$moderator_actions.='<option value="edit_poll">'.do_lang('EDIT_TOPIC_POLL').'</option>';
			if (array_key_exists('may_delete_poll',$topic_info))
				$moderator_actions.='<option value="delete_poll">'.do_lang('DELETE_TOPIC_POLL').'</option>';
			if (array_key_exists('may_attach_poll',$topic_info))
				$moderator_actions.='<option value="add_poll">'.do_lang('ADD_TOPIC_POLL').'</option>';
			if ((has_privilege(get_member(),'view_content_history')) && ($GLOBALS['FORUM_DB']->query_select_value('f_post_history','COUNT(*)',array('h_topic_id'=>$id))!=0))
				$moderator_actions.='<option value="topic_history">'.do_lang('POST_HISTORY').'</option>';
			if ((array_key_exists('may_make_personal',$topic_info)) && (!is_null($topic_info['forum_id'])))
				$moderator_actions.='<option value="make_personal">'.do_lang('MAKE_PERSONAL').'</option>';

			if ($GLOBALS['XSS_DETECT']) ocp_mark_as_escaped($moderator_actions);

			// Marked post actions
			$map=array('page'=>'topics','id'=>$id);
			$test=get_param_integer('kfs'.(is_null($topic_info['forum_id'])?'':strval($topic_info['forum_id'])),-1);
			if (($test!=-1) && ($test!=0)) $map['kfs'.(is_null($topic_info['forum_id'])?'':strval($topic_info['forum_id']))]=$test;
			$test=get_param_integer('threaded',-1);
			if ($test!=-1) $map['threaded']=$test;
			$action_url=build_url($map,get_module_zone('topics'),NULL,false,true);
			$marked_post_actions='';
			if (array_key_exists('may_move_posts',$topic_info))
			{
				$marked_post_actions.='<option value="move_posts_a">'.do_lang('MERGE_POSTS').'</option>';
				$marked_post_actions.='<option value="move_posts_b">'.do_lang('SPLIT_POSTS').'</option>';
			}
			if (array_key_exists('may_delete_posts',$topic_info))
				$marked_post_actions.='<option value="delete_posts">'.do_lang('DELETE_POSTS').'</option>';
			if ((array_key_exists('may_validate_posts',$topic_info)) && (addon_installed('unvalidated')))
				$marked_post_actions.='<option value="validate_posts">'.do_lang('VALIDATE_POSTS').'</option>';
			if (get_value('disable_multi_quote')!=='1')
			{
				if ($may_reply)
					$marked_post_actions.='<option value="new_post">'.do_lang('QUOTE_POSTS').'</option>';
			}

			if ($GLOBALS['XSS_DETECT']) ocp_mark_as_escaped($marked_post_actions);
		} else
		{
			$moderator_actions='';
			$marked_post_actions='';
		}

		$max_rows=$topic_info['max_rows'];
		if (($max_rows>$max) && (!$threaded))
		{
			require_code('templates_pagination');
			$pagination=pagination(do_lang_tempcode('FORUM_POSTS'),$start,'topic_start',$max,'topic_max',$max_rows,false);
		} else
		{
			$pagination=new ocp_tempcode();
		}

		// Members viewing this topic
		$members=is_null($id)?array():get_members_viewing('topicview','',strval($id),true);
		$num_guests=0;
		$num_members=0;
		if (is_null($members))
		{
			$members_viewing=new ocp_tempcode();
		} else
		{
			$members_viewing=new ocp_tempcode();
			foreach ($members as $member_id=>$at_details)
			{
				$username=$at_details['mt_cache_username'];

				if (is_guest($member_id))
				{
					$num_guests++;
				} else
				{
					$num_members++;
					$profile_url=$GLOBALS['FORUM_DRIVER']->member_profile_url($member_id,false,true);
					$map=array('FIRST'=>$members_viewing->is_empty(),'PROFILE_URL'=>$profile_url,'USERNAME'=>$username);
					if ((has_privilege(get_member(),'show_user_browsing')) || ((in_array($at_details['the_page'],array('topics','topicview'))) && ($at_details['the_id']==strval($id))))
					{
						$map['AT']=escape_html($at_details['the_title']);
					}
					$map['COLOUR']=get_group_colour(ocf_get_member_primary_group($member_id));
					$members_viewing->attach(do_template('OCF_USER_MEMBER',$map));
				}
			}
			if ($members_viewing->is_empty()) $members_viewing=do_lang_tempcode('NONE_EM');
		}

		if (!is_null($id))
			breadcrumb_add_segment($breadcrumbs,protect_from_escaping('<span>'.do_lang(is_null($topic_info['forum_id'])?'VIEW_PRIVATE_TOPIC':'VIEW_TOPIC').'</span>'));

		if (is_null($id)) // Just inline personal posts
		{
			$root_forum_name=$GLOBALS['FORUM_DB']->query_select_value('f_forums','f_name',array('id'=>db_get_first_id()));
			$breadcrumbs=hyperlink(build_url(array('page'=>'forumview','id'=>db_get_first_id()),get_module_zone('forumview')),escape_html($root_forum_name),false,false,do_lang('GO_BACKWARDS_TO'));
			breadcrumb_add_segment($breadcrumbs,protect_from_escaping('<span>'.do_lang('INLINE_PERSONAL_POSTS').'</span>'));
		}

		if (($topic_info['validated']==0) && (addon_installed('unvalidated')))
		{
			$warning_details=do_template('WARNING_BOX',array('_GUID'=>'313de370c1aeab9545c4bee4e35e7f84','WARNING'=>do_lang_tempcode((get_param_integer('redirected',0)==1)?'UNVALIDATED_TEXT_NON_DIRECT':'UNVALIDATED_TEXT')));
		} else $warning_details=new ocp_tempcode();

		if (is_null($id)) // Just inline personal posts
		{
			$title=get_screen_title('INLINE_PERSONAL_POSTS');
		} else
		{
			if (is_null($topic_info['forum_id']))
			{
				$title=get_screen_title(do_lang_tempcode('NAMED_PRIVATE_TOPIC',escape_html($topic_info['title'])),false,NULL,do_lang_tempcode('READING_PRIVATE_TOPIC'));
			} else
			{
				if ((get_value('no_awards_in_titles')!=='1') && (addon_installed('awards')))
				{
					require_code('awards');
					$awards=find_awards_for('topic',strval($id));
				} else $awards=array();

				$title=get_screen_title(do_lang_tempcode('NAMED_TOPIC',make_fractionable_editable('topic',$id,$topic_info['title'])),false,NULL,NULL,$awards);
			}
		}

		require_code('ocf_general');
		ocf_set_context_forum($topic_info['forum_id']);

		$topic_tpl=do_template('OCF_TOPIC_SCREEN',array(
			'_GUID'=>'bb201d5d59559e5e2bd60e7cf2e6f7e9',
			'TITLE'=>$title,
			'SERIALIZED_OPTIONS'=>$serialized_options,
			'HASH'=>$hash,
			'ID'=>strval($id),
			'_TITLE'=>$topic_info['title'],
			'MAY_DOUBLE_POST'=>has_privilege(get_member(),'double_post'),
			'LAST_POSTER'=>array_key_exists('last_poster',$topic_info)?(is_null($topic_info['last_poster'])?'':strval($topic_info['last_poster'])):'',
			'WARNING_DETAILS'=>$warning_details,
			'MAX'=>strval($max),
			'MAY_CHANGE_MAX'=>array_key_exists('may_change_max',$topic_info),
			'ACTION_URL'=>$action_url,
			'NUM_GUESTS'=>integer_format($num_guests),
			'NUM_MEMBERS'=>integer_format($num_members),
			'MEMBERS_VIEWING'=>$members_viewing,
			'PAGINATION'=>$pagination,
			'MODERATOR_ACTIONS'=>$moderator_actions,
			'MARKED_POST_ACTIONS'=>$marked_post_actions,
			'QUICK_REPLY'=>$quick_reply,
			'BREADCRUMBS'=>$breadcrumbs,
			'POLL'=>$poll,
			'SCREEN_BUTTONS'=>$buttons,
			'POSTS'=>$posts,
			'THREADED'=>$threaded,
		));

		require_code('templates_internalise_screen');
		return internalise_own_screen($topic_tpl);
	}

	/**
	 * Update the read status for a topic.
	 */
	function _update_read_status()
	{
		if (!is_guest())
		{
			if (!$GLOBALS['SITE_DB']->table_is_locked('f_read_logs'))
			{
				$GLOBALS['FORUM_DB']->query_delete('f_read_logs',array('l_member_id'=>get_member(),'l_topic_id'=>$this->id),'',1);
				$GLOBALS['FORUM_DB']->query_insert('f_read_logs',array('l_member_id'=>get_member(),'l_topic_id'=>$this->id,'l_time'=>time()),false,true); // race condition
			}
		}
		if (!$GLOBALS['SITE_DB']->table_is_locked('f_topics'))
			$GLOBALS['FORUM_DB']->query('UPDATE '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_topics SET t_num_views=(t_num_views+1) WHERE id='.strval((integer)$this->id),1,NULL,true);
	}

}
