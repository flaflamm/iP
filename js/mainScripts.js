/***********************************
***   Ce fichier contient les    ***
*** scripts utilitaires généraux ***
***********************************/

//Fonction qui affiche la boîte de message
function showMessage($text,$type='caution',$time=5000) {
  $('#message').removeClass().addClass($type).text($text).slideDown('medium').delay($time).slideUp('medium');
}


//Fonctions à appeler suite au chargement de la page
$(function() {
  //Fonction qui montre/cache le menu
  $('#menuIcon').click(function() {
    $('#mainMenu').toggleClass('open');
  });



  //Fonction pour faire appraître/disparaître le contenu des .details lors d'un click sur .summary
  $('.summary').click(function() {
    $(this).parent().children(':not(.summary)').toggle();
  });


});
