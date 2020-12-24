<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/************************************************
***    Ce fichier contient la structure de    ***
***   base de tous les fichiers qui peuvent   ***
***     être appelés par les utilisateur:     ***
***   Le contenu de la page à afficher sera   ***
***       dans la fonction mainContent()      ***
***   Les fichiers javascripts externes sont  ***
***      listés dans le tableau $jsFiles      ***
***   Le fichier mainPage.php complètera la   ***
***        page en chargeant l'entête,        ***
***        le menu, le pied de page...        ***
*************************************************/


/*****************************************
***   Cette fonction doit afficher    ***
***  le contenu principal de la page  ***
***  Elle sera appelée par le fichier ***
***    mainPage.php au bon endroit    ***
*****************************************/
function mainContent() {
  global $user;

  $cours = $user->getCours($_GET['id'])
  or exit("Vous n'avez pas accès à ce cours.");

  if($user->getType()=='p') {
    echo '<section class="title"><h2>'.$cours->getTitre().' <span class="smaller light">gr.: '.implode(', ',$cours->getGroupes()).'</span></h2>'.PHP_EOL;
    buttons([ ['icon'=>'list','text'=>"Liste d'étudiants",'address'=>"liste.php"], ['icon'=>'edit','text'=>"Modifier"]]);
    echo '</section>'.PHP_EOL;
  }
  else {echo '<section class="title"><h2>'.$cours->getTitre().'</h2></section>';}

  foreach($cours->getCategories() as $category) {
    echo '<section><h3>'.$category->nom().'&nbsp;&nbsp;<a class="icon" onclick="showCategoryParams()">settings</a></h3>'.PHP_EOL;
    formulaireParametre($category);
    echo '</section>';
  }

  varDump($cours,'$cours');
}


function formulaireParametre($cat) {
  //Affiche le formulaire des paramètres d'une catégorie (pour une nouvelle ou pour une catégorie existante)
}

/***************************************
 *** $jsFiles doit contenir la liste ***
 ***      des scripts javascript     ***
 ***    à charger pour cette page    ***
 ***************************************/
$jsFiles=array('js/cours.js');



/***********************************
 ***       Ne pas modifier       ***
 *** appelle le fichier/fonction ***
 ***    qui génèrera la page     ***
 ***********************************/
require_once('php/mainPage.php');
loadPage($jsFiles);
?>
