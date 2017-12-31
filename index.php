<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


/*****************************************
***   Cette fonction doit afficher    ***
***  le contenu principal de la page  ***
***  Elle sera appelée par le fichier ***
***    mainPage.php au bon endroit    ***
*****************************************/
function mainContent() {
    if(isset($_GET['dc']) && $_GET['dc']=='dc') { ?>
    <section class='centered message alert' id='dc' style='max-width: 500px; margin-top: 24px;'>Vous êtes maintenant déconnecté.</section>
    <script> $('#dc').delay(5000).slideUp('medium'); </script>
    <?php } ?>

    <section class='centered' style='max-width: 400px; margin-top: 24px;'>
      <h1>Connexion</h1>

      <form id='connect' action="php/verifyUser.php">
        <label for="username">Nom d'utilisateur:</label>
        <input type='text' id='username' name='username'/>
        <br>
        <label for="password">Mot de passe:</label>
        <input type='password' id='password' name='password'/>
        <br>
        <input type='submit' id='connexion' value='Connexion' class='large' style='margin: 2rem 0 1rem 0;'/>
      </form>
      <p class='messages hidden' id='usernameMsg'>Votre nom d'utilisateur est formé par la première lettre de votre prénom suivie d'un maximum de 7 lettres de votre nom de famille, le tout en minuscules, sans accents, espaces, trait d'union ou autres ponctuations. Par exemple, le nom d'utilisateur de Éloï-Jean Wu-O'Connor serait: «ewuoconn».</p>
      <p class='messages hidden' id='passwordMsg'>Le mot de passe est simplement votre matricule.</p>
      <p class='messages alert hidden' id='errorMsg'>Mauvaise combinaison de nom d'utilisateur et de mot de passe...</p>
    </section>

    <script>
        $('#username').focus(function() { $('#usernameMsg').slideDown('medium'); }).blur(function() { $('#usernameMsg').slideUp('medium'); });
        $('#password').focus(function() { $('#passwordMsg').slideDown('medium'); }).blur(function() { $('#passwordMsg').slideUp('medium'); });

        $("#connect").submit(function(e){
            e.preventDefault();
            $.post($(this).attr("action"), $(this).serialize(), function(data, textStatus, jqXHR) {
                alert(data);
                if (data=='cours') { window.location.href = "cours.php"; }
                else if (data=='dispo') { window.location.href = "dispo.php"; }
                else if (data=='error') { $('#errorMsg').slideDown('medium').delay(5000).slideUp('medium'); }
            });
        });


    </script>
<?php
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
