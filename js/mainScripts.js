/***********************************
***   Ce fichier contient les    ***
*** scripts utilitaires généraux ***
***********************************/


//Fonctions à appeler suite au chargement de la page
$(function() {
  //Fonction pour faire appraître/disparaître le contenu des .details lors d'un click sur .summary
  $('.summary').click(function() {
    $(this).parent().children(':not(.summary)').toggle();
  });

});
