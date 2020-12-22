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
    //Rechercher s'il existe un étudiant avec ce matricule (sinon retourner FALSE)
    $results = mysqliQuery('SELECT `prenom`, `nom` FROM `Etudiants` WHERE `matricule`=?','s',$matricule);

    if(count($results)==1) {
      $etu = $results[0];

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
    }//*/
  }
  return FALSE;
}




function connexionProf($username,$password) {
  //Vérifie si les nom d'utilisateur/mot de passe correspondent à un prof (si oui retourne à page où rediriger le prof et si non retourne FALSE).
  //Doit aussi fixer des valeurs de $_SESSION=résultat de la query sur la table prof (moins le hash du password)...

  $hash = encryptPassword($password);

  //Recherche un professeur(s) utilisant ce nom d'utilisateur
  //global $mysqli;
  $results = mysqliQuery('SELECT * FROM `Profs` WHERE `username`=?','s',$username);

  foreach($results as $prof) {
    if($prof['hash'] != '') { //Vérifier si le mot de passe correspond (nouvelle version)
      if(password_verify($password , $prof['hash'])) {
        unset($prof['password']);
        unset($prof['hash']);
        $_SESSION = $prof;
        return TRUE;
        }
      }
    elseif($prof['password']==$hash) {
      /*** CETTE SECTION SERA À ENLEVER LORSQUE TOUS LES MOTS DE PASSE AURONT UN NOUVEAU HASH ***/
      //Cela correspond, enregistrer les informations de session et quitter la fonction

      //Générer un nouveau $hash pour une meilleure sécurité
      mysqliQuery('UPDATE `Profs` SET `hash` = ? WHERE `index` = ?','si',password_hash($password,PASSWORD_DEFAULT),$prof['index']);

      unset($prof['password']);
      unset($prof['hash']);
      $_SESSION = $prof;
      return TRUE;
    }
  }//*/
  return FALSE;
}



function encryptPassword($password) {
//retourne la version encryptée du mot de passe
	$salt = array('sl4!8e','bv,n7fk','.r;3d');
	return sha1($salt[0].substr($password,0,2).$salt[1].substr($password,2).$salt[2]);
}//*/


function setKeepMeLoggedCookie($userType, $userId) {
  $selector = base64_encode(random_bytes(9));
  $authenticator = random_bytes(33);
  $limit = time() + 30*86400;


  setcookie(
      'remember',
       $selector.':'.base64_encode($authenticator),
       $limit,
       '/',
       $_SERVER['HTTP_HOST'],
       false, // TLS-only
       true  // http-only
  );

  mysqliQuery('INSERT INTO AuthTokens (selector, hashedValidator, usertype, userid, expires) VALUES (?, ?, ?, ?, ?)','sssis',$selector,hash('sha256', $authenticator), $userType, $userId, date('Y-m-d H:i:s', $limit));
}


if(isset($_POST['username'])&&isset($_POST['password'])) {
  require_once('sqlconfig.php');

	if(connexionEtudiant($_POST['username'],$_POST['password'])) { //Vérifier si étudiant
    if(isset($_POST['keepMeLogged'])&&$_POST['keepMeLogged']=='true') {setKeepMeLoggedCookie('e',$_SESSION['matricule']);}
    echo 'étudiant';
    }
	elseif(connexionProf($_POST['username'],$_POST['password'])) { //Vérifier si prof
    if(isset($_POST['keepMeLogged'])&&$_POST['keepMeLogged']=='true') {setKeepMeLoggedCookie('p',$_SESSION['index']);}
    echo 'prof';
    }
	else{ echo 'Mauvais nom d\'utilisateur ou mot de passe.'; }//*/
	}
else {echo 'Error.';}
?>
