<?php
/************************************************
***      Ce fichier charge la structure       ***
***   commune des pages, l'entête, le menu,   ***
***     le pied de page qui sont communs à    ***
***            "toutes" les pages             ***
*************************************************/
require_once('php/utils.php');



function loadPage($jsFiles) {
  //Déconnecter l'utilisateur si le querystring contient dc=dc
  if(isset($_GET['dc']) && $_GET['dc']=='dc') {
    $_SESSION=array();
    session_destroy();
  }

  //Vérifier si l'utilisateur est connecté, s'il ne l'est pas il faut le rediriger sur la page index.php
  if(!(isset($_SESSION['prof']) || isset($_SESSION['matricule']))) {
      $fileName  = substrBetween($_SERVER['REQUEST_URI'],'/','?');
      if($fileName!='index.php') { header('Location: index.php'); exit; }
  }//*/
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
    <?php $jsFiles[]='js/mainScripts.js'; foreach($jsFiles as $file) {echo "<script src='$file'></script>".PHP_EOL;} ?>
  </head>

  <body>
    <?php loadHeader(); ?>
    <div id='message'></div>
    <main>
      <?php mainContent(); ?>
    </main>
  </body>
  <?php
}


function loadHeader() {
  $user=''; //Il faudra déterminer le type d'utilisateur (prof, étudiant ou non connecté)

  switch ($user) { //Afficher un menu différent si c'est un prof, un étudiant (?) ou si on n'est pas connecté.
    case 'prof':
      ?><header class='menu'>
          <nav>
            <a href='index.php'><img src='icons/titre-dark.svg' alt='infoPhysique'/></a>
            <label for='showMenu'><i class="icon">menu</i></label>
            <input type='checkbox' id='showMenu' name='showMenu'/>
            <div>
              <a href='cours.php?id=1'>Mécanique</a>
              <a href='cours.php?id=2'>Électricité & magnétisme</a>
              <a href='dispo.php' class='sub'>Disponibilités</a>
              <a href='index.php?dc=dc'>Se déconnecter</a>
            </div>
            <span class='greetings'><span class='small light'>Bonjour</span><span><?=$_SESSION['name'];?></span></span>
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
