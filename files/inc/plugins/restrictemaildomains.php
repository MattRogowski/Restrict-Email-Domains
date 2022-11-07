<?php

/**
 * Restrict Email Domains 1.2.0

 * Copyright 2017 Matthew Rogowski

 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at

 ** http://www.apache.org/licenses/LICENSE-2.0

 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/

if (!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook("member_register_end", "restrictemaildomains_member_register_end");
$plugins->add_hook("xmlhttp", "restrictemaildomains_xmlhttp");
$plugins->add_hook("datahandler_user_validate", "restrictemaildomains_datahandler_user_validate");

function restrictemaildomains_info()
{
	return array(
		"name" => "Restrict Email Domains",
		"description" => "Allows you to restrict which domains users can register with.",
		"website" => "https://github.com/MattRogowski/Restrict-Email-Domains",
		"author" => "Matt Rogowski",
		"authorsite" => "https://matt.rogow.ski",
		"version" => "1.3.0",
		"compatibility" => "18*",
		"codename" => "restrictemaildomains"
	);
}

function restrictemaildomains_activate()
{
	global $db, $mybb;

	restrictemaildomains_deactivate();

	$settings_group = array(
		"name" => "restrictemaildomains",
		"title" => "Restrict Email Domains Settings",
		"description" => "Settings for the restrict email domains plugin.",
		"disporder" => "28",
		"isdefault" => 0
	);
	$db->insert_query("settinggroups", $settings_group);
	$gid = $db->insert_id();

	$settings = array();
	$settings[] = array(
		"name" => "restrictemaildomains_enabled",
		"title" => "Enable email domain restriction??",
		"description" => "If you want to temporarily stop the restriction but don't want to lose the list of domains below, set this to No instead of deactivating the plugin.",
		"optionscode" => "yesno",
		"value" => "1"
	);
	$settings[] = array(
		"name" => "restrictemaildomains_inadmin",
		"title" => "Enable email domain restriction in ACP??",
		"description" => "Do you want this check to be performed when editing a user in the ACP?? Select No if you want to be able to set any email address.",
		"optionscode" => "yesno",
		"value" => "1"
	);
	$settings[] = array(
		"name" => "restrictemaildomains_domains",
		"title" => "Domains to allow",
		"description" => "Which domains would you like to allow?? Put one domain on a line. Examples: gmail.com, live.com, hotmail.com",
		"optionscode" => "textarea",
		"value" => ""
	);
	$i = 1;
	foreach ($settings as $setting)
	{
		$insert = array(
			"name" => $db->escape_string($setting['name']),
			"title" => $db->escape_string($setting['title']),
			"description" => $db->escape_string($setting['description']),
			"optionscode" => $db->escape_string($setting['optionscode']),
			"value" => $db->escape_string($setting['value']),
			"disporder" => intval($i),
			"gid" => intval($gid),
		);
		$db->insert_query("settings", $insert);
		$i++;
	}

	rebuild_settings();

	if ($mybb->version_code >= 1823)
	{
		require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';
		find_replace_templatesets('member_register', '#' . preg_quote('{$footer}') . '#', "{\$validator_extra_script}\n{\$footer}");
	}
}

function restrictemaildomains_deactivate()
{
	global $db;

	$db->delete_query("settinggroups", "name = 'restrictemaildomains'");

	$settings = array(
		"restrictemaildomains_enabled",
		"restrictemaildomains_inadmin",
		"restrictemaildomains_domains"
	);
	$settings = "'" . implode("','", $settings) . "'";
	$db->delete_query("settings", "name IN ({$settings})");

	rebuild_settings();

	require MYBB_ROOT . '/inc/adminfunctions_templates.php';
	find_replace_templatesets('member_register', '#' . preg_quote("{\$validator_extra_script}\n") . '#', '');
}

function restrictemaildomains_member_register_end()
{
	global $mybb;

	if ($mybb->settings['restrictemaildomains_enabled'] != 1 || empty($mybb->settings['restrictemaildomains_domains']))
	{
		if ($mybb->version_code >= 1823)
		{
			global $validator_extra_script;
			$validator_extra_script = "";
		}

		return;
	}

	if ($mybb->version_code <= 1812)
	{
		global $validator_extra;
		$validator_extra .= "
	$('#email').rules('add', {
		required: true,
		email: true,
		remote: {
			url: 'xmlhttp.php?action=restrictemaildomains_check_email',
			type: 'post',
			dataType: 'json',
			data:
			{
				email: function () {
					return $('#email').val();
				},
				my_post_key: my_post_key
			},
		}
	});
		";
	}
	else if ($mybb->version_code >= 1813 && $mybb->version_code <= 1822)
	{
		global $validator_javascript;
		$validator_javascript .= "
	$('#email').rules('add', {
		required: true,
		email: true,
		remote: {
			url: 'xmlhttp.php?action=restrictemaildomains_check_email',
			type: 'post',
			dataType: 'json',
			data:
			{
				email: function () {
					return $('#email').val();
				},
				my_post_key: my_post_key
			},
		}
	});
		";
	}
	else
	{
		global $validator_extra_script;
		$validator_extra_script = "<script type=\"text/javascript\">
	$(function () {
		$('#email').rules('add', {
			required: true,
			email: true,
			remote: {
				url: 'xmlhttp.php?action=restrictemaildomains_check_email',
				type: 'post',
				dataType: 'json',
				data: {
					email: function () {
						return $('#email').val();
					},
					my_post_key: my_post_key
				},
			}
		});
	});
</script>";
	}
}

function restrictemaildomains_xmlhttp()
{
	global $mybb, $lang;

	if ($mybb->input['action'] == 'restrictemaildomains_check_email')
	{
		if (!verify_post_check($mybb->get_input('my_post_key'), true))
		{
			xmlhttp_error($lang->invalid_post_code);
		}

		$result = restrictemaildomains_check($mybb->input['email']);

		if ($result !== true)
		{
			echo json_encode($result);
		}
		else
		{
			echo json_encode("true");
		}
	}
}

function restrictemaildomains_datahandler_user_validate()
{
	global $mybb, $userhandler;

	if ($mybb->settings['restrictemaildomains_enabled'] != 1 || !((THIS_SCRIPT == "member.php" && $mybb->input['action'] == "do_register") || (THIS_SCRIPT == "usercp.php" && $mybb->input['action'] == "do_email")) || ($mybb->settings['restrictemaildomains_enabled'] == 1 && defined("IN_ADMINCP") && $mybb->settings['restrictemaildomains_inadmin'] != 1))
	{
		return;
	}

	$userhandler->data['email'] = $mybb->input['email'];

	if (!$userhandler->verify_email())
	{
		return;
	}

	$result = restrictemaildomains_check($mybb->input['email']);

	if ($result !== true)
	{
		$userhandler->set_error($result);
	}
}

function restrictemaildomains_check($email)
{
	global $mybb, $lang;

	$lang->load("restrictemaildomains");

	$is_allowed_email_domain = false;

	if (empty($mybb->settings['restrictemaildomains_domains']))
	{
		// needs to return true if no domains are specified, otherwise it won't allow any emails
		$is_allowed_email_domain = true;
	}
	else
	{
		// we just want to check the email domain itself here
		$allowed_email_domains = explode("\n", $mybb->settings['restrictemaildomains_domains']);
		// need to trim blank spaces off the email domains
		foreach ($allowed_email_domains as $key => $domain)
		{
			$allowed_email_domains[$key] = trim($domain);
		}
		$exploded_email = explode("@", $email);
		$email_domain = $exploded_email[1];
		if (in_array($email_domain, $allowed_email_domains))
		{
			$is_allowed_email_domain = true;
		}

		if (!$is_allowed_email_domain)
		{
			foreach ($allowed_email_domains as $domain)
			{
				if (substr($domain, 0, 1) == '.')
				{
					if (substr($mybb->input['email'], -strlen($domain)) == $domain)
					{
						$is_allowed_email_domain = true;
					}
				}
			}
		}
	}

	if (!$is_allowed_email_domain)
	{
		$error = $lang->invalid_email_domain;

		$allowed_email_domains = implode(", ", $allowed_email_domains);

		if (!empty($allowed_email_domains))
		{
			$error .= $lang->sprintf($lang->valid_email_domains, $allowed_email_domains);
		}

		return $error;
	}

	return true;
}
