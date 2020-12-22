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
  //Afficher un message si la personne vient de se déconnecter.
  if(isset($_GET['dc']) && $_GET['dc']=='dc') { ?>
    <script> showMessage('Vous êtes maintenant déconnecté.'); </script>
  <?php }

  global $user;

  if($user->getType()=='p') { //La personne est connectée (et c'est un professeur)
    //Affichage de la liste de cours
    echo '<section><h1>Cours</h1>';

    $listeCours = $user->getCours('tous');

    foreach($listeCours as $cours) {
      if(isset($sessionActuelle)) {
        if($cours->getSession()!=$sessionActuelle) {//Changement de session, afficher la session
          if($sessionActuelle==$premiereSession) {//Premier changement de session afficher le bouton
            echo '<div class="details"><h3 class="summary">Sessions précédentes&nbsp;<i class="icon small">add_box</i></h3><div class="definition">'.PHP_EOL;
          }
          $sessionActuelle=$cours->getSession();
          echo "<span class='definitionTerm'>$sessionActuelle :</span><br>".PHP_EOL;
        }
      }
      else { $sessionActuelle=$cours->getSession(); $premiereSession=$sessionActuelle; } //Premier élément, fixer les valeurs de départ...

      echo '<a href="cours.php?id='.$cours->getId().'">'.$cours->getTitre().'</a><br>'.PHP_EOL;

    }
    if($sessionActuelle!=$premiereSession) {echo '</div></div>'.PHP_EOL;}
    echo '</section>'.PHP_EOL;

    //Affichage de la liste de rendez-vous si le prof utilise le module de rendez-vous
    if($user->getRV()) {
      $listeRv = array(array('d'=>'2018-01-02','h'=>12,'m'=>'00','nom'=>'Jane Austin','matricule'=>1234567,'duree'=>10,'n'=>1),
                      array('d'=>'2018-01-02','h'=>12,'m'=>10,'nom'=>'Georges Orwel','matricule'=>1234567,'duree'=>20,'n'=>2),
                      array('d'=>'2018-01-02','h'=>13,'m'=>30,'nom'=>'Martin Luther King','matricule'=>1234567,'duree'=>10,'n'=>1),
                      array('d'=>'2018-01-03','h'=>8,'m'=>50,'nom'=>'Stephen King','matricule'=>1234567,'duree'=>20,'n'=>2),
                      array('d'=>'2018-01-04','h'=>9,'m'=>10,'nom'=>'Paul McCarthney','matricule'=>1234567,'duree'=>10,'n'=>1),
                      array('d'=>'2018-01-04','h'=>14,'m'=>30,'nom'=>'Fred Lépine','matricule'=>1234567,'duree'=>10,'n'=>1)); //Il faudra aller chercher ces valeurs (rv/getRV.php)...
      echo '<section><h1>Rendez-vous</h1>';
      if(is_null($listeRv)) {echo '<h3>Aucun rendez-vous dans les prochains jours.</h3>';}
      else {
        $today=date('Y-m-d');
        if($listeRv[0]['d']==$today) { echo '<h3>Aujourd\'hui</h3>'; }
        foreach($listeRv as $rv) {
          if($today && $rv['d']!=$today) {
            echo "<h3>$rv[d]</h3>";
            $today = $rv['d'];
          }
          echo "$rv[h]h$rv[m]:&nbsp;$rv[nom] <span class='small light'>($rv[duree]&nbsp;minutes)</span><br>";
        }
      }
      echo '</section>';
    }

    /*echo '<section>';
    varDump($listeCours);
    echo '</section><section>';
    varDump($listeRv);
    echo '</section>';//*/
  }
  else {  //Pas connecté, envoyer la page de connexion.
    ?>
    <section class='centered' style='max-width: 400px; margin-top: 24px;'>
      <h1>Connexion</h1>

      <form id='connect' action="php/login.php">
        <label for="username">Nom d'utilisateur:</label>
        <input type='text' id='username' name='username'/>
        <br>
        <label for="password">Mot de passe:</label>
        <input type='password' id='password' name='password'/>
        <br>
        <label for="keepMeLogged">Rester connecté?</label>
        <input type='checkbox' id='keepMeLogged' name='keepMeLogged' value='true'/>
        <br>
        <input type='submit' id='connexion' value='Connexion' class='large' style='margin: 2rem 0 1rem 0;'/>
      </form>
      <p class='messages hidden' id='usernameMsg'>Votre nom d'utilisateur est formé par la première lettre de votre prénom suivie d'un maximum de 7 lettres de votre nom de famille, le tout en minuscules, sans accents, espaces, trait d'union ou autres ponctuations. Par exemple, le nom d'utilisateur de Éloï-Jean Wu-O'Connor serait: «ewuoconn».</p>
      <p class='messages hidden' id='passwordMsg'>Le mot de passe est simplement votre matricule.</p>
      <p class='messages hidden' id='keepLoggedMsg'>Ne pas utiliser cette fonction sur un ordinateur public.</p>
    </section>



    <script>
        $('#username').focus(function() { $('#usernameMsg').slideDown('medium'); }).blur(function() { $('#usernameMsg').slideUp('medium'); });
        $('#password').focus(function() { $('#passwordMsg').slideDown('medium'); }).blur(function() { $('#passwordMsg').slideUp('medium'); });
        $('#keepMeLogged').focus(function() { $('#keepLoggedMsg').slideDown('medium'); }).blur(function() { $('#keepLoggedMsg').slideUp('medium'); });

        $("#connect").submit(function(e){
            e.preventDefault();
            $.post($(this).attr("action"), $(this).serialize(), function(data, textStatus, jqXHR) {
                //alert(data);
                if (data=='prof') { window.location = window.location.href.split("?")[0]; }
                else if (data=='étudiant') { window.location.href = "index.php"; }
                else { showMessage(data,'alert'); }
            });
        });


    </script>
<?php
  }
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
