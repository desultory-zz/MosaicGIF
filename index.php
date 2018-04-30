<?php
include 'functions.php';
ini_set('display_errors', 1);
ini_set('session.save_path', getcwd());
error_reporting(-1);
$rows = get_config_var('rows');
$columns = get_config_var('columns');
$total = $rows * $columns;
?>
<!DOCTYPE html>
<html lang="en">
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<meta charset="utf-8">
<head>
	<title>Sit Back</title>
</head>
<style>
html, body{
  margin: 0;
  padding: 0;
  height: 100%;
  width: 100%;
  overflow: hidden;
  -webkit-column-gap: 0;
  -moz-column-gap: 0;
  column-gap: 0;
  display: inline-block;
  border-collapse: collapse;
}
body{
  position: absolute;
  background-color:#000;
  background-size:cover;
  cursor: none;
}
img{
  display: inline-block;
  height: <?php echo 100 / $rows?>%;
  width: <?php echo 100 / $columns?>%;
  margin: 0;
  vertical-align: top;
}
</style>
<body>
<?php
for ($i = 1; $i <= $total; $i ++) {
	echo '<img src="' . get_random_gif() . '">';
	if ($i % $columns == 0) {
		echo '<br>' . "\n";
	}
}
?>
</body>
</html>
