<!DOCTYPE html>
<html>
<head>
<style>
body {
  background: url("background.jpg");
  background-repeat: repeat-y;
  background-size: cover;
  color: white;
  margin: 0;
  padding: 0;
  left: 0;
  right: 0;
  position: absolute;
  font-size: 16px;
  text-align: center;
  font-family: "Lucida Console", Monaco, monospace;
}
form {
  border: 0;
  margin: 0;
}
ul {
  list-style-type: none;
  margin: 0;
  padding: 0;
  overflow: hidden;
}
li {
  float: left;
  display: block;
}
summary {
  background: rgba(255, 0, 0, .1);
  text-align: left;;
  font-size: 18px;
}
table {
  max-width: 100%;
  border-spacing: 0;
  text-align: left;
  font-size: 16px;
}
tr {
  max-width: 100%;
}
th, td {
  height: 100%;
  padding: 10px;
  overflow-x: hidden;
  vertical-align: middle;
}
tr:nth-child(even) {
  background-color: rgba(255, 255, 255, 0.50);
}
tr:nth-child(odd) {
  background-color: rgba(255, 255, 255, 0.25);
}
input {
  border: 0;
  box-sizing: border-box;
  color: white;
  text-indent: 0px;
  font-size: 16px;
  background: rgba(0, 0, 0, 0);
  font-family: "Lucida Console", Monaco, monospace;
  width: 100%;
}
</style>
	<title>GIF Portal</title>
</head>
<body>
<?php
header('Content-type: text/html; charset=utf-8');
ini_set('display_errors', 1);
ini_set('session.save_path', getcwd());
error_reporting(-1);
include 'functions.php';
session_start();
if (file_exists('db.sqlite')) {
	if (isset($_SESSION['username'])) {
		if (isset($_POST['logout'])) {
			session_destroy();
			header("Refresh:1");
		}
		if (!empty($_POST['add_admin_name']) && !empty($_POST['add_admin_pass'])) {
			add_admin($_POST['add_admin_name'], $_POST['add_admin_pass']);
		}
		if (!empty($_POST['add_gif_name']) && !empty($_POST['add_gif_url'])) {
			add_gif($_POST['add_gif_name'], $_POST['add_gif_url']);
		}
		if (isset($_POST['config'])) {
			update_config($_POST['config']);
		}
		if (isset($_POST['delete_admin'])) {
			delete_admin($_POST['delete_admin']);
		}
		if (isset($_POST['delete_gif'])) {
			delete_gif($_POST['delete_gif']);
		}?>
<div style="align: right; height: 5vh; background: rgba(0, 0, 0, .5);">
<ul>
<?php
$username = $_SESSION['username'];
echo "<li>$username Logged in</li>";
?>
<form name="logout" method="post" action="">
	<li><input type="hidden" name="logout" value="logout"></li>
	<input style="float: right; width: unset;" type="submit" value="Log Out">
</form>
</div>
<div style="overflow-y: scroll; height: 95vh">
<details>
<summary>Panel</summary>
<form name="panel" method="post" action="">
<table align="center">
	<tr>
		<th>Panel Admins</th>
		<th>Delete</th>
		<th>Change Password</th>
	</tr>
	<?php
		$admins = get_panel_admins();
		foreach ($admins as $element) {
			$name = $element;
			echo "<tr>";
			echo "<td>$name</td>";
			echo "<td><input type=\"checkbox\" name=\"delete_admin[]\" value=\"$name\"></td>";
			echo "<td><input type=\"password\" name=\"change_admin_pass[$name]\" placeholder=\"Password\"></td>";
			echo "</tr>";
		}?>
	<tr>
		<td><input type="text" name="add_admin_name" placeholder="Username"></td>
		<td colspan="2"><input type="password" name="add_admin_pass" placeholder="Password"></td>
	<tr>
		<th colspan="3"><input type="submit" value="Update"></th>
	</tr>
</table>
</form>
</details>
<details>
<summary>Config</summary>
<form name="Config" method="post" action="">
<table align="center">
	<tr>
		<th>Setting</th>
		<th>Value</th>
	</tr>
	<?php
		$config = get_config();
		foreach ($config as $element) {
			$name = $element['name'];
			$value = $element['value'];
			echo "<tr>";
			echo "<td>$name</td>";
			echo "<td><input type=\"text\" name=\"config[$name]\" value=\"$value\"></td>";
			echo "</tr>";
		}?>
		<th colspan="3"><input type="submit" value="Update"></th>
	</tr>
</table>
</form>
</details>
<details>
<summary>GIFs</summary>
</details>
<form name="GIFs" method="post" action="">
<table align="center">
	<tr>
		<th>Name</th>
		<th>URL</th>
		<th>Delete</th>
	</tr>
		<th><input type="text" name="add_gif_name" placeholder="Name"></th>
		<th colspan="2"><input type="text" name="add_gif_url" placeholder="URL"></th>
	<?php
		$gifs = get_gifs();
		foreach ($gifs as $element) {
			$name = $element['name'];
			$url = $element['url'];
			echo "<tr>";
			echo "<td>$name</td>";
			echo "<td>$url</td>";
			echo "<td><input type=\"checkbox\" name=\"delete_gif[]\" value=\"$url\"></td>";
			echo "</tr>";
		}?>
		<th colspan="3"><input type="submit" value="Update"></th>
	</tr>
</table>
</form>
</div>
<?php
	} else {
		disp_login();
		if (isset($_POST['username']) && isset($_POST['password'])) {
			$db = new PDO('sqlite:db.sqlite');
			$username = strtolower($_POST['username']);
			$password = $_POST['password'];
			$query = $db->prepare('SELECT password FROM auth WHERE username=:username');
			$query->bindValue(':username', $username, PDO::PARAM_STR);
			$query->execute();
			$hashed = $query->fetch(PDO::FETCH_COLUMN, 0);
			if (password_verify($password, $hashed)) {
				echo "Logging in...";
				$_SESSION['username'] = $username;
				header("Refresh:1");
			} else {
				echo "Incorrect password!";
			}
		}
	}
} else if (is_writeable('./')) {
	if (!empty($_POST) && initdb()) {
		$db = new PDO('sqlite:db.sqlite');
		add_admin($_POST['user'], $_POST['pass']);
		$query = $db->prepare('INSERT INTO config (name, value) VALUES (:name, :value)');
		$query->bindValue(':name', "rows", PDO::PARAM_STR);
		$query->bindValue(':value', "3", PDO::PARAM_STR);
		$query->execute();
		$query = $db->prepare('INSERT INTO config (name, value) VALUES (:name, :value)');
		$query->bindValue(':name', "columns", PDO::PARAM_STR);
		$query->bindValue(':value', "4", PDO::PARAM_STR);
		$query->execute();
		file_put_contents('.htaccess', "<Files \"db.sqlite\">\nDeny From All\n</Files>\n<Files \"sess*\">\nDeny From All\n</Files>");
		header("Refresh:1");
	}
disp_setup();
} else {
	echo "Working directory is not writeable, either chown it to the webserver user and group or allow write permissions to everyone (insecure!)";
}?>
</body>
</html>
