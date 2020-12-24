<?php
/************************************************
***      Ce fichier charge la structure       ***
***   commune des pages, l'entête, le menu,   ***
***     le pied de page qui sont communs à    ***
***            "toutes" les pages             ***
*************************************************/
require_once('php/utils.php');
require_once('php/sqlconfig.php');
//Déconnecter l'utilisateur si le querystring contient dc=dc
if(isset($_GET['dc']) && $_GET['dc']=='dc') {
  $_SESSION=array();
  session_destroy();
  if(isset($_COOKIE['remember'])) {
    list($selector, $authenticator) = explode(':', $_COOKIE['remember']);
    mysqliQuery('DELETE FROM `AuthTokens` WHERE selector = ? AND `hashedValidator` = ?','ss',$selector, hash('sha256', base64_decode($authenticator))); //Efface l'entrée dans la base de donnée
    mysqliQuery('DELETE FROM `AuthTokens` WHERE `expires` < NOW()'); //On en profite pour effacer les entrées passé date...

    unset($_COOKIE['remember']);
    // empty value and expiration one hour before
    setcookie('remember', '', time() - 3600);
  }
}

//Créer l'objet user
require_once('classes/classeUtilisateur.php');
$user = chargeUtilisateur();

//Charge la page principale
function loadPage($jsFiles) {
  global $user;

  //Si l'utilisateur n'est pas connecté il faut le rediriger sur la page index.php
  if(!$user->getType()) {
    $fileName  = substrBetween($_SERVER['REQUEST_URI'],'/','?');
    if($fileName!='index.php') { header('Location: index.php'); exit; }
  }
  ?>
  <!DOCTYPE html>
  <html  lang="fr">

  <head>
    <meta charset="utf-8">
  	<meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
  	<title>infoPhysique</title>

  	<link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700&amp;subset=greek" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <link href="css/base.css?v=<?=time();/**** Enlever cette partie sur la version officielle ****/?>" rel="stylesheet">
    <link href="css/mainStyle.css?v=<?=time();/**** Enlever cette partie sur la version officielle ****/?>" rel="stylesheet">

    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/manifest.json">
    <link rel="mask-icon" href="/safari-pinned-tab.svg" color="#0075ed">
    <meta name="apple-mobile-web-app-title" content="infoPhysique">
    <meta name="application-name" content="infoPhysique">
    <meta name="theme-color" content="#444444">

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <?php $jsFiles[]='js/mainScripts.js'; foreach($jsFiles as $file) {echo "<script src='$file?v=".time()."'></script>".PHP_EOL;} ?>
  </head>

  <body>
    <?php loadHeader(); ?>
    <div id='message'></div>
    <main>
      <?php
      mainContent();
      if(error_reporting()) {
        echo '<section style="width:100%; display: flex; flex-flow: row wrap;"> Session:<br>';
        varDump($_SESSION);
        //echo '</section><section>';
        varDump($user);
        echo '</section>';
      }
       ?>
    </main>
  </body>
  <?php
}


function loadHeader() {
  global $user;
  //Afficher un menu différent si c'est un prof, un étudiant ou si on n'est pas connecté.
  switch ($user->getType()) {
    case 'p':
      $listeCours = $user->getCours('actuelle');
      ?><header class='menu'>
          <nav>
            <a href='index.php'><img src='icons/titre-dark.svg' alt='infoPhysique'/></a>
            <i class="icon" id='menuIcon'>menu</i>
            <div id='mainMenu'>
              <?php foreach($listeCours as $cours) {echo '<a href="cours.php?id='.$cours->getId().'">'.$cours->getTitre().'</a>'.PHP_EOL;}
              if($user->getRV()) {echo '<a href="dispo.php">Disponibilités</a>';} ?>
              <a href='perso.php'>Paramètre personnels</a>
              <a href='index.php?dc=dc'>Se déconnecter</a>
            </div>
            <span class='greetings'><span class='small light'>Bonjour</span><span><?=$user->getNom('p n');?></span></span>
          </nav>
      </header><?php
      break;
    case 'e':
      $listeCours = $user->getCours('actuelle');
      ?><header class='menu'>
          <nav>
            <a href='cours.php'><img src='icons/titre-dark.svg' alt='infoPhysique'/></a>
            <i class="icon" id='menuIcon'>menu</i>
            <div id='mainMenu'>
              <?php foreach($listeCours as $cours) {
                echo '<a href="cours.php?id='.$cours->getId().'">'.$cours->getTitre().'</a>'.PHP_EOL;
                $listeProfs = $cours->getProfs();
                foreach($listeProfs as $prof) {
                  if($prof->getRV()) {echo '<a href="dispo.php?id='.$prof->getId().'" class="sub">Dispo: '.$prof->getNom('p n').'</a>'.PHP_EOL; }
                }
              } ?>
              <a href='index.php?dc=dc'>Se déconnecter</a>
            </div>
            <span class='greetings'><span class='small light'>Bonjour</span><span><?=$user->getNom('p n');?></span></span>
          </nav>
      </header><?php
      break;

    default: //Pas connecté
      ?><header class='noMenu'>
          <h1>Bienvenue sur&nbsp; <img src='icons/titre-dark.svg' alt='infoPhysique'/></h1>
          <p>Ce site web s'adresse aux étudiants de physique du Cégep de Sainte-Foy.<br>Conçu et réalisé par <span style='font-style: italic;'>François Laflamme</span>, professeur au département de physique du Cégep de Sainte-Foy.<br></p>
        </header><?php
      break;
  }
}


?>
