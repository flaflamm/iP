<?php
session_start();
/*********************************************
*** Vérifie les informations de connexion, ***
***  retourne "étudiant" ou "prof" si ok,  ***
***      ou un message d'erreur sinon      ***
*********************************************/


function connexionEtudiant($username,$matricule) {
  //Vérifie si les nom d'utilisateur/mot de passe correspondent à un étudiant (retourne TRUE si oui et FALSE si non).
  //Doit aussi fixer des valeurs de $_SESSION[matricule], $_SESSION[prenom] et $_SESSION[nom]
  if(preg_match("/^\d{7,8}$/",$matricule) && preg_match("/^[a-zA-Z]{2,8}$/", $username) ) {
    global $mysqli;

    //Rechercher s'il existe un étudiant avec ce matricule (sinon retourner FALSE)
    $results = $mysqli->query('SELECT `prenom`, `nom` FROM `Etudiants` WHERE `matricule`="'.$matricule.'"');
  	if($etu = $results->fetch_assoc()) {
      $results->close();

      //Vérifier que le nom de l'étudiant (jusqu'à 8 lettres en minuscules, sans espaces et sans accents) correxpond à son username.
      $accent = array('é','è','ê','ë','à','â','ä','î','ï','ì','ô','ò','ö','ù','û','ü','ỳ','ÿ','ŷ','ç','É','È','Ê','Ë','À','Â','Ä','Î','Ï','Ì','Ô','Ò','Ö','Ù','Û','Ü','Ŷ','Ỳ','Ÿ','Ç','-',' ',"'",'.');
  	  $saccent= array('e','e','e','e','a','a','a','i','i','i','o','o','o','u','u','u','y','y','y','c','E','E','E','E','A','A','A','I','I','I','O','O','O','U','U','U','Y','Y','Y','C','' ,'' ,'' ,'' );
      if(strtolower($username) == strtolower(substr(str_replace($accent,$saccent,$etu['prenom']),0,1).substr(str_replace($accent,$saccent,$etu['nom']),0,7))) {
        //Les informations de connexion sont valides, les stocker dans $_SESSION
        $_SESSION['matricule']=$matricule;
        $_SESSION['nom']=$etu['nom'];
        $_SESSION['prenom']=$etu['prenom'];
        return TRUE;
      }
    }
  }
  return FALSE;
}




function connexionProf($username,$hash) {
  //Vérifie si les nom d'utilisateur/mot de passe correspondent à un prof (si oui retourne à page où rediriger le prof et si non retourne FALSE).
  //Doit aussi fixer des valeurs de $_SESSION=résultat de la query sur la table prof (moins le hash du password)...

  //Recherche un professeur(s) utilisant ce nom d'utilisateur
  global $mysqli;
  $results = $mysqli->query('SELECT * FROM `Profs` WHERE `username`="'.$username.'"');
  while($prof = $results->fetch_assoc()) {
    //Vérifier si le mot de passe correspond:
    if($prof['password']==$hash) {
      //Cela correspond, enregistrer les informations de session et quitter la fonction
      unset($prof['password']);
      $_SESSION = $prof;
      return TRUE;
    }
  }
  return FALSE;
}



function encryptPassword($password) {
//retourne la version encryptée du mot de passe
	$salt = array('sl4!8e','bv,n7fk','.r;3d');
	return sha1($salt[0].substr($password,0,2).$salt[1].substr($password,2).$salt[2]);
}//*/





if(isset($_POST['username'])&&isset($_POST['password'])) {
  require_once('sqlconfig.php');

	if(connexionEtudiant($_POST['username'],$_POST['password'])) {echo 'étudiant';} //Vérifier si étudiant
	elseif(connexionProf($_POST['username'],encryptPassword($_POST['password']))) {echo 'prof';} //Vérifier si prof
	else{ echo 'Mauvais nom d\'utilisateur ou mot de passe.'; }//*/
	}
else {echo 'Error.';}
?>
