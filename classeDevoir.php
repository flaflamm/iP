<?php
class devoir {
	private $id;
	private $devoir;
	private $questions;
	private $enonces;
	private $valeursAleatoires;
	private $reponses;
	private $constantes;
	
	
	function __construct($index) {
		//index est l'index du devoir
		GLOBAL $mysqli;
		
		$this->id=$index;
		//Récupérer les données du devoir
		$result = $mysqli->query('SELECT * FROM  `Devoirs` WHERE `index`="'.$this->id.'"');
		$this->devoir=$result->fetch_assoc();
		$result->free();
		
		//Récupérer les questions du devoir
		$result = $mysqli->query('SELECT * FROM  `Devoirs-questions` WHERE `devoir` LIKE "'.$this->id.'" ORDER BY `question` ASC');
		while($resultat=$result->fetch_assoc()) {
			$this->questions[$resultat['question']]=$resultat;
			if($resultat['type']!='numerique') {
				$result_reponses = $mysqli->query('SELECT `reponse`, `enonce`, `bonne` FROM `Devoirs-questions-reponses` WHERE `devoir` LIKE "'.$this->id.'" AND `question`='.$resultat['question']);
				while($resultat_reponses = $result_reponses->fetch_assoc()) {
					$this->questions[$resultat['question']]['reponses'][$resultat_reponses['reponse']]=$resultat_reponses;
				}
			}
		}
		$result->free();
		
		//Récupérer les énoncés secondaires du devoir
		$result = $mysqli->query('SELECT * FROM  `Devoirs-enonces` WHERE `devoir` LIKE "'.$this->id.'"');
		while($resultat=$result->fetch_assoc()) {
			$this->enonces[$resultat['index']]=$resultat;
			$this->enonces[$resultat['index']]['affiche']=FALSE;
			$questionsNecessitantEnonce=explode(',',$this->enonces[$resultat['index']]['questions']);
			foreach($questionsNecessitantEnonce as $numeroQuestion) {
				$this->questions[$numeroQuestion]['enonceNecessaire'][]=$resultat['index'];
			}
		}
		$result->free();
		
		//Les constantes doivent aussi être NULL tant qu'elles n'ont pas été fixées.
		$this->constantes=NULL;
		
		//Les valeurs aleatoires doivent être NULL si elles n'ont pas été fixées.
		$this->valeursAleatoires=NULL;
		
	}
	
	function titre() {
		return $this->devoir['titre'];
	}
	
	function prefix($questionId) {
		return $this->questions[$questionId]['prefix'];
	}
	
	function chiffresSignificatifs($questionId) {
		return $this->questions[$questionId]['chiffres-significatifs'];
	}
	
	function parametresParticuliers() {
		return $this->devoir['parametres-particuliers'];
	}
	
	function setValeursAleatoires($valeursAleatoires) {
		$this->valeursAleatoires = $valeursAleatoires;
	}
	
	function enonce($enonceId=NULL) {
		if(is_null($enonceId)) {return $this->afficheVariables($this->devoir['enonce']);}
		else {return $this->afficheVariables($this->enonces[$enonceId]['enonce']);}
	}
	
	function questionList() {
		return array_keys($this->questions);
	}
	
	function question($questionId) {
		return $this->afficheVariables($this->questions[$questionId]['enonce']);
	}
	
	function typeQuestion($questionId) {
		return $this->questions[$questionId]['type'];
	}
	
	function enonceNecessaire($questionId) {
		//Vérifie si un énoncé secondaire est nécessaire pour la question
		//Retourne un array contenant les index des énoncés si oui et FALSE si non.
		$return = array();
		if(isset($this->questions[$questionId]['enonceNecessaire'])) {
			//$enonceId=$this->questions[$questionId]['enonceNecessaire'];
			foreach($this->questions[$questionId]['enonceNecessaire'] as $enonceId) {
				if(!$this->enonces[$enonceId]['affiche']) {
					$this->enonces[$enonceId]['affiche']=TRUE;
					$return[]=$enonceId;
				}
			}
		}
		if(count($return)) {return $return;}
		else {return FALSE;}
	}
	
	function getReponses($questionId) {
		
		if(!isset($this->reponses[$questionId])) {
			$search=array();
			$replace=array();
			foreach($this->valeursAleatoires as $nom => $valeur) {
				$search[]='{'.$nom.'}';
				$replace[]=$valeur;
			}
			
			if(is_null($this->constantes)) { $this->constantes = chercheConstantes(); }
			foreach($this->constantes as $symbole => $constantArray) {
				$search[]='{c:'.$symbole.'}';
				$replace[]=$constantArray['valeur'];
			}//*/
			
			//Remplace les valeurs connues et les constantes dans le string de la réponse
			$this->reponses[$questionId] = $this->calculeReponse(str_replace($search,$replace,$this->questions[$questionId]['reponse']));
		}
		return $this->reponses[$questionId];
	}
	
	function setReponse($questionId,$reponse) {
		$this->reponses[$questionId][] = $reponse;
	}
	
	function getUnites($questionId,$une=FALSE) {
		//Retourne les unités d'une question
		//Si $une==TRUE, retourne une chaîne de caractère d'une seule unité
		//Si $une==FALSE, retourne un array contenant toutes les unités acceptées
		$unites = explode(' ou ',$this->questions[$questionId]['unites']);
		if($une) { return $unites[0]; }
		else { return $unites; }
	}
	
	
	
	
	function afficheDevoir($resultId=NULL,$oldResultsId=NULL,$nombreAFaire=NULL,$afficheInstructions=TRUE) {
		GLOBAL $mysqli;
		
		if(is_null($this->devoir)) { return false;}

		
		require_once(dirname(dirname(__FILE__)).'/commun/formateNombre.php');
		
		$alpha='A';
		date_default_timezone_set('America/Montreal');
		
		//Recherche les résultats de l'étudiant s'ils sont connus
		if(!is_null($resultId)) {
			$results = $mysqli->query("SELECT * FROM `Devoirs-resultats` WHERE `index`= $resultId");
			$resultats = $results->fetch_assoc();
			$results->free();
			
			$questionsListe=explode(',',$resultats['questions']);
			
			//Fixe les valeurs aléatoires si ce n'est pas déjà fait
			if(is_null($this->valeursAleatoires)) {
				$this->setValeursAleatoires(recupereValeurs($resultats['valeurs_aleatoires']));
			}
			
			//Récupère (dans des arrays) les valeurs des réponses, des notes, des codes et des bonnes réponses
			$resultats['reponses']=recupereValeurs($resultats['reponses'],':');
			$resultats['notes']=recupereValeurs($resultats['notes'],':');
			$resultats['codes']=recupereValeurs($resultats['codes'],':');
			$resultats['bonnes_reponses']=recupereValeurs($resultats['bonnes_reponses'],':');
			
			
			//Recherche les critères de correction de ce devoir
			$criteresCorrection = chercheParametreCorrection($resultats['prescription'],$this->parametresParticuliers());
			
			//Vérifie si la limite est dépassée, on affichera alors toutes les informations
			if(time()>strtotime($criteresCorrection['remise'].' 00:00:00')+2*86400) {$criteresCorrection['remises-info']='tout'; }
			
			//Si c'est le prof qui vérifie le devoir d'un étudiant, il faut afficher tout (si le devoir a été complété)
			if(!is_null($resultats['note']) && isset($_SESSION['prof'])) {
				$criteresCorrection['remises-info']='tout'; }
			
			//Charge les constantes
			$this->constantes = chercheConstantes($criteresCorrection['cours']);
		}
		else { $questionsListe=$this->questionList(); }
		
		
		//Recherche les anciens résultats de l'étudiant s'ils sont connus
		if(!is_null($oldResultsId)) {
			$results = $mysqli->query("SELECT `note`, `commentaire`, `temps`, `reponses`, `notes`, `codes`, `bonnes_reponses` FROM `Devoirs-resultats` WHERE `index`= $oldResultsId");
			$anciensResultats = $results->fetch_assoc();
			$results->free();
			
			//Récupère (dans des arrays) les valeurs des réponses, des notes, des codes et des bonnes réponses
			$anciensResultats['reponses']=recupereValeurs($anciensResultats['reponses'],':');
			$anciensResultats['notes']=recupereValeurs($anciensResultats['notes'],':');
			$anciensResultats['codes']=recupereValeurs($anciensResultats['codes'],':');
			$anciensResultats['bonnes_reponses']=recupereValeurs($anciensResultats['bonnes_reponses'],':');
		}
		else {
			foreach($questionsListe as $questionNumber) {$anciensResultats['reponses'][$questionNumber]='';}
		}


		if($afficheInstructions) { //S'il ne faut pas afficher les instructions, il n'y a pas de colones à faire
			echo "<div class='row'><div class='medium-8 large-7 columns'>";
		}
		if($resultats['prescription'][0]!='t') { //Si c'est un devoir associé à une théorie, ne pas afficher le titre.
			echo "<h2>".$this->titre();
			if(!is_null($resultId) && !is_null($resultats['temps'])) {
				//Affichage du nom de l'étudiant (si c'est un prof qui regarde)
				if(isset($_SESSION['prof'])) {
					$resultNom = $mysqli->query("SELECT * FROM `Etudiants` WHERE `matricule` = $resultats[matricule]");
					$nom = $resultNom->fetch_assoc();
					$resultNom->free();
					echo "<br><span class='smaller'>Remis par : $nom[prenom] $nom[nom]</span>";
				}
				//Affichage du moment de la remise
				echo '<br><small>Remis le : '.str_replace(' ',' à ',$resultats['temps']).'</small>';
			}
			echo "</h2>";
		}
		echo "<h4>".$this->enonce().'</h4>';
		
		
		foreach($questionsListe as $questionNumber) {
			//Vérifie si un énoncé secondaire est nécessaire et l'affiche le cas échéant
			if($enonceIds=$this->enonceNecessaire($questionNumber)) {
				//ENONCE : Ajouter un foreach pour parcourrir tous les énoncés
				foreach($enonceIds as $enonceId) {
					echo '<div class="row"><div class="small-12 columns"><h4>'.$this->enonce($enonceId).'</h4></div></div>';
				}
			}
			
			//Affiche la question
			echo '<div class="row"><div class="small-12 columns question"><span>'.$alpha.')</span>'.$this->question($questionNumber);//.'<br>';
			
			//if($_SESSION['matricule']==1111112) {echo '('.$this->chiffresSignificatifs($questionNumber).')';}
			
			if(!is_null($resultId)) {
				if(is_null($resultats['note'])) {
					if(!isset($_SESSION['prof'])) {
						//Affiche le formulaire de réponse
						if($this->typeQuestion($questionNumber)=='numerique') {
							echo '<br><div class="row hide-for-print"><div class="medium-8 large-6 columns">';
							if($criteresCorrection['unites']=='demandees') {
								echo "<input type='text' class='text-center question_$resultId' id='reponse_$questionNumber' value='".$anciensResultats['reponses'][$questionNumber]."'>";
							}
							else {
								$unit=$this->getUnites($questionNumber,TRUE);
								if($unit=='') {
									echo "<div class='row collapse'><div class='small-8 columns'><input type='text' class='text-center question_$resultId' id='reponse_$questionNumber' value='".$anciensResultats['reponses'][$questionNumber]."'></div><div class='small-4 columns'><span data-tooltip class='postfix radius has-tip' title='Sans unités'>S. U.</span></div></div>";
								}
								else {
									echo "<div class='row collapse'><div class='small-8 columns'><input type='text' class='text-center question_$resultId' id='reponse_$questionNumber' value='".$anciensResultats['reponses'][$questionNumber]."'></div><div class='small-4 columns'><span class='postfix radius'>$unit</span></div></div>";
								}
							}
							
							//Affiche les informations de la réponse précédente si applicable.
							if(!is_null($oldResultsId)) {
								if($criteresCorrection['remises-info']=='tout') {
									if($anciensResultats['codes'][$questionNumber][0]=='b') {
										echo '</div><div class="medium-4 large-6 columns"><span class="semiBold">Bonne réponse</span>';
										if(strpos($anciensResultats['codes'][$questionNumber],'p')) {
											echo ' en fonction des réponses précédentes';
										}
										if(strpos($anciensResultats['codes'][$questionNumber],'s')) {
											echo ' mais mauvais signe ( -'.$criteresCorrection['penalite-signe'].' pt ).';
										}
										else {echo '.';}
									}
									else {
										echo '</div><div class="medium-4 large-6 columns">Mauvaise réponse, la bonne réponse était : <span class="semiBold">'.formateNombre($anciensResultats['bonnes_reponses'][$questionNumber],'s',$this->chiffresSignificatifs($questionNumber)).' '.$this->getUnites($questionNumber,TRUE).'</span>';
									}
									
		
									if(strpos($anciensResultats['codes'][$questionNumber],'u')) {
										echo '<br>Mauvaises unités ( -'.$criteresCorrection['penalite-unites'].' pt )';
									}
									if(preg_match('/C(\d+)/',$anciensResultats['codes'][$questionNumber],$match)) {
										echo "<br>Il y a $match[1] chiffre(s) significatif(s) de trop ( -".($match[1]*$criteresCorrection['penalite-chiffres-significatifs'])." pt )";
									}
									if(preg_match('/c(\d+)/',$anciensResultats['codes'][$questionNumber],$match)) {
										echo "<br>Il manque $match[1] chiffre(s) significatif(s) ( -".($match[1]*$criteresCorrection['penalite-chiffres-significatifs'])." pt )";
									}
								}
								elseif($criteresCorrection['remises-info']=='notes') {
									echo '</div><div class="medium-4 large-6 columns"><span class="larger semiBold">'.$anciensResultats['notes'][$questionNumber].'/1</span>';
								}
							}
							
							echo '</div></div>';
						}
						elseif($this->typeQuestion($questionNumber)=='multiple') {
							echo '<br>';
							//Afficher les choix de réponses
						}
					}
					else {
						//Affiche seulement les unités (si nécessaire), c'est le prof qui regarde l'énoncé d'un étudiant.
						$unit=$this->getUnites($questionNumber,TRUE);
						if($unit!='') {
							echo "&nbsp;<span class='lighter'>(&nbsp;$unit&nbsp;)</span><br>";
						}
					}
				}
				else {
					//Affiche les résultats de l'étudiant
					//echo '<div class="row"><div class="medium-10 large-8 columns"><div class="panel callout radius"><span class="larger">Réponse donnée: <span class="semiBold">'.$resultats['reponses'][$questionNumber].'</span></span>';
					echo '<div class="panel callout radius"><span class="larger">Réponse donnée: <span class="semiBold">'.$resultats['reponses'][$questionNumber].'</span>';
					if($criteresCorrection['unites']=='fournies') {
						echo '&nbsp;<span class="lighter">'.$this->getUnites($questionNumber,TRUE).'</span>';
					}
					echo '</span>';
					
					switch($criteresCorrection['remises-info']) {
						case 'tout':
							if($resultats['codes'][$questionNumber][0]=='b') {
								echo '<br><span class="semiBold">Bonne réponse</span>';
								if(strpos($resultats['codes'][$questionNumber],'p')) {
									echo ' en fonction des réponses précédentes';
								}
								if(strpos($resultats['codes'][$questionNumber],'s')) {
									echo ' mais mauvais signe ( -'.$criteresCorrection['penalite-signe'].' pt ).';
								}
								else {echo '.';}
							}
							else {
								echo '<br>Mauvaise réponse, la bonne réponse était : <span class="semiBold">'.formateNombre($resultats['bonnes_reponses'][$questionNumber],'s',$this->chiffresSignificatifs($questionNumber)).' '.$this->getUnites($questionNumber,TRUE).'</span>';
							}
							

							if(strpos($resultats['codes'][$questionNumber],'u')) {
								echo '<br>Mauvaises unités ( -'.$criteresCorrection['penalite-unites'].' pt )';
							}
							if(preg_match('/C(\d+)/',$resultats['codes'][$questionNumber],$match)) {
								echo "<br>Il y a $match[1] chiffre(s) significatif(s) de trop ( -".($match[1]*$criteresCorrection['penalite-chiffres-significatifs'])." pt )";
							}
							if(preg_match('/c(\d+)/',$resultats['codes'][$questionNumber],$match)) {
								echo "<br>Il manque $match[1] chiffre(s) significatif(s) ( -".($match[1]*$criteresCorrection['penalite-chiffres-significatifs'])." pt )";
							}
							
						case 'notes':
							echo '<br><span class="larger">Note : <span class="semiBold">'.$resultats['notes'][$questionNumber].'/1</span></span>';
					}
					echo '</div>';//</div></div>';
				}//*/
			}
			
			echo '</div></div>';
			$alpha++;
		}
		
		if(!is_null($resultId)) {
			if(is_null($resultats['note'])) {
				if(!isset($_SESSION['prof'])) {
					//Affiche les anciens résultats.
					if(!is_null($oldResultsId) && $criteresCorrection['remises-info']!='aucune') {
						echo '<div class="row"><div class="medium-8 large-6 columns">';
						if(strtotime($anciensResultats['temps'])>strtotime($criteresCorrection['remise'].' 00:00:00')+2*86400) { $retard='plus'; echo '<div data-alert class="alert-box warning radius">Retard de plus de 24h.</div>'; }
						elseif(strtotime($anciensResultats['temps'])>strtotime($criteresCorrection['remise'].' 00:00:00')+86400) { $retard='moins'; echo '<div data-alert class="alert-box warning radius">Retard de moins de 24h ( -15% ).</div>'; }
				
						echo '<div data-alert class="alert-box info radius larger">Total : <span class="semiBold">'.$anciensResultats['note'].'%</span> <span class="smaller">('.array_sum($anciensResultats['notes']).'/'.count($anciensResultats['notes']).(isset($retard)?' - retard':'').')</span></div></div></div>';
					}
					//Affiche le bouton d'enregistrement
					if(strpos($this->id,'-')) {
						if(is_null($oldResultsId) || time()<strtotime($criteresCorrection['remise'].' 00:00:00')+2*86400) {
							echo "<a href='#' onclick='enregistreReponsesEtudiant($resultId,\"questionnaire".$this->id."\")' class='small button hide-for-print'>Enregistrer les réponses";
							if(!is_null($nombreAFaire)) {echo "<br><span class='smaller'>$nombreAFaire</span>";}
							echo '</a>';
						}
					}
					else {
						echo "<a href='#' onclick='enregistreReponsesEtudiant($resultId)' class='small button hide-for-print'>Enregistrer les réponses";
						if(!is_null($nombreAFaire)) {echo "<br><span class='smaller'>$nombreAFaire</span>";}
						echo '</a>';
					}
					
					
				}
			}
			elseif($criteresCorrection['remises-info']!='aucune') {
				echo '<div class="row"><div class="medium-8 large-6 columns">';
				if(strtotime($resultats['temps'])>strtotime($criteresCorrection['remise'].' 00:00:00')+2*86400) { $retard='plus'; echo '<div data-alert class="alert-box warning radius">Retard de plus de 24h.</div>'; }
				elseif(strtotime($resultats['temps'])>strtotime($criteresCorrection['remise'].' 00:00:00')+86400) { $retard='moins'; echo '<div data-alert class="alert-box warning radius">Retard de moins de 24h ( -15% ).</div>'; }
				
				echo '<div data-alert class="alert-box info radius larger">Total : <span class="semiBold">'.$resultats['note'].'%</span> <span class="smaller">('.array_sum($resultats['notes']).'/'.count($resultats['notes']).(isset($retard)?' - retard':'').')</span></div></div></div>';
				
				if(isset($_SESSION['prof'])) {
					echo "<form><fieldset><legend>Modifier la note / Commentaires</legend><div class='row collapse'>";
					echo "<div class='small-5 columns'><label for='nouvelleNote' class='inline'>Nouvelle note: </label></div><div class='small-4 columns'><input type='text' id='nouvelleNote' value='$resultats[note]'/></div><div class='small-3 columns'><span class='postfix'>%</span></div>";
					echo "<div class='small-12 columns'><label>Commentaire:<textarea id='commentaire'>$resultats[commentaire]</textarea></label></div>";
					echo "</div><div id='messageCommentaire' class='panel radius hide'></div><span class='button radius right' onclick='enregistreCommentaire(\"$resultats[note]\");'>Enregistrer</span></fieldset></form>";
				}
			}
		}
		
		
		if($afficheInstructions) { 
			//Affichage des informations de droite (Instructions, critères de correction et constantes)
			?></div>
			<div class="medium-4 large-offset-1 columns hide-for-print">
				<dl class="accordion" data-accordion>
					<dd>
						<a href="#instructionsPanel">Instructions</a>
						<div id="instructionsPanel" class="content">
							<h4>Chiffres significatifs</h4>
							<p>Vos r&eacute;ponses doivent avoir 3 chiffres significatifs sauf pour les questions sur les incertitudes, dans ce cas les règles d'arrondissement des incertitudes et des valeurs s'appliquent.</p>
	
							<?php if($criteresCorrection['unites']=='demandees') { ?>
							<h4>Unités</h4>
							<p>Vous devez indiquer les unit&eacute;s du syst&egrave;me S.I., avec ou sans pr&eacute;fixe sauf si cela est indiqu&eacute; dans la question.
							Dans le cas d'unités composées, seule la première unité peut avoir un préfixe: par exemple g/m³ ou kg/m³ seront valides mais pas g/cm³. Vous pouvez trouver &#181; en tapant (alt car m).</p>
							
							<p>Si vos unit&eacute;s utilisent un exposant,  vous pouvez utiliser &#178 et &#179 (alt car 8 ou alt car 9), sinon vous devrez indiquer l'exposant en utilisant l'accent circonflexe (sans une lettre sous l'accent), par exemple: m^-1.</p>
							
							<p>Pour les degrés, vous pouvez utiliser le symbole ° ou l'abbréviation «deg».
							<?php } ?>
							
							<h4>Notation scientifique</h4>
							<p>Vous pouvez utiliser la notation scientifique.
							2580m pourrait &ecirc;tre &eacute;crit 2,58x10^3m, 2,58E3m, 2,58e3m ou 2,58km.  </p>
						</div>
					</dd>
					<dd>
						<a href="#criteresPanel">Critères de correction</a>
						<div id="criteresPanel" class="content">
							<p>Chaque question vaut un point.</p>
							<p>Une erreur de signe entraîne une pénalité de <?php echo formateNombre($criteresCorrection['penalite-signe']); ?> point.</p>
							<?php if($criteresCorrection['unites']=='demandees') { echo '<p>Une erreur d\'unités entraîne une pénalité de '.formateNombre($criteresCorrection['penalite-unites']).'  point.</p>'; } ?>
							<p>Trop (ou trop peu) de chiffres significatifs entraîne une pénalité de <?php echo formateNombre($criteresCorrection['penalite-chiffres-significatifs']); ?> point par chiffre de trop (ou manquant).</p>
							<p>La note d'une question ne peut être négative...</p>
						</div>
					</dd>
					<dd>
						<a href="#constantesPanel">Constantes utiles</a>
						<div id="constantesPanel" class="content"> <?php
							foreach($this->constantes as $constante) {
								echo "<p data-tooltip class='has-tip' title=\"$constante[nom]\"><span class='variable'>$constante[variable]</span> = ".formateNombre($constante['valeur'],'s')." $constante[unites]</p>";
							}
						?></div>
					</dd>
				</dl>
			</div></div>
			<script>$(document).foundation();</script>
		<?php }
	}
	
	
	
	
	
	
	private function calculeReponse($reponseString) {
		//ATTENTION: Il peut y avoir des erreurs dans le calcul si on a -{...} avec une valeur négative (l'ordinateur ne donnera pas de résultat)
		if(preg_match('/\{r:(\d+)\}/',$reponseString,$match)) {
			$matchId=$match[1];
			$reponsesMatch=$this->getReponses($matchId);
			$reponseArray=array();
			foreach($reponsesMatch as $reponseMatch) {
				$reponseArray = array_merge($reponseArray, $this->calculeReponse(str_replace('{r:'.$matchId.'}',$reponseMatch,$reponseString)));
			}
			return array_unique($reponseArray);
		}
		else {
			$reponseString = str_replace('--','+',$reponseString);
			return array(eval('return '.$reponseString.';'));
			}
	}
	
	private function afficheVariables($enonce) {
		require_once(dirname(dirname(__FILE__)).'/commun/formateNombre.php');
		if(is_null($this->valeursAleatoires)) { //On n'a pas de valeurs, remplacer par les noms de variables génériques.
			return preg_replace('/\{([a-zA-Z0-9_:]+)\}/','<span class="variable semiBold">$1</span>',$enonce);
		}
	
		if(preg_match_all('/\{c:([a-zA-Z0-9_]+)\}/',$enonce,$results))	{ //Il  a des constantes à remplacer...
			if(is_null($this->constantes)) { $this->constantes=chercheConstantes(); }
			foreach($results[1] as $constantName) {
				$search[]='{c:'.$constantName.'}';
				$replace[]=$this->constantes[$constantName]['valeur'];
			}
		}
	
		//On a des valeurs, il faut juste les formater avant de remplacer
		foreach($this->valeursAleatoires as $nom => $valeur) {
			$search[]='{'.$nom.'}';
			$replace[]=formateNombre($valeur);
		}
		return str_replace($search,$replace,$enonce);
	}
}





function recupereValeurs($valeurs,$separateur='=') {
	//Extrait les valeurs d'une liste
	//Les éléments de la liste sont séparées par un ";"
	//La valeur est séparée de la clef pas $separateur
	//Retourne un tableau
	$valeurs = explode(';',$valeurs);
	foreach($valeurs as $valeur) {
		$valeur = explode($separateur,$valeur);
		$retour[$valeur[0]] = $valeur[1];
	}
	return $retour;
}



function chercheParametreCorrection($prescriptionId,$parametresParticuliers) {
	GLOBAL $mysqli;

	if($prescriptionId[0]=='t') {
		$results = $mysqli->query('SELECT `Theorie-parametres`.`remises-info`, `Theorie-parametres`.`ecart`, `Theorie-parametres`.`penalite-signe`, `Theorie-parametres`.`penalite-chiffres-significatifs`, `Theorie-parametres`.`penalite-unites`, `Theorie-parametres`.`unites`, `Theorie-prescriptions`.`remise`, `Theorie-prescriptions`.`cours` FROM `Theorie-parametres`, `Theorie-prescriptions` WHERE `Theorie-parametres`.`prescription`='.substr($prescriptionId,1).' AND `Theorie-prescriptions`.`index`='.substr($prescriptionId,1));
		
		if(!$resultat=$results->fetch_assoc()) { //Il n'y a pas de paramètres particuliers, il faut recherche les paramètres par défaut.
			$results->free();
			$results = $mysqli->query('SELECT `Theorie-parametres`.`remises-info`, `Theorie-parametres`.`ecart`, `Theorie-parametres`.`penalite-signe`, `Theorie-parametres`.`penalite-chiffres-significatifs`, `Theorie-parametres`.`penalite-unites`, `Theorie-parametres`.`unites`, `Theorie-prescriptions`.`remise`, `Theorie-prescriptions`.`cours` FROM `Theorie-parametres`, `Theorie-prescriptions`, `Categorie-Evaluations` WHERE `Theorie-prescriptions`.`index`='.substr($prescriptionId,1).' AND `Theorie-prescriptions`.`categorie`=`Categorie-Evaluations`.`index` AND `Categorie-Evaluations`.`theorie-params`=`Theorie-parametres`.`index`');
			$resultat=$results->fetch_assoc();
		}

	}
	else {
		$results = $mysqli->query("SELECT `Devoirs-parametres`.`remises-info`, `Devoirs-parametres`.`ecart`, `Devoirs-parametres`.`penalite-signe`, `Devoirs-parametres`.`penalite-chiffres-significatifs`, `Devoirs-parametres`.`penalite-unites`, `Devoirs-parametres`.`unites`, `Devoirs-prescriptions`.`remise`, `Devoirs-prescriptions`.`cours` FROM `Devoirs-parametres`, `Devoirs-prescriptions` WHERE `Devoirs-parametres`.`prescription`=$prescriptionId AND `Devoirs-prescriptions`.`index`=$prescriptionId");
		
		if(!$resultat=$results->fetch_assoc()) { //Il n'y a pas de paramètres particuliers, il faut recherche les paramètres par défaut.
			$results->free();
			$results = $mysqli->query("SELECT `Devoirs-parametres`.`remises-info`, `Devoirs-parametres`.`ecart`, `Devoirs-parametres`.`penalite-signe`, `Devoirs-parametres`.`penalite-chiffres-significatifs`, `Devoirs-parametres`.`penalite-unites`, `Devoirs-parametres`.`unites`, `Devoirs-prescriptions`.`remise`, `Devoirs-prescriptions`.`cours` FROM `Devoirs-parametres`, `Devoirs-prescriptions`, `Categorie-Evaluations` WHERE `Devoirs-prescriptions`.`index`=$prescriptionId AND `Devoirs-prescriptions`.`categorie`=`Categorie-Evaluations`.`index` AND `Categorie-Evaluations`.`devoirs-params`=`Devoirs-parametres`.`index`");
			$resultat=$results->fetch_assoc();
		}
	}
	
	if($parametresParticuliers!='') {
		$parametresParticuliers=explode(';',$parametresParticuliers);
		foreach($parametresParticuliers as $parametre) {
			$parametre=explode('=',$parametre);
			$resultat[$parametre[0]]=$parametre[1];
		}
	}
	
	return $resultat;
}



function chercheConstantes($cours=0) {
	GLOBAL $mysqli;
	
	$result = $mysqli->query("SELECT * FROM `Devoirs-constantes` WHERE `cours`=0 OR `cours`=$cours");
	while($const=$result->fetch_assoc()) {
		if(isset($constantes[$const['symbole']])) {
			if($const['cours']>$constantes[$const['symbole']]['cours']) {
				$constantes[$const['symbole']] = $const;
			}
		}
		else { $constantes[$const['symbole']] = $const; }
	}
	$result->free();
	
	uksort($constantes, "strnatcasecmp");
	
	return $constantes;
}


?>
