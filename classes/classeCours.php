<?php
require_once(dirname(dirname(__FILE__)).'/php/sqlconfig.php');

class cours {
	private $id;
	private $titre;
	private $groupes;
	private $categories;
	//private $session;
	//private $profs;
	private $etudiants;
	
	function __construct($id) { $this->id = $id; }
	
	function getId() { return $this->id; }
	
	function getTitre() {
		if(is_null($this->titre)) { $this->loadCours(); }
		return $this->titre;
	}

	function getGroupes() {
		if(is_null($this->groupes)) { $this->loadGroupes(); }
		return $this->groupes;
	}
	
	
	function getEtudiants($groupe=NULL) {
		if(is_null($this->etudiants)) { $this->loadEtudiants(); }
		if(is_null($groupe) || $groupe=='tous') {
			$dummy = array();
			foreach($this->etudiants as $etudiants) {$dummy = array_merge($dummy,$etudiants);}
			return $dummy;
		}
		elseif($groupe=='groupe' || $groupe=='groupes') {return $this->etudiants;}
		else { return $this->etudiants[$groupe]; }
	}

	function getCategories() {
		if(is_null($this->categories)) { $this->loadCategories(); }
		return $this->categories;
	}


	/*************************
	*** Fonctions internes ***
	*************************/
	private function loadCours() {
		//Charge les données du cours... pour l'instant uniquement le titre du cours
		global $mysqli;
		$results = $mysqli->query("SELECT `nom` FROM `Cours` WHERE `index`=".$this->id);
		$resultat = $results->fetch_assoc();
		$this->titre = $resultat['nom'];
	}
	
	private function loadGroupes() {
		//Recherche de la liste de groupes
		GLOBAL $mysqli;
	
		$resultsGroupes = $mysqli->query("SELECT `groupes` FROM `Liste-Cours-Profs` WHERE `prof`=$_SESSION[prof] AND `cours`=".$this->id);
		$groupes = $resultsGroupes->fetch_assoc();
	
		if($groupes['groupes']=='tous') {
			$resultsGroupes->free();
			$resultsGroupes = $mysqli->query('SELECT DISTINCT `groupe` FROM `Liste-Cours-Etudiants` WHERE `cours`='.$this->id.' AND `groupe` IS NOT NULL');
			$groupes=Array();
			while($groupe = $resultsGroupes->fetch_assoc()) {
				$groupes[]=$groupe['groupe'];
				
			}
		}
		else {
			$groupes = explode(',',$groupes['groupes']);
		}
		$resultsGroupes->free();
		sort($groupes);
		$this->groupes = $groupes;
	}
	
	
	private function loadEtudiants() {
		//Récupère la liste des étudiants (stocké par ordre alphabétique dans $etudiants[groupe][])
		global $mysqli;
		require_once(dirname(dirname(__FILE__)).'/cours/classeEtudiant.php');

		$results = $mysqli->query('SELECT `Etudiants`.*, `Liste-Cours-Etudiants`.`groupe` FROM `Etudiants`, `Liste-Cours-Etudiants` WHERE `Liste-Cours-Etudiants`.`cours`='.$this->getId().' AND `Etudiants`.`matricule`=`Liste-Cours-Etudiants`.`matricule` ORDER BY `Liste-Cours-Etudiants`.`groupe` ASC, `Etudiants`.`nom` ASC, `Etudiants`.`prenom` ASC');
		while($etudiant = $results->fetch_assoc()) {
			$matricule = $etudiant['matricule'];
			unset($etudiant['matricule']);
			$groupe = $etudiant['groupe'];
			unset($etudiant['groupe']);
			
			$this->etudiants[$groupe][] = new etudiant($matricule,$etudiant,array($this));
		}
	}
	
	private function loadCategories() {
		global $mysqli;
		$resultsCategory = $mysqli->query("SELECT * FROM `Categorie-Evaluations` WHERE `cours`=$_SESSION[cours] AND ( `prof`=$_SESSION[prof] OR `prof`=0 ) ORDER BY `cours` DESC");
		
		require_once(dirname(dirname(__FILE__)).'/cours/classeCategorieEvaluation.php');
		while($categorieInfos = $resultsCategory->fetch_assoc()) {
			if($categorieInfos['devoirs-params']!=0 || $categorieInfos['theorie-params']) { $this->categories[]=new categorieDevoirs($this->id,$categorieInfos); }
			elseif($categorieInfos['equipes-params']!=0) { $this->categories[]=new categorieEquipes($this->id,$categorieInfos); }
		}
	}
}
?>