<?php
require_once(dirname(dirname(__FILE__)).'/php/sqlconfig.php');

class etudiant {
	private $matricule;
	private $nom;
    private $cours;
    
    function __construct($matricule,$nom=NULL,$cours=NULL) {
        $this->matricule=$matricule;
        if(!is_null($nom)) {$this->data=$nom;}
        if(!is_null($cours)) {$this->cours=$cours;}
    }
    
    function matricule() {return $this->matricule;}
    
    function nom() {
        if(is_null($this->nom)) {
            global $mysqli;
            $result = $mysqli->query('SELECT `nom`, `prenom` FROM `Etudiants` WHERE `matricule`='.$this->matricule);
            $this->nom = $result->fetch_assoc();
        }
        return $this->nom['nom'].', '.$this->nom['prenom'];
    }
}
?>