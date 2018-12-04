<?php
//Outputs variable data for debuggins
function debugvar($variable) {
	file_put_contents('debug.txt', print_r($variable, true));
}
//Initializes a sqlite database with a table for parameters, authentication, and a table for webms, returns error code if there is an error
function initdb() {
	$db = new PDO('sqlite:db.sqlite');
	$dbcmds = ['CREATE TABLE IF NOT EXISTS config(
		name TEXT NOT NULL,
		value TEXT NOT NULL
		)',
	'CREATE TABLE IF NOT EXISTS webms(
		file TEXT NOT NULL,
		)',
	'CREATE TABLE IF NOT EXISTS auth(
		username TEXT NOT NULL,
		password TEXT NOT NULL
		)'
	];
	foreach ($dbcmds as $cmd) {
		$db->exec($cmd);
	}
	$clean = 1;
	foreach ($db->errorInfo() as $error) {
		if ($error != 0) {
			$clean = $error;
		}
	}
	return $clean;
}
//Returns panel admins as an array
function get_panel_admins() {
	$db = new PDO('sqlite:db.sqlite');
	$query = $db->prepare('SELECT username FROM auth');
	$query->execute();
	$result = $query->fetchAll(PDO::FETCH_COLUMN, 0);
	return $result;
}
//Adds admins listed in array
function add_admin($user, $pass) {
	$db = new PDO('sqlite:db.sqlite');
	$admins = get_panel_admins();
	$username = strtolower($user);
	$password = password_hash($pass, PASSWORD_DEFAULT);
	if (!in_array($username, $admins)) {
		$query = $db->prepare('INSERT INTO auth (username, password) VALUES (:username, :password)');
		$query->bindValue(':username', $username, PDO::PARAM_STR);
		$query->bindValue(':password', $password, PDO::PARAM_STR);
		$query->execute();
	} else {
		echo "Admin already exists";
	}
}
//Changes an admin password to the specified password
function change_admin_pass($users) {
	$db = new PDO('sqlite:db.sqlite');
	foreach ($users as $name=>$pass) {
		if (!empty($pass)) {
			$username = strtolower($name);
			$password = password_hash($pass, PASSWORD_DEFAULT);
			$query = $db->prepare('UPDATE auth set password=:password WHERE username=:username');
			$query->bindValue(':username', $username, PDO::PARAM_STR);
			$query->bindValue(':password', $password, PDO::PARAM_STR);
			$query->execute();
		}
	}
}
//Deletes admins listed in array
function delete_admin($delete) {
	$db = new PDO('sqlite:db.sqlite');
	foreach ($delete as $name) {
		$query = $db->prepare('SELECT count(username) FROM auth');
		$query->execute();
		$count = $query->fetch();
		$count = $count[0];
		if ($count > '1') {
			$username = strtolower($name);
			$query = $db->prepare('DELETE FROM auth WHERE username=:name');
			$query->bindValue(':name', $username, PDO::PARAM_STR);
			$query->execute();
		} else {
			echo "Cannot delete last admin";
		}
	}
}
//Returns the config as an arrat
function get_config() {
	$db = new PDO('sqlite:db.sqlite');
	$query = $db->prepare('SELECT name,value FROM config');
	$query->execute();
	$result = $query->fetchAll();
	return $result;
}
//Returns the parameter of a config var given a name
function get_config_var($name) {
	$db = new PDO('sqlite:db.sqlite');
	$query = $db->prepare('SELECT value FROM config WHERE name=:name');
	$query->bindValue(':name', $name, PDO::PARAM_STR);
	$query->execute();
	$result = $query->fetch(PDO::FETCH_ASSOC);
	return $result['value'];
}
//Sets the value of a config var given a name and value
function update_config($config) {
	$db = new PDO('sqlite:db.sqlite');
	foreach ($config as $name=>$value) {
		$query = $db->prepare('UPDATE config SET value=:value WHERE name=:name');
		$query->bindValue(':name', $name, PDO::PARAM_STR);
		$query->bindValue(':value', $value, PDO::PARAM_STR);
		$query->execute();
	}
}
//Check if for needle in multidiensional array
function in_array_m($needle, $haystack) {
	foreach ($haystack as $element) {
		if (in_array($needle, $element)) {
			return true;
		}
	}
	return false;
}
//Returns the url of a random webm from the database
function get_random_webm() {
	$webms = get_webms();
	srand();
	$file = $webms[array_rand($webms)][0];
	return $file;
}
//Returns urls of all wemb as an array
function get_webms() {
	$db = new PDO('sqlite:db.sqlite');
	$query = $db->prepare('SELECT * FROM webms');
	$query->execute();
	$result = $query->fetchAll();
	return $result;
}
//Adds a webm to the database
function add_webm($file, $url) {
	$webms = get_webms();
	$name = str_replace('.webm', '', $file);
	if (in_array_m("$name.webm", $webms)) {
		echo("A WEBM by that name already exists");
		exit();
	}
	if (strpos($url, '.webm') !== false) {
		file_put_contents("webms/$name.webm", fopen("$url", 'r'));
	} elseif (strpos($url, '.gif') !== false) {
		file_put_contents("webms/$name.gif", fopen("$url", 'r'));
		$escapedGif = escapeshellarg("webms/$name.gif");
		$escapedWebm = escapeshellarg("webms/$name.webm");
		$ffmpegArgs = '-c:v libvpx -crf 4 -b:v 5000K -auto-alt-ref 0 -threads 8';
		$ffmpegString = "ffmpeg -i $escapedGif $ffmpegArgs $escapedWebm 2>&1";
		exec("$ffmpegString");
		unlink("webms/$name.gif");
	} else {
		echo('Error, probably bad URL');
		exit();
	}
	$db = new PDO('sqlite:db.sqlite');
	$query = $db->prepare('INSERT INTO webms (file) VALUES (:file)');
	$query->bindValue(':file', "$name.webm", PDO::PARAM_STR);
	$query->execute();
}
//Removes a webm from tha database
function delete_webm($delete) {
	$db = new PDO('sqlite:db.sqlite');
	foreach ($delete as $file) {
		$query = $db->prepare('DELETE FROM webms WHERE file=:file');
		$query->bindValue(':file', $file, PDO::PARAM_STR);
		$query->execute();
	}
}
//Display the setup form
function disp_setup() {
	$setup = <<<'EOSETUP'
<form name="setup" method="post" action="">
<table align="center" style="width: 50%;">
	<tr>
		<td>Panel username:</td>
		<td><input type="text" style="width: 100%;" name="user" placeholder="Panel username" required></td>
	</tr>
	<tr>
		<td>Panel password:</td>
		<td><input type="password" style="width: 100%;" name="pass" placeholder="Panel password" required></td>
	</tr>
	<tr>
		<td colspan="3"><input type="submit" value="Initialize"></td>
	</tr>
</table>
</form>
EOSETUP;
	echo $setup;
}
//Display the login
function disp_login() {
	$login = <<<'EOLOGIN'
<form name="login" method="post" action="">
<table align="center" style="width: 50%;">
	<tr>
		<td>Username:</td>
		<td><input type="text" style="width: 100%;" name="username" placeholder="Panel username" required></td>
	</tr>
	<tr>
		<td>Password:</td>
		<td><input type="password" style="width: 100%;" name="password" placeholder="Panel password" required></td>
	</tr>
	<tr>
		<td colspan="3"><input type="submit" value="Login"></td>
	</tr>
</table>
</form>
EOLOGIN;
	echo $login;
}


