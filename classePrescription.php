<?php
require_once(dirname(dirname(__FILE__)).'/php/sqlconfig.php');

class prescription {
	protected $id;
	protected $groupes;
	protected $ceGroupe;
	protected $valeur;
	protected $titreAbbr;
	protected $resultats;
	
	
	function index() {return $this->id;}
	function indexPrefixed() {return $this->index();}
	function groupes() {return $this->groupes;}
	function valeur() {return $this->valeur;}
	function retard($date=NULL) {return False;}
	function ceGroupe($groupe) {
		if($this->groupes=='tous') { return True;}
		if(is_null($this->ceGroupe)) {
			foreach(explode(',',$this->groupes) as $g) {$this->ceGroupe[$g]=True;}
		}
		return isset($this->ceGroupe[$groupe]);
	}
	function titre() {return '';}
	function titreAbbr() {
		if(is_null($this->titreAbbr)) {
			$maxLength=6;
			if(strlen($this->titre())<=$maxLength) {$this->titreAbbr=$this->titre();}
			else {
				//Recherche la longueur du premier mot (s'il y en a plusieurs)
				$s=strpos($this->titre(),' ');
	
				if($s!==FALSE && $s<$maxLength) {return substr($this->titre(),0,$s);} //Retourne le premier mot s'il est plus court que maxlength
		
				preg_match('/^[bcçdfghjklmnpqrstvwxz]*[aàâeéèêiïouùy]+[bcçdfghjklmnpqrstvwxz]*/i',$this->titre(),$return);
				$return = $return[0];
				//if(strlen($return)>=$maxLength) {$return=substr($return,0,$maxLength-1);}
				if(substr($return,-2,1)==substr($return,-1,1)) {$return=substr($return,0,-1);} //Retire la dernière lettre si c'est une consonne qui se répète
				if($s!==FALSE && $s<=strlen($return)+1) {return substr($this->titre(),0,$s);} //Retourne le premier mot s'il est aussi court que son abbréviation + un point
				$this->titreAbbr=$return.'.';
			}
		}
		return $this->titreAbbr;
	}
}

class prescriptionDevoirs extends prescription {
	private $remise;
	private $devoirs;
	private $nombreResultats;
	private $titre;
	
	function __construct($data) {
		$this->id = $data['index'];
		$this->groupes = $data['groupes'];
		$this->valeur = $data['valeur'];
		$this->remise = $data['remise'];
	}
	
	function type() {return 'devoirs';}
	function remise() {return $this->remise;}
	function devoirs() {
		if(is_null($this->devoirs)) { $this->loadDevoirs(); }
		return $this->devoirs;
	}
	function nombreResultats() {
		if(is_null($this->nombreResultats)) {
			global $mysqli;
			$results = $mysqli->query('SELECT DISTINCT `matricule` FROM `Devoirs-resultats` WHERE `prescription`='.$this->id.' AND `note` IS NOT NULL');
 			$this->nombreResultats = $results->num_rows;
			$results->free();
		}
		return $this->nombreResultats;
	}
	function titre() {
		if(is_null($this->titre)) {
			$this->titre=implode(' - ',$this->devoirs());
		}
		return $this->titre;
	}
	function retard($date=NULL) {
		if(is_null($date)) {$date = time();}
		
		$limite = strtotime($this->remise().' 00:00:00');
		
		if($date>$limite+2*86400) {return 2;} //retard de plus de 24h
		elseif($date>$limite+86400) {return 1;} //retard de moins de 24h
		else {return False;} //pas de retard
	}
	
	function chargeResultats($ordre,$matricule=NULL) {
		global $mysqli;
		require_once(dirname(dirname(__FILE__)).'/cours/classeNote.php');
			
		if(is_null($matricule)) { $results = $mysqli->query('SELECT `matricule`, `note` FROM `Devoirs-resultats` WHERE `prescription`="'.$this->indexPrefixed().'" AND `note` IS NOT NULL ORDER BY `'.$ordre.'` ASC');}
		else { $results = $mysqli->query('SELECT `matricule`, `note` FROM `Devoirs-resultats` WHERE `matricule`='.$matricule.' AND `prescription`="'.$this->indexPrefixed().'" AND `note` IS NOT NULL ORDER BY `'.$ordre.'` ASC'); }
		
		while($note = $results->fetch_assoc()) {
			$this->resultats[$note['matricule']]=new note($note['note']);
		}
		return $this->resultats;
	}
	
	
	/*************************
	*** Fonctions internes ***
	*************************/
	private function loadDevoirs() {
		global $mysqli;
		$results = $mysqli->query('SELECT `Devoirs`.`index`, `Devoirs`.`titre` FROM `Devoirs-liste-prescriptions-devoirs`, `Devoirs` WHERE `Devoirs-liste-prescriptions-devoirs`.`prescription`='.$this->id.' AND `Devoirs-liste-prescriptions-devoirs`.`devoir`=`Devoirs`.`index`');
		
		while($result = $results->fetch_assoc()) {$this->devoirs[$result['index']]=$result['titre'];}
	}
}


class prescriptionTheorie extends prescription {
	private $remise;
	private $theorieId;
	private $theorieTitre;
	private $nombreResultats;

	function __construct($data) {
		$this->id = $data['index'];
		$this->groupes = $data['groupes'];
		$this->valeur = $data['valeur'];
		$this->remise = $data['remise'];
		$this->theorieId = $data['theorie'];
	}

	function indexPrefixed() {return 't'.$this->index();}
	function type() {return 'theorie';}
	function remise() {return $this->remise;}
	function theorie() {
		if(is_null($this->theorieTitre)) { $this->loadTitre(); }
		return $this->theorieTitre;
	}
	function titre() {return $this->theorie();}
	function retard($date=NULL) {
		if(is_null($date)) {$date = time();}
		$limite = strtotime($this->remise().' 00:00:00');
		if($date>$limite+2*86400) {return 2;} //retard de plus de 24h
		elseif($date>$limite+86400) {return 1;} //retard de moins de 24h
		else {return False;} //pas de retard
	}

	function nombreResultats() {
		/*if(is_null($this->nombreResultats)) {
			global $mysqli;
			$results = $mysqli->query('SELECT DISTINCT `matricule` FROM `Devoirs-resultats` WHERE `prescription`='.$this->id.' AND `note` IS NOT NULL');
 			$this->nombreResultats = $results->num_rows;
			$results->free();
		}
		return $this->nombreResultats;//*/
		return 0; //Il faudra changer cette fonction... doit vérifier le nombre de résultats complétés
	}
	
	function chargeResultats($ordre,$matricule=NULL) {
		global $mysqli;
		require_once(dirname(dirname(__FILE__)).'/cours/classeNote.php');
		
		$results = $mysqli->query('SELECT SUM(`Theorie-enonce`.`nombreQuestions`) FROM `Theorie-enonce`, `Theorie-prescriptions` WHERE `Theorie-prescriptions`.`theorie`=`Theorie-enonce`.`theorie` AND `Theorie-prescriptions`.`index`='.$this->index());
		$denom = $results->fetch_row();
		$denom = $denom[0];
			
		//echo 'SELECT `matricule`, `devoir`, `notes` FROM `Devoirs-resultats` WHERE `prescription`="'.$this->indexPrefixed().'" AND `note` IS NOT NULL ORDER BY `'.$ordre.'` ASC<br>';	
			
		if(is_null($matricule)) { $results = $mysqli->query('SELECT `matricule`, `devoir`, `notes` FROM `Devoirs-resultats` WHERE `prescription`="'.$this->indexPrefixed().'" AND `note` IS NOT NULL ORDER BY `'.$ordre.'` ASC');}
		else { $results = $mysqli->query('SELECT `matricule`, `devoir`, `notes` FROM `Devoirs-resultats` WHERE `matricule`='.$matricule.' AND `prescription`="'.$this->indexPrefixed().'" AND `note` IS NOT NULL ORDER BY `'.$ordre.'` ASC'); }
		
		$temporaryResults = array();
		while($result = $results->fetch_assoc()) {
		
			$notes = explode(';',$result['notes']);
			$total=0;
			foreach($notes as $note) {
				$note = explode(':',$note);
				$total+=$note[1];
			}
			$temporaryResults[$result['matricule']][$result['devoir']]=$total;
		}
		//echo '<pre>'; var_dump($temporaryResults); echo '</pre>';
				
		foreach($temporaryResults as $matricule => $notes) {
			$total=0;
			foreach($notes as $note) {
				$total += $note;
			}
		$this->resultats[$matricule]=new note($total.'/'.$denom);
		}
		
		
		return $this->resultats;//*/
	}

	
	/*************************
	*** Fonctions internes ***
	*************************/
	private function loadTitre() {
		global $mysqli;
		$results = $mysqli->query('SELECT `titre` FROM `Theorie` WHERE `index`='.$this->theorieId);
		
		$result = $results->fetch_assoc();
		$this->theorieTitre = $result['titre'];
	}
}


class prescriptionEquipes extends prescription {
	private $titre;
	private $denominateur;
	private $commentaireCommun;
	private $coevaluation;
	private $limite;
	private $equipesEnregistrees;
	private $resultatsEnregistres;
	private $evaluations;

	function __construct($data) {
		$this->id = $data['index'];
		$this->groupes = $data['groupes'];
		$this->valeur = $data['valeur'];
		$this->titre = $data['titre'];
		$this->denominateur = $data['denominateur'];
		$this->commentaireCommun = $data['commentaireCommun'];
		$this->coevaluation = $data['coevaluation'];
		$this->limite = $data['limite'];
	}

	function type() {return 'equipes';}
	function titre() {return $this->titre;}
	function denominateur() {return $this->denominateur;}
	function commentaireCommun() {return $this->commentaireCommun;}
	function coevaluation() {return $this->coevaluation;}
	function limite() {return $this->limite;}
	
	
	function evaluationsAFaire() {
		if(is_null($this->evaluations['aFaire'])) {$this->loadDivers();}
		return $this->evaluations['aFaire'];
	}
	function evaluationsFaites() {
		if(is_null($this->evaluations['faites'])) {$this->loadDivers();}
		return $this->evaluations['faites'];
	}
	function resultatsEnregistres() {
		if(is_null($this->resultatsEnregistres)) {$this->loadDivers();}
		return $this->resultatsEnregistres;
	}
	function equipesEnregistrees() {
		if(is_null($this->equipesEnregistrees)) {$this->loadDivers();}
		return $this->equipesEnregistrees;
	}
	
	function chargeResultats($ordre,$matricule=NULL) {
		global $mysqli;
		require_once(dirname(dirname(__FILE__)).'/cours/classeNote.php');
		
		if(is_null($matricule)) {
			$results = $mysqli->query('SELECT `equipiers`, `note` FROM `Equipes-equipiers` WHERE `prescription`="'.$this->indexPrefixed().'"');
			while($note = $results->fetch_assoc()) {
				$matricules = explode(';',$note['equipiers']); 
				$nombreEvaluations= count($matricules)-1;
				foreach($matricules as $m) {
					if(!is_null($note['note'])) { $this->resultats[$m]=new note($note['note'].'/'.$this->denominateur()); }
					if($this->coevaluation()) { $this->resultats['nombre'.$m]= $nombreEvaluations; }
				}
			}
		}
		else {
			$results = $mysqli->query('SELECT `equipiers`, `note` FROM `Equipes-equipiers` WHERE `equipiers` REGEXP "^([0-9]+;)*(0)*'.$matricule.'(;[0-9]+)*$" AND `prescription`="'.$this->indexPrefixed().'"');
			while($note = $results->fetch_assoc()) {
				if(!is_null($note['note'])) { $this->resultats[$matricule]=new note($note['note'].'/'.$this->denominateur()); }
				if($this->coevaluation()) { $this->resultats['nombre'.$matricule]= substr_count($note['equipiers'],';'); }
			}
		}
		//echo '<pre>'; var_dump($this->resultats); echo '</pre>';
		return $this->resultats;
	}
	
	private function loadDivers() {
		global $mysqli;
		$this->resultatsEnregistres=FALSE;
		$this->evaluations['aFaire']=0;
		$results = $mysqli->query('SELECT `equipiers`, `note`  FROM `Equipes-equipiers` WHERE `prescription`='.$this->index());
		
		if($results->num_rows) { $this->equipesEnregistrees=TRUE; }
		else { $this->equipesEnregistrees=FALSE; }
		
		while($equipe=$results->fetch_assoc()) {
			if(!is_null($equipe['note'])) {$this->resultatsEnregistres=TRUE;}
			$equipe = explode(';',$equipe['equipiers']);
			$count = count($equipe);
			$this->evaluations['aFaire'] += $count*($count-1);
		}
		$results->free();
		
		//Et du nombre d'évaluations faites
		$results = $mysqli->query('SELECT `evaluateur` FROM `Equipes-evaluations` WHERE `prescription`='.$this->index());
		$this->evaluations['faites']=$results->num_rows;
		$results->free();
	}
}

?>