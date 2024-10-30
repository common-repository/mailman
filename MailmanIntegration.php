<?php
/*
	$Id: MailmanIntegration.php 165709 2009-10-22 12:48:15Z datenimperator $
	
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

class PythonValue {
	var $value;
	var $charlist = " '";
	
	function trimChar($val) {
		return ltrim(rtrim($val, $this->charlist), $this->charlist);
	}
	
	function PythonValue($orig) {
		if (!(isset($orig))) {
			$value = null;
			return;
		}
		$tmp = ltrim(rtrim($orig));
		if ($orig == '') {
			$value = null;
			return;
		}
		if (preg_match("/^\[.*\]$/", $tmp)) {
			//Array-Definition [val,val,val]
			$a = explode(",", substr($tmp, 1, strlen($tmp)-2));
			array_walk($a, array ($this, 'trimChar'));
			$this->value = $a;
		} else {
			if ($tmp == 'True') {
				$this->value = true;
			} elseif ($tmp == 'False') {
				$this->value = false;
			} else {
				$this->value = $this->trimChar($tmp);
			}
		}
	}
}

class MailmanIntegration {
	var $mm;
	var $commands = array(
		"add" => array(
			"cmd"=>"bin/add_members",
			"opt"=>"-r -"
		),
		"remove" => array(
			"cmd"=>"bin/remove_members",
			"opt"=>""
		),
		"version" => array(
			"cmd"=>"bin/version",
			"opt"=>""
		),
		"list_lists" => array(
			"cmd"=>"bin/list_lists",
			"opt"=>"-ba"
		),
		"list_members" => array(
			"cmd"=>"bin/list_members",
			"opt"=>""
		),
		"config_list" => array(
			"cmd"=>"bin/config_list",
			"opt"=>"-o -"
		)
	);
	var $listinfo = array();
	var $verified = false;
	
	function MailmanIntegration($parent, $ver = false) {
		$this->mm = $parent;
		$this->verified = $ver;
	}
	
	function getCommand($name) {
		$cmd = $this->commands[$name];
		if ($cmd != null) {
			return get_option($this->mm->prefix.'mailman_home').
				$cmd["cmd"].' '.$cmd["opt"].' 2>&1';
		}
		return 'na';
	}
	
	function parseProperties($data) {
		$ret = array();
		$charlist = " '";
		foreach ($data as $row) {
			if ($row && $row{0} != '#') {
				$tmp = explode('=', ltrim(rtrim($row)));
				if (count($tmp) == 2) {
					$pyval = new PythonValue($tmp[1]);
					//echo("PyVal: ".$pyval->value);
					$key = $pyval->trimChar($tmp[0]);
					$ret[$key] = $pyval->value;
				}
			}
		}
		return $ret;
	}
	
	function checkPermissions() {
		
		$home = get_option(
			$this->mm->prefix.'mailman_home'
		);

		if (function_exists('posix_getuid') && function_exists('posix_getpwuid')) {
			$uid = posix_getuid();
			$user = posix_getpwuid($uid);
			$groups = posix_getgroups();
			$name = $user['name'];
			$gid = $user['gid'];

			$cmd = dirname(__FILE__)."/listpath.py";
			if (!(is_executable($cmd))) {
				return "$cmd is not executable";
			}
			exec("$cmd -b $home", $result, $code);
			
			if ($code == 0) {
				$list_dir = $result[0];
				$owner_id = fileowner($list_dir);
				$owner_group = filegroup($list_dir);
				if ($owner_id == $uid) return "OK";
				if (in_array($owner_group, $groups)) return "OK";
				
				return "Directory $list_dir is not accessible for user $name ($uid) with groups (".join($groups, ', ').")";
			}
			return "n/a";
		}
		return "n/a";
	}
	
	function checkPath($newpath) {
		$home = get_option(
			$this->mm->prefix.'mailman_home'
		);
		foreach ($this->commands as $i => $cmd) {
			$file = $home.$cmd['cmd'];
			
			if (!(file_exists($file))) {
				return "$file not found";
			}
			if (!(is_executable($file))) {
				return "$file is not executable";
			}
		}
		return true;
	}
	
	function getLists() {
		if ($this->verified == false)
			return null;
	   $cmd = $this->getCommand("list_lists");
	   exec($cmd, $result, $code);
	   if ($code == 0) {
	   	return $result;
	   }
	   throw new Exception("Mailman lists are not accessible: ".$result[count($result)-1], $code);
	}
	
	function subscribe($list, $email) {
		if ($this->verified == false) {
			error_log("Still unverified, can't subscribe $email to $list");
			return null;
		}
	   $cmd = $this->getCommand("add");
	   if ($this->getSendWelcomeMsg()) {
		   $cmd .= ' -w y';
		} else {
		   $cmd .= ' -w n';
		}
	   if ($this->getSendAdminMsg()) {
		   $cmd .= ' -a y';
		} else {
		   $cmd .= ' -a n';
		}
		
		$f = popen("$cmd $list", "w");
		if (is_resource($f)) {
			fwrite($f, $email);
			pclose($f);	
		} else {
			error_log("Error writing to `$cmd $list`");
		}
	}
	
	function unsubscribe($list, $email) {
		if ($this->verified == false) {
			error_log("Still unverified, can't unsubscribe $email from $list");
			return null;
		}
		if (!$this->isMemberOf($list, $email)) return null;
		
	  $cmd = $this->getCommand("remove");
	  $cmd .= " $list $email";
	  exec($cmd, $result, $code);
	  if ($code == 0) {
	  	return $result;
	  } else {
	  	error_log("Executing $cmd returned: $code ".join($result, '\n  '));
	  }
	}
	
	function getSelectedLists() {
		$sel = get_option(
			$this->mm->prefix.'selected_lists'
		);
		return ($sel == null) ? array() : $sel;
	}
	
	function getDefaultLists() {
		$default = get_option(
			$this->mm->prefix.'default_lists'
		);
		return ($default == null) ? array() : $default;
	}
	
	function getSendWelcomeMsg() {
		$val = get_option(
			$this->mm->prefix.'send_welcome_msg'
		);
		return ($val == null) ? false : $val;
	}
	
	function getSendAdminMsg() {
		$val = get_option(
			$this->mm->prefix.'send_admin_msg'
		);
		return ($val == null) ? false : $val;
	}
	
	function getListinfo($name) {
		if ($this->verified == false)
			return null;
		if (array_key_exists($name, $this->listinfo)) {
			return $this->listinfo[$name];
		} else {
			$cmd = $this->getCommand("config_list");
			exec($cmd." $name", $result, $code);
			if ($code == 0) {
				$this->listinfo[$name] = $this->parseProperties($result);
				return $this->listinfo[$name];
			}
			return array();
	   }
	}
	
	function isMemberOf($list, $email) {
		if ($this->verified == false)
			return false;
		$cmd = $this->getCommand("list_members");
		exec("$cmd $list", $result, $code);
		if ($code == 0) {
			return in_array($email, $result);
		}
		return false;
	}
	
	function getVersion() {
		if ($this->verified == false)
			return 'n/a';
	   $cmd = $this->getCommand("version");
	   exec($cmd, $result, $code);
	   if ($code == 0) {
	   	return substr(strrchr($result[0], ': '), 1);
	   }
	   return 'n/a';
	}
}
?>