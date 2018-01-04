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
    <script> $('#message').removeClass().addClass('caution').text('Vous êtes maintenant déconnecté.').show().delay(5000).slideUp('medium'); </script>
  <?php }


  if(isset($_SESSION['prof'])) { //La personne est connectée (et c'est un professeur)
    $listeCours = array(array('id'=>1,'titre'=>'Mécanique','session'=>'H','annee'=>2018),
                        array('id'=>2,'titre'=>'Électricité & Magnétisme','session'=>'H','annee'=>2018),
                        array('id'=>3,'titre'=>'Mécanique','session'=>'A','annee'=>2017),
                        array('id'=>4,'titre'=>'Ondes & physique moderne','session'=>'H','annee'=>2017),
                        array('id'=>5,'titre'=>'Phénomènes physiques','session'=>'H','annee'=>2017),
                        array('id'=>6,'titre'=>'Mécanique','session'=>'A','annee'=>2016)); //Il faudra aller chercher ces valeurs...
    $listeRv = array(array('d'=>'2018-01-02','h'=>12,'m'=>'00','nom'=>'Jane Austin','matricule'=>1234567,'duree'=>10,'n'=>1),
                    array('d'=>'2018-01-02','h'=>12,'m'=>10,'nom'=>'Georges Orwel','matricule'=>1234567,'duree'=>20,'n'=>2),
                    array('d'=>'2018-01-02','h'=>13,'m'=>30,'nom'=>'Martin Luther King','matricule'=>1234567,'duree'=>10,'n'=>1),
                    array('d'=>'2018-01-03','h'=>8,'m'=>50,'nom'=>'Stephen King','matricule'=>1234567,'duree'=>20,'n'=>2),
                    array('d'=>'2018-01-04','h'=>9,'m'=>10,'nom'=>'Paul McCarthney','matricule'=>1234567,'duree'=>10,'n'=>1),
                    array('d'=>'2018-01-04','h'=>14,'m'=>30,'nom'=>'Fred Lépine','matricule'=>1234567,'duree'=>10,'n'=>1)); //Il faudra aller chercher ces valeurs (rv/getRV.php)...

    //Affichage de la liste de cours
    echo '<section><h1>Cours</h1>';
    $sessionActuelle=$listeCours[0]['session'].'-'.$listeCours[0]['annee'];
    foreach($listeCours as $cours) {
      if($sessionActuelle!=$cours['session'].'-'.$cours['annee']) {
        if($sessionActuelle==$listeCours[0]['session'].'-'.$listeCours[0]['annee']) {
          echo '<div class="details"><h3 class="summary">Sessions précédentes&nbsp;<i class="icon small">add_box</i></h3><div class="definition">';
        }//*/
        $sessionActuelle=$cours['session'].'-'.$cours['annee'];
        echo "<span class='definitionTerm'>$sessionActuelle :</span><br>";
      }
      echo "<a href='cours.php?id=$cours[id]'>$cours[titre]</a><br>";
    }
    if($sessionActuelle!=$listeCours[0]['session'].'-'.$listeCours[0]['annee']) {echo '</div></div>';}
    echo '</section>';

    //Affichage de la liste de rendez-vous
    if(true) {//Vérifier si le prof utilise le module de rendez-vous
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
    <section class='title'><h1>Titre principal de page<br><span class='smaller lighter'>Sous-titre</span></h1>
      Éléments secondaires...<br>
      Caractéristiques de la page...
    </section>
    <section>
      Test de tableau...
      <table><thead><tr><th>Nom</th><th>Note</th></tr></thead>
        <tbody><tr><td>Georges Lucas</td><td>9/10</td></tr>
          <tr><td>Stephen Spieldberg</td><td>8/10</td></tr>
          <tr><td>Quentin Tarantino</td><td>9/10</td></tr>
          <tr><td>Martin Scorcese</td><td>9/10</td></tr>
          <tr><td>Denis Villeneuve</td><td>10/10</td></tr></tbody>
        <tfoot><tr><td colspan=2><a>Ajout</a></td></tr></tfoot>
      </table>
    </section>

    <section>
      Test de formulaire...
      <div class='inlineInput'><label>Label</label><input type='date'></div>
      <div class='inlineInput'><span class='accent'>Pre:</span><input type='number' min="1" max="5"></div>
      <div class='inlineInput'><select>
  <option value="volvo">Volvo</option>
  <option value="saab">Saab</option>
  <option value="mercedes">Mercedes</option>
  <option value="audi">Audi</option>
</select><span class='secondary'>Post</span></div>
      <div class='inlineInput'><label>Label</label><span class='accent'>Pre:</span><input type='text' placeholder="Bla blabla..."><span class='secondary'>Post</span></div>
      <fieldset>
        <legend>Personalia:</legend>
        <input type="checkbox" id="coding" name="interest" value="coding">
      <label for="coding">Développement</label>
  <input type="checkbox" id="music" name="interest" value="music">
      <label for="music">Musique</label>
  <input type="checkbox" id="art" name="interest" value="art">
      <label for="art">Art</label><br>
      <input type="radio" name="gender" value="male" checked> Male<br>
  <input type="radio" name="gender" value="female"> Female<br>
  <input type="radio" name="gender" value="other"> Other
</fieldset>
    </section>


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
