<?php
require_once(dirname(dirname(__FILE__)).'/php/sqlconfig.php');


function chargeCategorie($catId,$coursId=NULL) {
	//Charge la catégorie id en vérifiant le bon type.

	if(is_null($coursId)) {$coursId=$_SESSION['cours'];}

	$results = mysqliQuery("SELECT `Categorie-Evaluations`.* FROM `Categorie-Evaluations` WHERE  `Categorie-Evaluations`.`index`=?",'i',$catId);
	$params = $results[0];

	if($params['devoirs-params']!=0 || $params['theorie-params']) { return new categorieDevoirs($coursId,$params); }
	elseif($params['equipes-params']!=0) { return new categorieEquipes($coursId,$params); }
}


class categorie {
	protected $defaultParams;
	protected $id;
	protected $resultats=NULL;
	protected $prescriptions;

	function index() { return $this->id; }
	function nom() { return $this->defaultParams['nom']; }
	function description() { return $this->defaultParams['description']; }
	function valeurEgales() { return $this->defaultParams['valeur-egales']; }
	function eliminer() { return $this->defaultParams['eliminer']; }
	function orderForQuery($prescriptionIndex=FALSE) {return '';}
	function parametres() {
		if($this->defaultParams['devoirs-params']) {$return['devoirs']=$this->defaultParams['devoirs-params'];}
		if($this->defaultParams['theorie-params']) {$return['theorie']=$this->defaultParams['theorie-params'];}
		if($this->defaultParams['equipes-params']) {$return['equipes']=$this->defaultParams['equipes-params'];}
		return $return;
	}

	function note($matricule,$prescriptionId) {
		if(!isset($this->resultats[$matricule])) { $this->chargeResultats($matricule); }
		return $this->resultats[$matricule][$prescriptionId];
	}

	function chargeResultats($listeEtudiants) {
		//listeEtudiants doit avoir la forme d'un array contenant les étudiants classés par groupes...
		//TODO// Il faudra ajouter / modifier pour charger les résultats d'un seul étudiant...
		require_once(dirname(dirname(__FILE__)).'/cours/classeNote.php');

		foreach($this->prescriptions() as $prescription) {
			foreach( $prescription->chargeResultats($this->orderForQuery($prescription->indexPrefixed()),NULL) as $m => $note) {
				$this->resultats[$m][$prescription->indexPrefixed()]=$note;
			}
		}

		//echo '<pre>'; var_dump($this->prescriptions()); echo '</pre>';
		//S'il n'y a pas de notes à une prescription il faut fixer la note à 0 si la date limite est dépassée.
		foreach($this->prescriptions() as $prescription) {
			if($prescription->retard()==2) {
				foreach($listeEtudiants as $groupe => $liste) {
					if($prescription->ceGroupe($groupe)) {
						foreach($liste as $etudiant) {
							if(!isset($this->resultats[$etudiant->matricule()][$prescription->indexPrefixed()])) {$this->resultats[$etudiant->matricule()][$prescription->indexPrefixed()]=new note(0);}
						}
					}
				}
			}
		}


		foreach($listeEtudiants as $groupe) { foreach($groupe as $etudiant) {
			if($this->eliminer()) { //Si les notes ne comptent pas toutes, il faut éliminer les moins bonnes.
				if((count($this->resultats[$etudiant->matricule()])-$this->eliminer())<2) { //Il n'y a qu'une note à conserver (et il faut en conserver au moins une même si le nombre à éliminer permettrait de toutes les élimminer.
					$nombreEliminer=count($this->resultats[$etudiant->matricule()])-1;
				}

				uasort($this->resultats[$etudiant->matricule()],'cmpNotes');
				$somme=0;
				$nombre=0;
				foreach($this->resultats[$etudiant->matricule()] as $prescriptionId => $note) {
					if($nombreEliminer>0) {
						$note->retire();
						$nombreEliminer--;
					}
					else {
						$somme+=$note->pourcent();
						$nombre++;
					}
				}
				if($nombre) {$this->resultats[$etudiant->matricule()]['total']=new note($somme/$nombre);}
			}
			elseif($this->valeurEgales()) {
				if(count($this->resultats[$etudiant->matricule()])) {
					$somme=0;
					foreach($this->resultats[$etudiant->matricule()] as $note) { $somme += $note->pourcent(); }
					$this->resultats[$etudiant->matricule()]['total']=new note($somme/count($this->resultats[$etudiant->matricule()]));
				}
			}
			else {
				$total=0;
				$denominateur=0;
				foreach($this->resultats[$etudiant->matricule()] as $prescriptionId => $note) {
					$total+=$note->pourcent()*$this->prescriptions[$prescriptionId]->valeur()/100;
					$denominateur+=$this->prescriptions[$prescriptionId]->valeur();
				}
				$this->resultats[$etudiant->matricule()]['total']=new note($total.'/'.$denominateur);
				//echo '<pre>'; var_dump($temporaryResults); echo '</pre>';
			}
		}}//*/


		if($this->type()=='equipes') {
			$liste = array();
			foreach($this->prescriptions() as $prescription) { $liste[] = $prescription->index(); }

			$results = mysqliQuery('SELECT `prescription`, `evaluateur`, `evalue`, `retard`, `notes` FROM `Equipes-evaluations` WHERE `prescription` IN (?)','s',implode(',',$liste));
			while($resultat = $results->fetch_assoc()) {
				$notes = explode(';',$resultat['notes']);
				foreach($notes as $note) {
					$note = explode(':',$note);
					$evaluationsEquipiers[$resultat['evalue']][$note[0]][] = $note[1];
				}

				$this->resultats['nombre'.$resultat['evaluateur']][$resultat['prescription']]--;

				if($resultat['retard']) {$this->resultats['retard'.$resultat['evaluateur']][$resultat['prescription']];}
			}

			foreach($evaluationsEquipiers as $m => $evaluations) {
				$sum=0; $n=0;
				foreach($evaluations as $critere => $notes) {
					$this->resultats[$m]['c'.$critere] = new note(array_sum($notes).'/'.(10*count($notes)));
					$sum+=$this->resultats[$m]['c'.$critere]->pourcent();
					$n+=100;
				}
				if($n>0) {$this->resultats[$m]['equipiers'] = new note($sum.'/'.$n);}

				//Calcul des pénalités dues aux retards et évaluations manquantes...
				switch($this->penaliteCumulative()) {
					case 'non':
						$this->resultats[$m]['penaliteRetard'] = -$this->penaliteRetard()*isset( $this->resultats['retard'.$m] );
						$this->resultats[$m]['penaliteManquante'] = -$this->penaliteManquante()*(array_sum($this->resultats['nombre'.$m])?1:0);
						break;
					case 'travail':
						$this->resultats[$m]['penaliteRetard'] = -$this->penaliteRetard()*(isset( $this->resultats['retard'.$m] )?count(array_unique($this->resultats['retard'.$m] )):0);
						$this->resultats[$m]['penaliteManquante'] = -$this->penaliteManquante()*count(array_filter($this->resultats['nombre'.$m]));
						break;
					case 'evaluation':
						$this->resultats[$m]['penaliteRetard'] = -$this->penaliteRetard()*count( $this->resultats['retard'.$m] );
						$this->resultats[$m]['penaliteManquante'] = -$this->penaliteManquante()*array_sum($this->resultats['nombre'.$m]);
						break;
				}

				switch($this->evaluationEquipiers()) {
					case 'additive':
						$this->resultats[$m]['bonus'] = new note($this->resultats[$m]['penaliteRetard']+$this->resultats[$m]['penaliteManquante']);
						$this->resultats[$m]['totalEquipiers'] = new note($this->resultats[$m]['equipiers']->pourcent()+$this->resultats[$m]['bonus']->pourcent());
                        $this->resultats[$m]['grandTotal'] = new note(($this->resultats[$m]['total']->valeur()+($this->resultats[$m]['totalEquipiers']->pourcent())*$this->defaultParams['valeur-coevaluation']/100).'/'.($this->resultats[$m]['total']->denominateur()+$this->defaultParams['valeur-coevaluation']));
                        break;
                    case 'multiplicative':
						$this->resultats[$m]['bonus'] = new note($this->defaultParams['bonus']+$this->resultats[$m]['penaliteRetard']+$this->resultats[$m]['penaliteManquante']);
						$this->resultats[$m]['totalEquipiers'] = new note(min($this->resultats[$m]['equipiers']->pourcent()+$this->resultats[$m]['bonus']->pourcent(),100));
                        $this->resultats[$m]['grandTotal'] = new note(($this->resultats[$m]['total']->pourcent()*$this->resultats[$m]['totalEquipiers']->pourcent()/100));
                        break;//*/
				}
			}
		}
		//echo '<pre>'; var_dump($this->resultats); echo '</pre>';
	}
}

class categorieDevoirs extends categorie {
	protected $theorieParams;
	protected $devoirsParams;
	private $prescriptionsParams;


	function __construct($id,$data=NULL) {
		//parent::__construct($id);
		if(!is_null($data)) {
			$this->id = $data['index'];
			$this->defaultParams = $data;
			$this->defaultParams['cours']=$id;
		}
		else {
			$this->id = $id;

			$results = mysqliQuery("SELECT `Categorie-Evaluations`.* FROM `Categorie-Evaluations` WHERE  `Categorie-Evaluations`.`index`=?",'i',$id);
			$this->defaultParams = $results[0];
		}

		if($this->defaultParams['devoirs-params']!=0) {
			$results = mysqliQuery("SELECT `Devoirs-parametres`.* FROM `Devoirs-parametres` WHERE `Devoirs-parametres`.`index`= ?",'i',$this->defaultParams['devoirs-params']);
			$this->devoirsParams = $results[0];
		}

		if($this->defaultParams['theorie-params']!=0) {
			$results = mysqliQuery("SELECT `Theorie-parametres`.* FROM `Theorie-parametres` WHERE `Theorie-parametres`.`index`= ?",'i',$this->defaultParams['theorie-params']);
			$this->theorieParams = $results[0];
		}
  }


	private function getParams($nom,$prescriptionIndex,$type="devoirs") {
		if($prescriptionIndex[0]=='t') {
			$type='theorie';
			$prescriptionIndex = substr($prescriptionIndex,1);
		}

		if($prescriptionIndex!==FALSE) {
			if(!isset($this->prescriptionsParams[$type][$prescriptionIndex])) {

				if($type=='devoirs') {$results = mysqliQuery("SELECT * FROM `Devoirs-parametres` WHERE `prescription`= ?", 'i', $prescriptionIndex);}
				else {$results = mysqliQuery("SELECT * FROM `Theorie-parametres` WHERE `prescription`= ? ","i" ,$prescriptionIndex);}

				if($results->num_rows) {
					$this->prescriptionsParams[$type][$prescriptionIndex] = $results->fetch_assoc();
					}
				else { $this->prescriptionsParams[$type][$prescriptionIndex] = FALSE; }
			}

			if($this->prescriptionsParams[$type][$prescriptionIndex]) {
				return $this->prescriptionsParams[$type][$prescriptionIndex][$nom];
			}
		}

		if($type=="devoirs") {return $this->devoirsParams[$nom];}
		else {return $this->theorieParams[$nom];}
	}

	function type() {return 'devoirs';}

	function remisesInfo($prescriptionIndex=FALSE) {return $this->getParams('remises-info',$prescriptionIndex);}
	function remisesNombre($prescriptionIndex=FALSE) {return $this->getParams('remises-nombre',$prescriptionIndex);}
	function resultatFinal($prescriptionIndex=FALSE) {return $this->getParams('resultat-final',$prescriptionIndex);}
	function changerValeurs($prescriptionIndex=FALSE) {return $this->getParams('changer-valeurs',$prescriptionIndex);}
	function ecart($prescriptionIndex=FALSE) {return $this->getParams('ecart',$prescriptionIndex);}
	function penaliteSigne($prescriptionIndex=FALSE) {return $this->getParams('penalite-signe',$prescriptionIndex);}
	function penaliteChiffresSignificatifs($prescriptionIndex=FALSE) {return $this->getParams('penalite-chiffres-significatifs',$prescriptionIndex);}
	function penaliteUnites($prescriptionIndex=FALSE) {return $this->getParams('penalite-unites',$prescriptionIndex);}
	function unites($prescriptionIndex=FALSE) {return $this->getParams('unites',$prescriptionIndex);}

	function remisesInfoTheorie($prescriptionIndex=FALSE) {return $this->getParams('remises-info',$prescriptionIndex,'theorie');}
	function remisesNombreTheorie($prescriptionIndex=FALSE) {return $this->getParams('remises-nombre',$prescriptionIndex,'theorie');}
	function resultatFinalTheorie($prescriptionIndex=FALSE) {return $this->getParams('resultat-final',$prescriptionIndex,'theorie');}
	function changerValeursTheorie($prescriptionIndex=FALSE) {return $this->getParams('changer-valeurs',$prescriptionIndex,'theorie');}
	function ecartTheorie($prescriptionIndex=FALSE) {return $this->getParams('ecart',$prescriptionIndex,'theorie');}
	function penaliteSigneTheorie($prescriptionIndex=FALSE) {return $this->getParams('penalite-signe',$prescriptionIndex,'theorie');}
	function penaliteChiffresSignificatifsTheorie($prescriptionIndex=FALSE) {return $this->getParams('penalite-chiffres-significatifs',$prescriptionIndex,'theorie');}
	function penaliteUnitesTheorie($prescriptionIndex=FALSE) {return $this->getParams('penalite-unites',$prescriptionIndex,'theorie');}
	function unitesTheorie($prescriptionIndex=FALSE) {return $this->getParams('unites',$prescriptionIndex,'theorie');}

	function orderForQuery($prescriptionIndex=FALSE) {
		if($this->resultatFinal($prescriptionIndex)=='meilleur') {return 'note';}
		else {return 'temps';}
	}

	function prescriptions() {
		if(is_null($this->prescriptions)) {$this->loadPrescriptions();}
		return $this->prescriptions;
	}

	private function loadPrescriptions() {
		global $mysqli;
		require_once(dirname(dirname(__FILE__)).'/cours/classePrescription.php');

		if($this->defaultParams['devoirs-params']!=0) {
			$results = $mysqli->query('SELECT `index`, `groupes`, `remise`, `valeur` FROM `Devoirs-prescriptions` WHERE `cours`='.$this->defaultParams['cours'].' AND `categorie`='.$this->id.' ORDER BY `remise` ASC');
			while($result = $results->fetch_assoc()) {
				//$prescriptionsDevoirs[]=$result;
				$prescriptionsDevoirs[]=new prescriptionDevoirs($result);
			}
		}

		if($this->defaultParams['theorie-params']!=0) {
			$results = $mysqli->query('SELECT `index`, `theorie`, `groupes`, `remise`, `valeur` FROM `Theorie-prescriptions` WHERE `cours`='.$this->defaultParams['cours'].' AND `categorie`='.$this->id.' ORDER BY `remise` ASC');
			while($result = $results->fetch_assoc()) {
				//$prescriptionsTheorie[]=$result;
				$prescriptionsTheorie[]=new prescriptionTheorie($result);
			}
		}

		while(count($prescriptionsDevoirs)+count($prescriptionsTheorie)) {
			if(count($prescriptionsDevoirs)==0) {$ajout=array_shift($prescriptionsTheorie);}
			elseif(count($prescriptionsTheorie)==0) {$ajout=array_shift($prescriptionsDevoirs);}
			elseif($prescriptionsDevoirs[0]->remise()<=$prescriptionsTheorie[0]->remise()) {$ajout=array_shift($prescriptionsDevoirs);}
			else {$ajout=array_shift($prescriptionsTheorie);}

			$this->prescriptions[$ajout->indexPrefixed()]=$ajout;
		}
	}


}




class categorieEquipes extends categorie {

	function __construct($id,$data=NULL) {
		//parent::__construct($id);
		GLOBAL $mysqli;
		if(!is_null($data)) {
			$this->id = $data['index'];
			$this->defaultParams = $data;
			$this->defaultParams['cours']=$id;

			$results = $mysqli->query('SELECT * FROM `Equipes-parametres` WHERE `index`='.$this->defaultParams['equipes-params']);
			$equipesParams = $results->fetch_assoc();
			foreach($equipesParams as $key => $value) { $this->defaultParams[$key] = $value; }
			$results->free();
		}
		else {
			$this->id = $id;

			$results = $mysqli->query("SELECT `Categorie-Evaluations`.*, `Equipes-parametres`.* FROM `Categorie-Evaluations`, `Equipes-parametres` WHERE `Categorie-Evaluations`.`equipes-params`=`Equipes-parametres`.`index` AND `Categorie-Evaluations`.`index`=$id");
			$this->defaultParams = $results->fetch_assoc();
			$results->free();
		}
    }

	function type() {return 'equipes';}

	function equipesParams() { return $this->defaultParams['equipes-params']; }
	function evaluationEquipiers() { return $this->defaultParams['evaluation-equipiers']; } //	enum('non', 'additive', 'multiplicative')
	function valeurCoevaluation() { return $this->defaultParams['valeur-coevaluation']; }
	function penaliteCumulative() { return $this->defaultParams['cumulatif']; } // enum('non','travail','evaluation')
	function penaliteRetard() { return $this->defaultParams['penalite-retard']; }
	function penaliteManquante() { return $this->defaultParams['penalite-manquante']; }
	function bonus() { return $this->defaultParams['bonus']; }
	function periodeSupplementaire() { return $this->defaultParams['periode-supplementaire']; }

	function prescriptions() {
		if(is_null($this->prescriptions)) {$this->loadPrescriptions();}
		return $this->prescriptions;
	}

	private function loadPrescriptions() {
		global $mysqli;
		require_once(dirname(dirname(__FILE__)).'/cours/classePrescription.php');

		$results = $mysqli->query('SELECT `index`, `titre`, `groupes`, `denominateur`, `valeur`, `commentaireCommun`, `coevaluation`, `limite` FROM `Equipes-prescriptions` WHERE `cours`='.$this->defaultParams['cours'].' AND `categorie`='.$this->id.' ORDER BY `ordre` ASC');
		while($result = $results->fetch_assoc()) {
			//$prescriptionsDevoirs[]=$result;
			$this->prescriptions[$result['index']]=new prescriptionEquipes($result);
		}
	}//*/

	function limite($limite=NULL) {
		//Si une valeur est NULL, il faut retourner l'autre
		if(is_null($limite)) {return $this->defaultParams['limite'];}

		if(is_null($this->defaultParams['limite'])) {return $limite;}

		//Les deux valeurs ne sont pas nulles, il faut retourner la plus petite
		if($limite<$this->defaultParams['limite']) {return $limite;}
		else {return $this->defaultParams['limite'];}
	}

	function visible() {
		if($this->defaultParams['evaluation-equipiers']=='non') {return FALSE;}
		elseif($this->defaultParams['visible']==NULL) {return FALSE;}
		else {return $this->defaultParams['visible'];}
	}

	function criteres($type='solo') {
		GLOBAL $mysqli;
		if($type=='total') {
			$resultsCriteres = $mysqli->query('SELECT `index`, `enonce`, `resume` FROM `Equipes-criteres` WHERE 1');
			while($critere = $resultsCriteres->fetch_assoc()) {
				$criteres[$critere['index']]=$critere;
				if(preg_match('/(^|;)'.$critere['index'].'(;|$)/',$this->defaultParams['criteres'])) {$criteres[$critere['index']]['utilise']=True;}
				else {$criteres[$critere['index']]['utilise']=False;}
			}
		}
		else {$resultsCriteres = $mysqli->query('SELECT `index`, `enonce`, `resume` FROM `Equipes-criteres` WHERE `index`='.str_replace(';',' OR `index`=',$this->defaultParams['criteres']));
			while($critere = $resultsCriteres->fetch_assoc()) {
				$criteres[$critere['index']]=$critere;
			}
		}
		return $criteres;
	}



	function notes($matricule) {
		//Fonction qui recherche les résultats d'un étudiant (matricule) pour toutes ses prescriptions ainsi que les évaluations de ses équipiers, bonus et pénalités.
		GLOBAL $mysqli;



		//$resultsPrescriptions = $mysqli->query("SELECT `Equipes-prescriptions`.*, `Equipes-equipiers`.* FROM `Equipes-prescriptions`, `Equipes-equipiers` WHERE `Equipes-prescriptions`.`cours`=$_SESSION[cours] AND `Equipes-prescriptions`.`categorie`=".$this->id." AND `Equipes-prescriptions`.`groupes` REGEXP '^(([0-9]+,)*$_SESSION[groupe](,[0-9]+)*)|(tous)$' AND `Equipes-prescriptions`.`index` = `Equipes-equipiers`.`prescription` AND `Equipes-equipiers`.`equipiers` REGEXP '^([0-9]+;)*$matricule(;[0-9]+)*$' ORDER BY `Equipes-prescriptions`.`index` ASC");

		$matricule= ltrim ($matricule, '0'); //Enlève les "0" au début du matricule (puisque certains enregistrements l'auront retiré)

		//Il est probablement préférable de ne pas vérifier en fonction des groupes...
		$resultsPrescriptions = $mysqli->query("SELECT `Equipes-prescriptions`.*, `Equipes-equipiers`.* FROM `Equipes-prescriptions`, `Equipes-equipiers` WHERE `Equipes-prescriptions`.`cours`=$_SESSION[cours] AND `Equipes-prescriptions`.`categorie`=".$this->id." AND `Equipes-prescriptions`.`index` = `Equipes-equipiers`.`prescription` AND `Equipes-equipiers`.`equipiers` REGEXP '^([0-9]+;)*(0)*$matricule(;[0-9]+)*$' ORDER BY `Equipes-prescriptions`.`ordre` ASC");


		$noteTotale=0;
		$valeurTotale=0;
		while($prescription = $resultsPrescriptions->fetch_assoc()) {

			$return['prescriptions'][$prescription['prescription']]['equipiers']=$prescription['equipiers'];
			$return['prescriptions'][$prescription['prescription']]['titre']=$prescription['titre'];

			if($prescription['commentaireCommun']!='') {
				$commentaires = explode(';',$prescription['commentaireCommun']);
				foreach($commentaires as $commentaire) {
					if(preg_match('/^(g\d{1,4})+:/',$commentaire,$groupes)) {
						if(preg_match("/g0{0,3}$_SESSION[groupe][g:]/",$groupes[0])) {
							$return['prescriptions'][$prescription['prescription']]['commentaires'][]=substr($commentaire, strpos($commentaire, ":") + 1);
						}
					}
					else {//Le commentaire s'adresse à tous
						$return['prescriptions'][$prescription['prescription']]['commentaires'][]=$commentaire;
					}
				}
			}


			$return['prescriptions'][$prescription['prescription']]['denominateur']=$prescription['denominateur'];

			if(!$this->valeurEgales()) {
				$return['prescriptions'][$prescription['prescription']]['valeur']=$prescription['valeur'];
			}

			if(!is_null($prescription['note'])) {
				$return['prescriptions'][$prescription['prescription']]['note']=$prescription['note'];

				$return['prescriptions'][$prescription['prescription']]['pourcent']=100*$prescription['note']/$prescription['denominateur'];

				if($this->valeurEgales()) {
					$return['prescriptions'][$prescription['prescription']]['valeur']=1;
					$noteTotale+=$return['prescriptions'][$prescription['prescription']]['pourcent'];
					$valeurTotale++;
				}
				else {
					$noteTotale+=$prescription['valeur']*$return['prescriptions'][$prescription['prescription']]['pourcent'];
					$valeurTotale+=$prescription['valeur'];
				}
			}

			if($prescription['commentaire']!='') {$return['prescriptions'][$prescription['prescription']]['commentaire']=$prescription['commentaire'];}

			//Vérifie si l'étudiant peut faire les évaluations de ses équipiers et calcule les pénalités (si applicable)
			$evaluationsAFaire[$prescription['prescription']]=substr_count($prescription['equipiers'],';');

			$resultsEvaluations = $mysqli->query("SELECT `retard` FROM `Equipes-evaluations` WHERE `prescription`=$prescription[prescription] AND `evaluateur`=$matricule");
			while($result = $resultsEvaluations->fetch_assoc()) {
				$evaluationsAFaire[$prescription['prescription']]--;
				if($result['retard']) {$evaluationsEnRetard[$prescription['prescription']][]=1;}
			}

			if($prescription['coevaluation']) {
				//Vérifier si la limite n'est pas dépassée
				$limite = $this->limite($prescription['limite']);
				if(is_null($limite) || time()<strtotime($limite.' 00:00:00')+2*86400) {
					// Si le nombre d'évaluations faites est plus petit que celui à faire...
					if($resultsEvaluations->num_rows<$evaluationsAFaire[$prescription['prescription']]) {
						$return['actions'][$prescription['prescription']] = is_null($limite)?'':$limite;
					}
				}
			}
		}
		$resultsPrescriptions->free();

		if($valeurTotale) {
			$return['moyenne']=$noteTotale/$valeurTotale;
			if(!$this->valeurEgales()) {
				$return['note']=$noteTotale/100;
				$return['valeur']=$valeurTotale;
			}
		}


		//Recherche du bonus (si applicable)
		if($this->defaultParams['evaluation-equipiers']=='multiplicative') {
			$return['bonus'] = $this->defaultParams['bonus'];
		}

		if($limite=$this->visible()) {
			$resultsEvaluations = $mysqli->query("SELECT `Equipes-evaluations`.`total`, `Equipes-evaluations`.`notes` FROM `Equipes-evaluations`, `Equipes-prescriptions` WHERE `Equipes-evaluations`.`evalue`=$matricule AND `Equipes-evaluations`.`temps` <= '$limite 23:59:59' AND `Equipes-evaluations`.`prescription`=`Equipes-prescriptions`.`index` AND `Equipes-prescriptions`.`categorie`=".$this->id);

			while($result=$resultsEvaluations->fetch_assoc()) {
				$return['equipiers'][]=$result['total'];
				$result['notes']=explode(';',$result['notes']);
				foreach($result['notes'] as $noteCritere){
					$noteCritere=explode(':',$noteCritere);
					$return['criteres'][$noteCritere[0]][]=$noteCritere[1];
				}//*/
			}
			$resultsEvaluations->free();

			if(isset($return['equipiers'])) {
				$return['equipiers']=array_sum($return['equipiers'])/count($return['equipiers']);

				foreach($return['criteres'] as $critere => $notes) {
					$return['criteres'][$critere] = 10*array_sum($notes)/count($notes);
				}
			}//*/
		}


		switch($this->defaultParams['cumulatif']) {
			case 'non':
				$return['retard']['pourcent'] = $this->defaultParams['penalite-retard']*isset(	$evaluationsEnRetard);
				$return['manquante']['pourcent'] = $this->defaultParams['penalite-manquante']*(	array_sum($evaluationsAFaire)?1:0);
				break;

			case 'travail':
				$return['retard']['nombre'] = isset($evaluationsEnRetard)?count(array_unique($evaluationsEnRetard)):0;
				$return['retard']['pourcent'] = $this->defaultParams['penalite-retard']*$return['retard']['nombre'];
				$return['manquante']['nombre'] = count(array_filter($evaluationsAFaire));
				$return['manquante']['pourcent'] = $this->defaultParams['penalite-manquante']*$return['manquante']['nombre'];
				break;

			case 'evaluation':
				$return['retard']['nombre'] = count($evaluationsEnRetard);
				$return['retard']['pourcent'] = $this->defaultParams['penalite-retard']*$return['retard']['nombre'];
				$return['manquante']['nombre'] = array_sum($evaluationsAFaire);
				$return['manquante']['pourcent'] = $this->defaultParams['penalite-manquante']*$return['manquante']['nombre'];
				break;

		}




		if($this->defaultParams['evaluation-equipiers']=='multiplicative') {
			$return['bonusPenalites']=$return['bonus']-$return['retard']['pourcent']-$return['manquante']['pourcent'];
			$return['equipiersPenalites']=min(100,$return['equipiers']+$return['bonusPenalites']);
			if($valeurTotale) {
				$return['total']=$return['moyenne']*$return['equipiersPenalites']/100;
			}
		}
		elseif($category['evaluation-equipiers']=='additive') {
			$return['valeurEquipiers']=$this->defaultParams['valeur-coevaluation'];
			$return['bonusPenalites']=-$return['retard']['pourcent']-$return['manquante']['pourcent'];
			$return['equipiersPenalites']=$return['equipiers']-$return['bonusPenalites'];
			if($valeurTotale) {
				$return['total']=($noteTotale+$return['equipiersPenalites']*$this->defaultParams['valeur-coevaluation'])/($valeurTotale+$this->defaultParams['valeur-coevaluation']);
			}
		}

		return $return;//*/
	}
}



function cmpNotes($a, $b) {
    if ($a->pourcent() == $b->pourcent()) { return 0; }
    return ($a->pourcent() < $b->pourcent()) ? -1 : 1;
}
?>
