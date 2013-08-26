<?php

 /* ============================================================
  *
  *						  UnPS-API Backend
  *
  *	  Remember to sanitize everything before sending it here!
  *
  * ============================================================
  */

function checkRemoteFile($link){
	if (@file_get_contents($link)): return true;
	else: return false;
	endif;
}

include('hashpass.php');

class api{
	// Begin Short
	function shorten($apidb, $apikey, $sdb, $link, $dpass=null){
		$apisql = "SELECT * FROM `users` WHERE `key` = '$apikey' LIMIT 1";
		if(!$result = $apidb->query($apisql)) return 'ERROR: ['.$apidb->error.']';
		if($row = $result->fetch_assoc()){
			$canshort = $row['short'];
			$name = $row['name'];
			
			$ip = $_SERVER['REMOTE_ADDR'];
			
			$apisql = "INSERT INTO `apiuse` (time, name, apikey, ip, type, allowed, misc) VALUES (NOW(), '$name', '$apikey', '$ip', 'Link Shorten', '$canshort', '$link')";
			if(!$result = $apidb->query($apisql)) return 'ERROR: ['.$apidb->error.']';
		}
		if($canshort != 1) return '<div id="error">You are not authorized to shorten links</div>';
		
		$sql = "SELECT * FROM `links` WHERE `link` = '$link' LIMIT 1;";
		if($result = $sdb->query($sql)){
			if($row = $result->fetch_assoc()){
				$short = $row['shortlink'];
				return "<div id=\"error\">Existing link: <a href=\"http://unps.us/?l=$short\" target=\"$short\">http://unps.us/?l=$short</a></div>";
			}
		}
		if(checkRemoteFile($link) !== true) return "<div id=\"error\">Dead Link: $link</div>";
		$short = substr(number_format(time() * mt_rand(),0,'',''),0,10); 
		$short = base_convert($short, 10, 36); 
		
		$dpass = substr(number_format(time() * mt_rand(),0,'',''),0,10); 
		$dpass = base_convert($short.$dpass, 10, 36); 

		if($dpass != null): $sql = "INSERT INTO `links` (link, shortlink, dpass) VALUES ('$link', '$short', '$dpass')";
		else: $sql = "INSERT INTO `links` (link, shortlink, dpass) VALUES ('$link', '$short', '$apikey')";
		endif;
		
		if($result = $sdb->query($sql)): return "Shortened: <a href=\"http://unps.us/?l=$short\" target=\"$short\">http://unps.us/?l=$short</a><br />Your link deletion password (write this down): $dpass";
		else: return '<div id="error">ERROR: ['.$sdb->error.']</div>';
		endif;
	}
	
	function delShort ($apidb, $apikey, $sdb, $link, $dpass=null){
		$apisql = "SELECT * FROM `users` WHERE `key` = '$apikey' LIMIT 1";
		if(!$result = $apidb->query($apisql)) return 'ERROR: ['.$apidb->error.']';
		if($row = $result->fetch_assoc()){
			$canshort = $row['short'];
			$name = $row['name'];
			
			$ip = $_SERVER['REMOTE_ADDR'];
			
			$apisql = "INSERT INTO `apiuse` (time, name, apikey, ip, type, allowed, misc) VALUES (NOW(), '$name', '$apikey', '$ip', 'Short Link Delete', '$canshort', '$link')";
			if(!$result = $apidb->query($apisql)) return 'ERROR: ['.$apidb->error.']';
		}
		if($canshort != 1) return '<div id="error">You are not authorized to delete short links</div>';
		
		$sql = "SELECT * FROM `links` WHERE `shortlink` = '$link' LIMIT 1;";
		if($result = $sdb->query($sql)){
			if($row = $result->fetch_assoc()){
				$short = $row['shortlink'];
				$password = $row['dpass'];
				
				if($dpass != null) $apikey = $dpass;
				
				if($apikey == $password){
					$sql = "DELETE FROM `links` WHERE `shortlink` = '$link' AND `dpass` = '$apikey' LIMIT 1;";
					if(!$result = $sdb->query($sql)) return '<div id="error">ERROR: ['.$sdb->error.'</div>]';
					echo "Deleted: $link";
					return;
				}else{ return "<div id=\"error\">The password doesn't match. Delete $link aborted!</div>"; }
			}
		}else{ return '<div id="error">ERROR: ['.$sdb->error.']</div>'; }
	}

	function reportLink($apidb, $apikey, $sdb, $link, $reason){
		$apisql = "SELECT * FROM `users` WHERE `key` = '$apikey' LIMIT 1;";
		if(!$result = $apidb->query($apisql)) return 'ERROR: ['.$apidb->error.']';
		if($row = $result->fetch_assoc()){
			$canshort = $row['short'];
			$name = $row['name'];
			
			$ip = $_SERVER['REMOTE_ADDR'];

			$apisql = "INSERT INTO `apiuse` (time, name, apikey, ip, type, allowed, misc) VALUES (NOW(), '$name', '$apikey', '$ip', 'Report Link', '$canshort', '$link')";
			if(!$result = $apidb->query($apisql)) return 'ERROR: ['.$apidb->error.']';
		}
		if($canshort != 1) return '<div id="error">You are not authorized to shorten links, meaning you also can\'t report false negatives</div>';

		$sql = "INSERT INTO `manual` (time, apikey, ip, link, reason) VALUES(NOW(), '$apikey', '$ip', '$link', '$reason');";
		if(!$result = $sdb->query($sql)) return '<div id="error">ERROR: ['.$sdb->error.']</div>';
		return "Reported $link. Please check back in a day or two";
	}

	// End Short
}

?>