<?php
require_once(dirname(dirname(__FILE__)).'/php/sqlconfig.php');
require_once('classeCours.php');

abstract class utilisateur {
	protected $id;
	protected $nom;
	protected $prenom;
	protected $type; //[p]rofesseur, [e]tudiant [false]... il faudra ajouter quelquechose pour les superutilisateurs...
	protected $cours;

	function getId() {return $this->id;}

	function getType() {return $this->type;}

	function getNom($format) {
		//$format est soit: 'n', 'p', 'p n', 'n, p' (par défaut)
		if(is_null($this->nom)) { $this->loadNom(); }
		switch($format) {
			case 'n': return $this->nom;
			case 'p': return $this->prenom;
			case 'p n': return $this->prenom.' '.$this->nom;
			default : return $this->nom.', '.$this->prenom;
		}
	}

	abstract protected function loadNom();
}

class etudiant extends utilisateur {
  function __construct($info,$cours=NULL) {
		//info peut contenir uniquement le matricule (chaine de caractère ou entier...)
		//ou un tableau qui contiendra son matricule, son nom et son prénom.
		if(is_array($info)) {
			if(isset($info['matricule'])) {$this->id=$info['matricule'];}
			if(isset($info['nom'])) {$this->nom=$info['nom'];}
			if(isset($info['prenom'])) {$this->prenom=$info['prenom'];}
		}
		else {$this->id=$info;}
    $this->type='e';
    if(!is_null($cours)) {$this->cours=$cours;}
  }

  function getMatricule() {return $this->id;}

	function getCours($type) {
		if(is_null($this->cours)) { $this->loadCours(); }

		if(is_numeric($type)) {return $this->cours[$type];} //Retourne le cours demandé
		return $this->cours; //PAR DÉFAUT: Retourne tous les Cours
	}

	protected function loadNom() {
    global $mysqli;
    $results = $mysqli->query('SELECT `nom`, `prenom` FROM `Etudiants` WHERE `matricule`='.$this->id);
    $result = $results->fetch_assoc();
		$this->nom = $result['nom'];
		$this->prenom = $result['prenom'];
  }

	protected function loadCours() {
		global $mysqli;
		$results = $mysqli->query('SELECT `Cours`.`index`, `Cours`.`nom`, `Cours`.`session`, `Liste-Cours-Etudiants`.`groupe` FROM `Cours`, `Liste-Cours-Etudiants` WHERE `Liste-Cours-Etudiants`.`cours`=`Cours`.`index` AND `Liste-Cours-Etudiants`.`matricule`='.$this->id.'  ORDER BY `Cours`.`session` DESC');
		while($result = $results->fetch_assoc()) {
			//Pour ne conserver que les cours de la plus récente session.
			if(!isset($sessionActuelle)) {$sessionActuelle=$result['session'];}
			elseif($result['session']!=$sessionActuelle) {break;}

			//Pour enregistrer le cours.
			$cours[$result['index']] = new cours($result);
		}
		$this->cours=$cours;
	}

}



class prof extends utilisateur {
	private $courriel;
	private $rv;
	private $superuser=FALSE;

	function __construct($info,$cours=NULL) {
		//info peut contenir uniquement son index (chaine de caractère ou entier...)
		//ou un tableau qui contiendra son index, nom, prénom, courriel, rv et superuser.
		if(is_array($info)) {
			if(isset($info['index'])) {$this->id=$info['index'];}
			if(isset($info['nom'])) {$this->nom=$info['nom'];}
			if(isset($info['prenom'])) {$this->prenom=$info['prenom'];}
			if(isset($info['courriel'])) {$this->courriel=$info['courriel'];}
			if(isset($info['rv'])) {$this->rv=$info['rv'];}
			if(isset($info['superuser']) && $info['superuser']) {$this->superuser=TRUE;}
		}
		else { $this->id=$info; }

		$this->type='p';

    if(!is_null($cours)) {$this->cours=$cours;}
  }

	function getRV() {return $this->rv;}

	function getCourriel() {return $this->courriel;}

	function getCours($type) {
		if(is_null($this->cours)) { $this->loadCours(); }

		if(is_numeric($type)) {return $this->cours[$type];} //Retourne le cours demandé
		if($type=='tous') {return $this->cours;} //Retourne tous les Cours
		//PAR DÉFAUT: Retourne uniquement les cours de la session la plus récente.
		$listeCours = array();
		foreach($this->cours as $id => $cours) {
			if(isset($sessionActuelle)) { if($sessionActuelle!=$cours->getSession()) {break;}}
			else {$sessionActuelle = $cours->getSession(); }
			$listeCours[$id]=$cours;
		}
		return $listeCours;
	}

	protected function loadNom() {
    global $mysqli;
    $results = $mysqli->query('SELECT `nom`, `prenom` FROM `Profs` WHERE `index`='.$this->id);
    $result = $results->fetch_assoc();
		$this->nom = $result['nom'];
		$this->prenom = $result['prenom'];
  }

	protected function loadCours() {
		global $mysqli;
		$results = $mysqli->query('SELECT `Cours`.`index`, `Cours`.`nom`, `Liste-Cours-Profs`.`groupes`, `Sessions`.`session`, `Sessions`.`annee` FROM `Cours`, `Sessions`, `Liste-Cours-Profs` WHERE `Liste-Cours-Profs`.`cours`=`Cours`.`index` AND `Cours`.`session`=`Sessions`.`index` AND `Liste-Cours-Profs`.`prof`='.$this->id.'  ORDER BY `Sessions`.`annee` DESC, `Sessions`.`session` ASC');
		while($result = $results->fetch_assoc()) {
			$cours[$result['index']] = new cours($result);
		}
		$this->cours=$cours;
	}
}


class nonConnecte extends utilisateur {
	function __construct() {
		//Personne n'est connecté, les appels aux fonctions de base doivent retourner NULL ou FALSE
		$this->id=NULL;
		$this->type=FALSE;
	}

	protected function loadNom() {
		$this->nom='';
		$this->prenom='';
	}
}




function chargeUtilisateur() {
	if(isset($_SESSION['matricule'])) {return new etudiant($_SESSION);}
	if(isset($_SESSION['index'])) {return new prof($_SESSION);}
	return new nonConnecte();
}
?>
