<?php
class note {
    private $pourcent;
    private $valeur;
    private $denominateur;
    private $visible;
    private $retiree;

    
    function __construct($note,$flags='') {
        /********************************************************
         *** $note: un pourcentage ou une fraction xx/yy      ***
         *** $flags: contient les caractéristiques de la note ***
         ***         -cachée: la note ne sera pas visible     ***
         ***         -retiree: la note ne copte pas ds la moy ***
         ********************************************************/
        if($note==='') { $this->pourcent=''; }
        else {
            $note = explode('/',$note);
            if(count($note)==1) { $this->pourcent = $note[0]; } //La note fournie est un pourcentage
            else { $this->valeur=$note[0]; $this->denominateur=$note[1]; $this->pourcent=100*$note[0]/$note[1]; } //La note fournie est une fraction xx/yy
        }
        
        if(stripos($flags,'cac')) {$this->visible=False;}
        else {$this->visible=True;}
        if(stripos($flags,'ret') || stripos($flags,'eli')) {$this->retiree=True;}
        else {$this->retiree=False;}
    }
    
    function pourcent($decimals='') {
        if($decimals==='') {return $this->pourcent;}
        else { return formateNombre($this->pourcent(),'d',$decimals); }
    }
    function valeur() {return $this->valeur;}
    function denominateur() {return $this->denominateur;}
    
    function retire() {$this->retiree=True;}
    
    function affiche($decimals=1,$format='') {
        if($this->pourcent==='') { return ''; }
        if(!$this->visible) { return 'Note enregistrée'; }

        require_once(dirname(dirname(__FILE__)).'/commun/formateNombre.php');
        
        if($this->retiree) { $debut='<span class="eliminer">'; $fin='</span>';}
        elseif(stripos($format,'b')===0 && $this->pourcent>=0) {$debut='+'; $fin='';}
        else {$debut=''; $fin='';}
        
        $debut.=formateNombre($this->pourcent(),'d',$decimals).'%';
        
        if(!is_null($this->valeur)) {
            if(stripos($format,'v')===0) {$debut.='<br>';}
            else {$debut.=' ';}
            if($format!='') {$debut.='<span class="lighter">('.formateNombre($this->valeur,'d',$decimals).'/'.$this->denominateur.')</span>';}
        }
        
        return $debut.$fin;//*/
    }
}
?>