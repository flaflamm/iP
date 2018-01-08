<?php
//Vérifier que le nom de serveur est le bon
if($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '192.168.0.175') {
  //Fournir les bons noms d'utilisateur et mot de passe...
	$mysqli = new mysqli("localhost","USERNAME","PASSWORD", "profs_flaflamme2");
	if ($mysqli->connect_errno) { echo "Echec lors de la connexion à MySQL : (" . $mysqli->connect_errno . ") " . $mysqli->connect_error; }
	$mysqli->set_charset ( 'utf8' );
	date_default_timezone_set('America/Montreal');

	if(isset($_GET['debug'])&&$_GET['debug']==1) {$_SESSION['debug']=TRUE;}
	}
else {
	echo 'Mauvais serveur.<br>';
	if($_SESSION['debug']==TRUE) {
		echo '<pre>SERVER: ';
		print_r($_SERVER);
		echo '</pre>';
		}
	}
?>
