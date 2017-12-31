<?php
session_start();
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
}

/***************************************
 *** $jsFiles doit contenir la liste ***
 ***      des scripts javascript     ***
 ***    à charger pour cette page    ***
 ***************************************/
$jsFiles=array();



/***********************************
 ***       Ne pas modifier       ***
 *** appelle le fichier/fonction ***
 ***    qui génèrera la page     ***
 ***********************************/
require_once('php/mainPage.php');
loadPage($jsFiles);
?>
