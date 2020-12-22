<?php
//Fonction à appeler pour faire des requêtes à la base de donnée
	function mysqliQuery($query,$codes='',$v1=NULL,$v2=NULL,$v3=NULL,$v4=NULL,$v5=NULL,$v6=NULL,$v7=NULL,$v8=NULL,$v9=NULL,$v10=NULL,$v11=NULL,$v12=NULL,$v13=NULL,$v14=NULL,$v15=NULL) {
	//codes : i - Integer, d - Double, s - String, b - Blob
	global $mysqli;
	$stmt = $mysqli->prepare($query);
	switch(strlen($codes)) {
		case  0: break;
		case  1: $stmt->bind_param($codes, $v1); break;
		case  2: $stmt->bind_param($codes, $v1, $v2); break;
		case  3: $stmt->bind_param($codes, $v1, $v2, $v3); break;
		case  4: $stmt->bind_param($codes, $v1, $v2, $v3, $v4); break;
		case  5: $stmt->bind_param($codes, $v1, $v2, $v3, $v4, $v5); break;
		case  6: $stmt->bind_param($codes, $v1, $v2, $v3, $v4, $v5, $v6); break;
		case  7: $stmt->bind_param($codes, $v1, $v2, $v3, $v4, $v5, $v6, $v7); break;
		case  8: $stmt->bind_param($codes, $v1, $v2, $v3, $v4, $v5, $v6, $v7, $v8); break;
		case  9: $stmt->bind_param($codes, $v1, $v2, $v3, $v4, $v5, $v6, $v7, $v8, $v9); break;
		case 10: $stmt->bind_param($codes, $v1, $v2, $v3, $v4, $v5, $v6, $v7, $v8, $v9, $v10); break;
		case 11: $stmt->bind_param($codes, $v1, $v2, $v3, $v4, $v5, $v6, $v7, $v8, $v9, $v10, $v11); break;
		case 12: $stmt->bind_param($codes, $v1, $v2, $v3, $v4, $v5, $v6, $v7, $v8, $v9, $v10, $v11, $v12); break;
		case 13: $stmt->bind_param($codes, $v1, $v2, $v3, $v4, $v5, $v6, $v7, $v8, $v9, $v10, $v11, $v12, $v13); break;
		case 14: $stmt->bind_param($codes, $v1, $v2, $v3, $v4, $v5, $v6, $v7, $v8, $v9, $v10, $v11, $v12, $v13, $v14); break;
		case 15: $stmt->bind_param($codes, $v1, $v2, $v3, $v4, $v5, $v6, $v7, $v8, $v9, $v10, $v11, $v12, $v13, $v14, $v15); break;
		default: return;
	}
	$stmt->execute();

	if(substr( $query, 0, 6 ) === "SELECT") { //SELECT query, return results array...
		$results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
	}
	else { //others query, return array ['Rows matched' => , 'Changed' => , 'Warnings' =>]
		preg_match_all('/(\S[^:]+): (\d+)/', $mysqli->info, $matches);
		$results = array_combine($matches[1], $matches[2]);
		if(substr( $query, 0, 6 ) === "INSERT") {$results['InsertId']=$mysqli->insert_id;} //Pour un INSERT, ajouter insertId
	}

	$stmt->close();
	return $results;
}




//Vérifier que le nom de serveur est le bon
if($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '192.168.0.175') {
  //Fournir les bons noms d'utilisateur et mot de passe...
	$mysqli = new mysqli("localhost","flaflamme","ie1$3v1L", "profs_flaflamme2");
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
