<?php
/*
Plugin Name: WP Mailman Integration
Plugin URI: http://software-consultant.net/opensource/mailman-integration-fuer-wordpress
Description: wp-mailman integrates Wordpress with the GNU Mailman mailing list manager.
Version: 1.2
Author: Christian Aust
Author URI: http://software-consultant.net/

Copyright 2006 Christian Aust

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
require_once('Mailman.php');

$mailman_plugin = new Mailman();
if(defined('ABSPATH') && defined('WPINC')) {
	load_plugin_textdomain($mailman_plugin->prefix, 'wp-content/plugins/wp-mailman');
	
	add_action('user_register', array(&$mailman_plugin, 'doRegister'));

	add_action('show_user_profile', array(&$mailman_plugin, 'doPersonalProfileOptions'));
	add_action('edit_user_profile', array(&$mailman_plugin, 'doPersonalProfileOptions'));
	add_action('profile_update', array(&$mailman_plugin, 'doProfileUpdate'));

	add_action('admin_menu', array(&$mailman_plugin, 'registerAdminOption'));
}
?>