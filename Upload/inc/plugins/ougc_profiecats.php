<?php

/***************************************************************************
 *
 *	OUGC Profile Fields Categories plugin (/inc/plugins/ougc_profiecats.php)
 *	Author: Omar Gonzalez
 *	Copyright: © 2014 Omar Gonzalez
 *
 *	Website: http://omarg.me
 *
 *	Allow administrators to create custom profile fields categories.
 *
 ***************************************************************************
 
****************************************************************************
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
****************************************************************************/

// Die if IN_MYBB is not defined, for security reasons.
defined('IN_MYBB') or die('Direct initialization of this file is not allowed.');

// Run/Add Hooks
if(defined('IN_ADMINCP'))
{
	$plugins->add_hook('admin_config_profile_fields_begin', 'ougc_profiecats_admin');
}

// Plugin API
function ougc_profiecats_info()
{
	global $lang, $profiecats;
	$profiecats->lang_load();

	return array(
		'name'			=> 'OUGC Profile Fields Categories',
		'description'	=> $lang->setting_group_ougc_profiecats_desc,
		'website'		=> 'http://omarg.me',
		'author'		=> 'Omar G.',
		'authorsite'	=> 'http://omarg.me',
		'version'		=> '0.1',
		'versioncode'	=> '0100',
		'compatibility'	=> '18*'
	);
}

// _activate() routine
function ougc_profiecats_activate()
{
	global $cache;

	// Insert/update version into cache
	$plugins = $cache->read('ougc_plugins');
	if(!$plugins)
	{
		$plugins = array();
	}

	$info = ougc_profiecats_info();

	if(!isset($plugins['pages']))
	{
		$plugins['pages'] = $info['versioncode'];
	}

	/*~*~* RUN UPDATES START *~*~*/

	/*~*~* RUN UPDATES END *~*~*/

	$plugins['pages'] = $info['versioncode'];
	$cache->update('ougc_plugins', $plugins);
}

// _install() routine
function ougc_profiecats_install()
{
	global $db;

	$db->write_query("CREATE TABLE `".TABLE_PREFIX."ougc_profiecats_categories` (
			`cid` int UNSIGNED NOT NULL AUTO_INCREMENT,
			`name` varchar(100) NOT NULL DEFAULT '',
			`forums` varchar(100) NOT NULL DEFAULT '',
			`disporder` smallint NOT NULL DEFAULT '0',
			`active` tinyint(1) NOT NULL DEFAULT '1',
			`required` tinyint(1) NOT NULL DEFAULT '1',
			PRIMARY KEY (`cid`)
		) ENGINE=MyISAM{$db->build_create_table_collation()};"
	);

	$db->add_column('profilefields', 'cid', 'int NOT NULL DEFAULT \'0\'');
}

// _is_installed() routine
function ougc_profiecats_is_installed()
{
	global $db;

	return $db->table_exists('ougc_profiecats_categories');
}

// _uninstall() routine
function ougc_profiecats_uninstall()
{
	global $db, $cache;

	// Drop DB entries
	$db->drop_table('ougc_profiecats_categories');
	$db->drop_column('profilefields', 'cid');

	$cache->delete('ougc_profiecats_categories');

	// Delete version from cache
	$plugins = (array)$cache->read('ougc_plugins');

	if(isset($plugins['profiecats']))
	{
		unset($plugins['profiecats']);
	}

	if(!empty($plugins))
	{
		$cache->update('ougc_plugins', $plugins);
	}
	else
	{
		$cache->delete('ougc_plugins');
	}
}

// Hijack Tabs
function ougc_profiecats_admin()
{
	global $sub_tabs, $lang, $profiecats;
	$profiecats->lang_load();

	$sub_tabs['ougc_profiecats_admin_tab'] = array(
		'title'			=> $lang->ougc_profiecats_admin_tab,
		'link'			=> 'index.php?module=config-profile_fields&amp;action=categories',
		'description'	=> $lang->ougc_profiecats_admin_tab_desc
	);

	global $mybb, $plugins, $page, $db, $db, $db, $db, $db, $db;

	$plugins->add_hook('admin_formcontainer_end', 'ougc_profiecats_admin_field');
	$plugins->add_hook('admin_config_profile_fields_start', 'ougc_profiecats_admin_hijack');
	$plugins->add_hook('admin_config_profile_fields_edit_commit', 'ougc_profiecats_admin_field_commit');
	$plugins->add_hook('admin_config_profile_fields_add_commit', 'ougc_profiecats_admin_field_commit');

	if($mybb->get_input('action') != 'categories')
	{
		return;
	}

	$sub_tabs['ougc_profiecats_admin_tab_add'] = array(
		'title'			=> $lang->ougc_profiecats_admin_tab_add,
		'link'			=> 'index.php?module=config-profile_fields&amp;action=categories&amp;do=add',
		'description'	=> $lang->ougc_profiecats_admin_tab_add_desc
	);

	if($mybb->get_input('do') == 'edit')
	{
		$sub_tabs['ougc_profiecats_admin_tab_edit'] = array(
			'title'			=> $lang->ougc_profiecats_admin_tab_edit,
			'link'			=> 'index.php?module=config-profile_fields&amp;action=categories&amp;do=edit&amp;cid='.$mybb->get_input('cid', 1),
			'description'	=> $lang->ougc_profiecats_admin_tab_edit_desc
		);
	}

	$sub_tabs['custom_profile_fields'] = array(
		'title'			=> $lang->custom_profile_fields,
		'link'			=> 'index.php?module=config-profile_fields',
		'description'	=> $lang->custom_profile_fields_desc
	);

	$plugins->run_hooks('admin_config_profile_fields_categories_start');

	if($mybb->get_input('do') == 'add' || $mybb->get_input('do') == 'edit')
	{
		$add = $mybb->get_input('do') == 'add';

		$url = ($add ? $sub_tabs['ougc_profiecats_admin_tab_add']['link'] : $sub_tabs['ougc_profiecats_admin_tab_edit']['link']);

		$page->add_breadcrumb_item($lang->ougc_awards_acp_nav, $url);

		if(!$add)
		{
			if(!($category = $profiecats->get_category($mybb->get_input('cid', 1))))
			{
				flash_message($lang->ougc_profiecats_admin_error_invalid_category, 'error');
				admin_redirect($sub_tabs['ougc_profiecats_admin_tab']['link']);
			}

			$page->add_breadcrumb_item(strip_tags($category['name']));
		}

		$mergeinput = array();
		foreach(array('name', 'forums', 'active', 'required', 'disporder') as $key)
		{
			$mergeinput[$key] = isset($mybb->input[$key]) ? $mybb->input[$key] : ($add ? '' : $category[$key]);
			if($key == 'forums')
			{
				$profiecats->clean_ints($mergeinput[$key]);
			}
		}
		$mybb->input = array_merge($mybb->input, $mergeinput);

		$page->output_header($sub_tabs['ougc_profiecats_admin_tab_add']['title']);
		$page->output_nav_tabs($sub_tabs, $add ? 'ougc_profiecats_admin_tab_add' : 'ougc_profiecats_admin_tab_edit');

		if($mybb->request_method == 'post')
		{
			$errors = array();
			if(!$mybb->get_input('name') || isset($mybb->input{100}))
			{
				$errors[] = $lang->ougc_profiecats_admin_error_invalid_name;
			}

			if(empty($errors))
			{
				$method = $add ? 'insert_category' : 'update_category';
				$lang_val = $add ? 'ougc_profiecats_admin_success_add' : 'ougc_profiecats_admin_success_edit';

				$profiecats->{$method}(array(
					'name'			=> $mybb->get_input('name'),
					'forums'		=> $mybb->get_input('forums', 2),
					'active'		=> $mybb->get_input('active', 1),
					'required'		=> $mybb->get_input('required', 1),
					'disporder'		=> $mybb->get_input('disporder', 1)
				), $mybb->get_input('cid', 1));

				$profiecats->update_cache();
				$profiecats->log_action();

				flash_message($lang->{$lang_val}, 'success');
				admin_redirect($sub_tabs['ougc_profiecats_admin_tab']['link']);
			}
			else
			{
				$page->output_inline_error($errors);
			}
		}

		$form = new Form($url, 'post');
		$form_container = new FormContainer($sub_tabs['ougc_profiecats_admin_tab_'.($add ? 'add' : 'edit')]['title']);

		$form_container->output_row($lang->ougc_profiecats_admin_name.' <em>*</em>', $lang->ougc_profiecats_admin_name_desc, $form->generate_text_box('name', $mybb->get_input('name')));
		$form_container->output_row($lang->ougc_profiecats_admin_forums, $lang->ougc_profiecats_admin_forums_desc, $form->generate_forum_select('forums[]', $mybb->get_input('forums', 2), array('multiple' => true)));
		$form_container->output_row($lang->ougc_profiecats_admin_active, $lang->ougc_profiecats_admin_active_desc, $form->generate_yes_no_radio('active', $mybb->get_input('active', 1)));
		$form_container->output_row($lang->ougc_profiecats_admin_required, $lang->ougc_profiecats_admin_required_desc, $form->generate_yes_no_radio('required', $mybb->get_input('required', 1)));
		$form_container->output_row($lang->ougc_profiecats_admin_disporder, $lang->ougc_profiecats_admin_disporder_desc, $form->generate_text_box('disporder', $mybb->get_input('disporder', 1), array('style' => 'text-align: center; width: 30px;" maxlength="5')));

		$form_container->end();
		$form->output_submit_wrapper(array($form->generate_submit_button($lang->ougc_profiecats_admin_summit), $form->generate_reset_button($lang->reset)));
		$form->end();
		$page->output_footer();
	}
	elseif($mybb->get_input('do') == 'delete')
	{
		return;
	}
	else
	{
		$page->add_breadcrumb_item($sub_tabs['ougc_profiecats_admin_tab']['title'], $sub_tabs['ougc_profiecats_admin_tab']['link']);
		$page->output_header($sub_tabs['ougc_profiecats_admin_tab']['title']);
		$page->output_nav_tabs($sub_tabs, 'ougc_profiecats_admin_tab');

		$table = new Table;
		$table->construct_header($lang->ougc_profiecats_admin_name, array('width' => '55%'));
		$table->construct_header($lang->ougc_profiecats_admin_active, array('width' => '10%', 'class' => 'align_center'));
		$table->construct_header($lang->ougc_profiecats_admin_required, array('width' => '10%', 'class' => 'align_center'));
		$table->construct_header($lang->ougc_profiecats_admin_disporder, array('width' => '15%', 'class' => 'align_center'));
		$table->construct_header($lang->options, array('width' => '10%', 'class' => 'align_center'));

		$limit = (int)$mybb->get_input('limit', 1);
		$limit = $limit > 100 ? 100 : ($limit < 1 ? 1 : $limit);

		if($mybb->get_input('page', 1) > 0)
		{
			$start = ($mybb->get_input('page', 1)-1)*$limit;
		}
		else
		{
			$start = 0;
			$mybb->input['page'] = 1;
		}

		$query = $db->simple_select('ougc_profiecats_categories', '*', '', array('limit_start' => $start, 'limit' => $limit, 'order_by' => 'disporder'));
		
		if(!$db->num_rows($query))
		{
			$table->construct_cell('<div align="center">'.$lang->ougc_profiecats_admin_empty.'</div>', array('colspan' => 6));
			$table->construct_row();
			$table->output($sub_tabs['ougc_profiecats_admin_tab']['title']);
		}
		else
		{
			if($mybb->request_method == 'post' && $mybb->get_input('do') == 'updatedisporder')
			{
				foreach($mybb->get_input('disporder', 2) as $cid => $disporder)
				{
					$profiecats->update_category(array('disporder' => $disporder), $cid);
				}
				$profiecats->update_cache();
				admin_redirect($sub_tabs['ougc_profiecats_admin_tab']['link']);
			}

			$form = new Form($sub_tabs['ougc_profiecats_admin_tab']['link'].'&amp;do=updatedisporder', 'post');

			$query2 = $db->simple_select('ougc_profiecats_categories', 'COUNT(cid) AS categories');
			$catscount = (int)$db->fetch_field($query2, 'categories');

			echo draw_admin_pagination($mybb->get_input('page', 1), $limit, $catscount, $sub_tabs['ougc_profiecats_admin_tab']['link']);

			while($category = $db->fetch_array($query))
			{
				$edit_link = 'index.php?module=config-profile_fields&amp;action=categories&amp;do=edit&amp;cid='.$category['cid'];

				$category['active'] or $category['name'] = '<i>'.$category['name'].'</i>';

				$table->construct_cell('<a href="'.$edit_link.'">'.$category['name'].'</a>');
				$table->construct_cell('<img src="styles/default/images/icons/bullet_o'.(!$category['active'] ? 'ff' : 'n').'.png" alt="" title="'.(!$category['active'] ? $lang->ougc_awards_form_hidden : $lang->ougc_awards_form_visible).'" />', array('class' => 'align_center'));
				$table->construct_cell('<img src="styles/default/images/icons/bullet_o'.(!$category['required'] ? 'ff' : 'n').'.png" alt="" title="'.(!$category['required'] ? $lang->ougc_awards_form_hidden : $lang->ougc_awards_form_visible).'" />', array('class' => 'align_center'));

				$table->construct_cell($form->generate_text_box('disporder['.$category['cid'].']', (int)$category['disporder'], array('style' => 'text-align: center; width: 30px;')), array('class' => 'align_center'));

				$popup = new PopupMenu("category_{$category['cid']}", $lang->options);
				$popup->add_item($lang->edit, $edit_link);
				$popup->add_item($lang->view, 'index.php?module=config-profile_fields&amp;view_cat='.$category['cid']);
				$popup->add_item($lang->delete, 'index.php?module=config-profile_fields&amp;action=categories&amp;do=delete&amp;cid='.$category['cid']);
				$table->construct_cell($popup->fetch(), array('class' => 'align_center'));

				$table->construct_row();
			}
			$table->output($sub_tabs['ougc_profiecats_admin_tab']['title']);

			$form->output_submit_wrapper(array($form->generate_submit_button($lang->ougc_profiecats_admin_summit), $form->generate_reset_button($lang->reset)));
			$form->end();
		}
		$page->output_footer();
	}
}

// Add/Edit fields
function ougc_profiecats_admin_field()
{
	global $run_module, $form_container, $lang;

	if($run_module == 'config' && ($form_container->_title == $lang->edit_profile_field || $form_container->_title == $lang->add_profile_field))
	{
		global $form, $mybb, $profiecats, $profile_field;
		$profiecats->lang_load();

		if(isset($profile_field['cid']) && $mybb->request_method != 'post')
		{
			$mybb->input['category'] = $profile_field['cid'];
		}

		$checked = (!empty($mybb->input['vgroup_1_vgroups']) ? array('all' => '', 'select' => ' checked="checked"') : array('all' => 'checked="checked"', 'select' => ''));

		$form_container->output_row($lang->ougc_profiecats_admin_category, $lang->ougc_profiecats_admin_category_desc, $profiecats->generate_category_select('category', $mybb->get_input('category', 1)));
	}
}

// Commit changes
function ougc_profiecats_admin_field_commit()
{
	global $mybb, $plugins, $updated_profile_field, $db, $fid;

	if($plugins->current_hook == 'admin_config_profile_fields_add_commit')
	{
		$db->update_query('profilefields', array('cid' => $mybb->get_input('category', 1)), "fid='{$fid}'");
	}
	else
	{
		$updated_profile_field['cid'] = $mybb->get_input('category', 1);
	}
}

// Hijack fields listing
function ougc_profiecats_admin_hijack()
{
	global $mybb;

	if(!$mybb->get_input('view_cat', 1))
	{
		return;
	}

	control_object($GLOBALS['db'], '
		function query($string, $hide_errors=0, $write_query=0)
		{
			if(!$write_query && strpos($string, \'SELECT * FROM \') !== false)
			{
				$string = strtr($string, array(
					\'ORDER\' => \'WHERE cid=\\\''.$mybb->get_input('view_cat', 1).'\\\' ORDER\'
				));
			}

			return parent::query($string, $hide_errors, $write_query);
		}
	');
}

// control_object by Zinga Burga from MyBBHacks ( mybbhacks.zingaburga.com ), 1.62
if(!function_exists('control_object'))
{
	function control_object(&$obj, $code)
	{
		static $cnt = 0;
		$newname = '_objcont_'.(++$cnt);
		$objserial = serialize($obj);
		$classname = get_class($obj);
		$checkstr = 'O:'.strlen($classname).':"'.$classname.'":';
		$checkstr_len = strlen($checkstr);
		if(substr($objserial, 0, $checkstr_len) == $checkstr)
		{
			$vars = array();
			// grab resources/object etc, stripping scope info from keys
			foreach((array)$obj as $k => $v)
			{
				if($p = strrpos($k, "\0"))
				{
					$k = substr($k, $p+1);
				}
				$vars[$k] = $v;
			}
			if(!empty($vars))
			{
				$code .= '
					function ___setvars(&$a) {
						foreach($a as $k => &$v)
							$this->$k = $v;
					}
				';
			}
			eval('class '.$newname.' extends '.$classname.' {'.$code.'}');
			$obj = unserialize('O:'.strlen($newname).':"'.$newname.'":'.substr($objserial, $checkstr_len));
			if(!empty($vars))
			{
				$obj->___setvars($vars);
			}
		}
		// else not a valid object or PHP serialize has changed
	}
}

// Plugin Class
class OUGC_PROFIECATS
{

	// Build the class
	function __construct()
	{
	}

	// Loads language strings
	function lang_load()
	{
		global $lang;

		isset($lang->setting_group_ougc_profiecats) or $lang->load('ougc_profiecats');
	}

	// Log admin action
	function log_action()
	{
		$data = array();

		if($this->cid)
		{
			$data['cid'] = $this->cid;
		}

		log_admin_action($data);
	}

	// Update the cache
	function update_cache()
	{
		global $db, $cache;

		$d = array();

		$query = $db->simple_select('ougc_profiecats_categories', '*', '', array('order_by' => 'disporder'));
		while($category = $db->fetch_array($query))
		{
			$d[$category['cid']] = $category;
		}

		$cache->update('ougc_profiecats_categories', $d);
	}

	// Clean input
	function clean_ints(&$val, $implode=false)
	{
		if(!is_array($val))
		{
			$val = (array)explode(',', (string)$val);
		}

		foreach($val as $k => &$v)
		{
			$v = (int)$v;
		}

		$val = array_filter($val);

		if($implode)
		{
			$val = (string)implode(',', $val);
		}

		return $val;
	}

	// Insert a new rate to the DB
	function insert_category($data, $cid=null, $update=false)
	{
		global $db;

		$cleandata = array();

		!isset($data['name']) or $cleandata['name'] = $db->escape_string($data['name']);
		!isset($data['forums']) or $cleandata['forums'] = $db->escape_string($this->clean_ints($data['forums'], true));
		!isset($data['active']) or $cleandata['active'] = (int)$data['active'];
		!isset($data['required']) or $cleandata['required'] = (int)$data['required'];
		!isset($data['disporder']) or $cleandata['disporder'] = (int)$data['disporder'];

		if($update)
		{
			$this->cid = (int)$cid;
			$db->update_query('ougc_profiecats_categories', $cleandata, 'cid=\''.$this->cid.'\'');
		}
		else
		{
			$this->cid = (int)$db->insert_query('ougc_profiecats_categories', $cleandata);
		}

		return true;
	}

	// Update espesific rate
	function update_category($data, $cid)
	{
		$this->insert_category($data, $cid, true);
	}

	// Get a award from the DB
	function get_category($cid)
	{
		global $db;

		$query = $db->simple_select('ougc_profiecats_categories', '*', 'cid=\''.(int)$cid.'\'');
		return $db->fetch_array($query);
	}

	// Generate a categories selection box.
	function generate_category_select($name, $selected)
	{
		global $db, $lang;
		$this->lang_load();

		$selected = (int)$selected;

		$select = "<select name=\"{$name}\">\n";

		$select_add = '';
		if($selected == 0)
		{
			$select_add = ' selected="selected"';
		}
		$select .= "<option value=\"0\"{$select_add}>{$lang->ougc_profiecats_admin_none}</option>\n";

		$query = $db->simple_select('ougc_profiecats_categories', '*', '', array('order_by' => 'name'));
		while($category = $db->fetch_array($query))
		{
			$select_add = '';
			if($selected == $category['cid'])
			{
				$select_add = ' selected="selected"';
			}
	
			$category['name'] = htmlspecialchars_uni($category['name']);
			$select .= "<option value=\"{$category['cid']}\"{$select_add}>{$category['name']}</option>\n";
		}

		$select .= "</select>\n";

		return $select;
	}
}

$GLOBALS['profiecats'] = new OUGC_PROFIECATS;