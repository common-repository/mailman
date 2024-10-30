<?php
/*  
	$Id: Mailman.php 165709 2009-10-22 12:48:15Z datenimperator $
	
	Copyright 2006 Christian Aust (email: datenimperator@gmx.de)

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

require_once "MailmanIntegration.php";

class Mailman {
	var $prefix = 'wpmailman';
	var $mi = null;
	
	function Mailman() {
		add_option(
			$this->prefix.'mailman_home', 
			'', 
			'Mailman home directory', 
			false);
		add_option(
			$this->prefix.'path_verified', 
			false, 
			'Mailman verification flag', 
			false);

		$this->mi = new MailmanIntegration(
			$this,
			get_option(
				$this->prefix.'path_verified'
			)
		);
	}
	
	function registerAdminOption() {
		if (function_exists('add_options_page')) {
			add_options_page(
				'Mailman', 
				'Mailman Integration', 
				9, 
				basename(__FILE__), 
				array(&$this, 'doAdminOptions')
			);
		}
	}
	
	function doProfileUpdate($userid) {
		$user_info = get_userdata($userid);
		if (isset($_POST['submit'])) {
			if (isset($_POST['subscribe'])) {
				$subscribe = $_POST['subscribe'];
			} else {
				$subscribe = array();
			}
			
			$curlists = $this->mi->getSelectedLists();
			foreach($curlists as $list) {
				if (!(in_array($list, $subscribe))) {
					$this->mi->unsubscribe($list, $user_info->user_email);
				} else {
					$this->mi->subscribe($list, $user_info->user_email);
				}
			}
		}
	}
	
	function doPersonalProfileOptions($profile_user) {
		$user_email = $profile_user->data->user_email;
		?>
		<h3>Mailing lists</h3>
		<table class="form-table">
		<?php
		$lists = $this->mi->getSelectedLists();
		if (count($lists) == 0) {
			echo "<tr><td colspan=\"2\">The administrator hasn't chosen and lists that you could subscribe to.</td></tr>";
		} else {
			foreach($lists as $list) {
				$abo = $this->mi->isMemberOf($list, $user_email);
			?>
			<tr>
				<th><label for="subscribe_<?php echo($list) ?>"><?php echo($list) ?></label></th>
				<td><input name="subscribe[]" type="checkbox" id="subscribe_<?php echo($list) ?>" value="<?php echo($list) ?>"<?php
					if ($abo == true) echo('checked="checked"');
					?> /> Subscribe to mailing list</td>
			</tr>
			<?php
			}
		}
		?>
		</table>
		<?php
	}
	
	function doAdminOptions() {
		if (isset($_POST['check_path'])) {
			$result = $this->mi->checkPath(escapeshellcmd($_POST['mailman_home']));
			
			if ($result === true) {
				update_option(
					$this->prefix.'mailman_home', 
					escapeshellcmd($_POST['mailman_home'])
				);
			?>
				<div class="updated"><p><strong><?php 
				update_option(
					$this->prefix.'path_verified', 
					true
				);
				$this->mi = new MailmanIntegration(
					$this,
					true
				);
				_e('Path verification successful, your setup looks ok.',
				$this->prefix)
				?></strong></p></div>
			
			<?php } else { ?>
			
				<div class="error"><p><strong><?php 
				update_option(
					$this->prefix.'mailman_home', 
					escapeshellcmd($_POST['mailman_home'])
				);
				update_option(
					$this->prefix.'path_verified', 
					false
				);
				$this->mi = new MailmanIntegration(
					$this,
					false
				);
				_e('The given path could not be verified.',
				$this->prefix);
				echo " ".$result;
				?></strong></p></div>
			
			<?php }
		}
		
		if (isset($_POST['info_update'])) {
		
			update_option(
				$this->prefix.'selected_lists', 
				( isset($_POST['active']) ? $_POST['active'] : array() )
			);

			update_option(
				$this->prefix.'default_lists', 
				( isset($_POST['default']) ? $_POST['default'] : array() )
			);

			update_option(
				$this->prefix.'send_welcome_msg', 
				( isset($_POST['send_welcome_msg']) ? $_POST['send_welcome_msg'] : false )
			);

			update_option(
				$this->prefix.'send_admin_msg', 
				( isset($_POST['send_admin_msg']) ? $_POST['send_admin_msg'] : false )
			);

			?><div class="updated"><p><strong><?php 
			_e('Settings updated successfully.',
			$this->prefix)
			?></strong></p></div><?php
		}
		
		$verified = $this->mi->verified;
		$selectedLists = $this->mi->getSelectedLists();
		$defaultLists = $this->mi->getDefaultLists();
		
		?>
		<script type="text/javascript">
		function check(list) {
			var def = document.getElementById("def_" + list);
			var sel = document.getElementById("sel_" + list);
			if (sel.checked) {
				def.disabled = false;
			} else {
				def.disabled = true;
				def.checked = false;
			}
		}
		</script>
		<div class=wrap>
		<form method="post">
		<input type="hidden" name="path_checked" value="0" />
		<?php screen_icon(); ?>
		<h2>Mailman Integration</h2>
		
		<h3><?php _e('System values', $this->prefix) ?></h3>
		<table class="form-table"> 
			
			<tr valign="top"> 
				<th scope="row">Mailman homedir</th> 
				<td>
					<input type="text" name="mailman_home" class="regular-text code" value="<?php echo get_option($this->prefix.'mailman_home'); ?>" 
					onChange="this.form['info_update'].disabled=true" />
					<span class="description">eg. /usr/share/mailman/</span>
				</td>
			</tr>
			
			<tr valign="top"> 
				<th scope="row">Mailman version</th> 
				<td>
					<?php echo $this->mi->getVersion(); ?>
				</td>
			</tr>

			<tr valign="top"> 
				<th scope="row">Local permissions</th> 
				<td>
					<?php echo $this->mi->checkPermissions(); ?>
				</td>
			</tr>

		</table>
		<div class="submit">
			<input type="submit" name="check_path" class="button" value="<?php _e('Check path') ?>" />
		</div>
		
		<h3><?php _e('Wordpress-enabled lists', $this->prefix) ?></h3>
		<p><?php _e('Only publicly advertised lists will be accessible through this plugin. See the <a href="http://www.list.org/mailman-admin/node21.html" target="_new">GNU Mailman docs</a> for the <em>advertised</em> property.', $this->prefix) ?></p>
		<table class="widefat"> 
			<colgroup>
				<col width="33%" />
				<col />
				<col />
				<col width="33%" />
			</colgroup>
			<tr>
				<th>List name</th>
				<th>Active</th>
				<th>Subscribe when registered</th>
				<th>List info</th>
			<tr>
			<?php 
				if ($verified == true) {
					$lists = array();
					try {
						$lists = $this->mi->getLists();
					} catch (Exception $e) {
						echo "<tr><td colspan=\"4\">".$e->getMessage()."</td></tr>";
					}
					foreach ($lists as $list) { 
						$info = $this->mi->getListinfo($list);
						$selected = in_array($list, $selectedLists);
			?>
			
			<tr valign="top">
				<td><?php
					echo " <strong>{$list}@{$info['host_name']}</strong>";
					?></td>
					
				<td align="center"><input type="checkbox" name="active[]" id="sel_<?php echo $list ?>" value="<?php echo $list ?>" onChange="check('<?php echo $list ?>')" <?php
					if ($selected == true) echo('checked=\"checked\"');
					?> /></td>
					
				<td align="center"><input type="checkbox" name="default[]" id="def_<?php echo $list ?>" value="<?php echo $list ?>" <?php
					if ($selected != true) echo('disabled=\"disabled\" ');
					if (in_array($list, $defaultLists)) echo('checked=\"checked\" ');
					?> /></td>
					
				<td><?php echo $info['info']; ?></td>
			</tr>

			<?php }
		}  else { ?>
			<tr><td colspan="4"><?php
			_e('Please verify your Mailman path before choosing a list.', $this->prefix);
			?></td></tr><?php
		} ?>
		</table>

		<h3 style="margin-top:5px;padding-top:1.5em;"><?php _e('List behavior', $this->prefix) ?></h3>
		<table class="form-table"> 
			
			<tr valign="middle"> 
				<th scope="row">Send welcome msg</th>
				<td><label for="send_welcome_msg">
				<input type="checkbox" name="send_welcome_msg" id="send_welcome_msg" value="1" <?php
				if ($this->mi->getSendWelcomeMsg() == true) echo('checked="checked"');
				?> />
				Should new members receive a welcome message?
				</label>
				</td>
			</tr>
			
			<tr valign="middle"> 
				<th scope="row">Notify list admins</th>
				<td><label for="send_admin_msg">
				<input type="checkbox" name="send_admin_msg" id="send_admin_msg" value="1" <?php
				if ($this->mi->getSendAdminMsg() == true) echo('checked="checked"');
				?> />
				Will list admins receive a msg about subscription success or failure?
				</label>
				</td>
			</tr>
			
		</table>

		<div class="submit">
		<input type="submit" name="info_update" class="button-primary" value="<?php
		_e('Update Options')
		?> &rsaquo;" enabled="<?php echo $verified; ?>" /></div>
		</form>
		</div><?php
	}
	
	function doRegister($userid) {
		$user_info = get_userdata($userid);
		foreach ($this->mi->getDefaultLists() as $list) {
			$this->mi->subscribe($list, $user_info->user_email);
		}
	}
	
}
?>