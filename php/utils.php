<?php

function buttons($buttons) {
  //Génère des boutons individuels (si $buttons est un array contenant icon,text,address,function)
  //Génère une barre de boutons si $buttons contient plusieurs boutons (un array avec des clés 0,1,2...)
  //<section class="buttonBar"><a class="button"><span class="icon">edit</span> Liste d\'étudiants</a><a class="button"><span class="icon">settings</span><span>Paramètres</span></a></SECTION>
  if(isset($buttons[0])) {
    echo '<section class="buttonBar">';
    foreach($buttons as $b) {buttons($b);}
    echo '</section>'.PHP_EOL;
  }
  else {
    echo '<a class="button" '.( isset($buttons['address']) ? "href='$buttons[address]'" : '' ).'>';
    if(isset($buttons['icon'])) {echo "<span class='icon'>$buttons[icon]</span>";}
    if(isset($buttons['text'])) {echo "<span>$buttons[text]</span>";}
    echo '</a>';
  }
}

function varDump($mixed = null, $name = null) {
  //Version améliorée de var_dump qui ajoute des <pre> pour faciliter la lecture
  echo '<pre>';
  if(!is_null($name)) {echo "$name: <br>";}
  var_dump($mixed);
  echo '</pre>';
  return null;
}

function substrAfter($string,$char) {
  //retourne la partie de $string après le dernier $char
  $pos = strrpos($string, $char);
  if($pos===false) {return $string;}
  else {return substr($string, $pos + 1);  }
}

function substrBefore($string,$char) {
  //retourne la partie de $string avant le premier $char
  $pos = strpos($string, $char);
  if($pos===false) {return $string;}
  else {return substr($string, 0, $pos);}
}

function substrBetween($string,$start,$end) {
  //retourne la partie de $string entre le dernier $start qui est avant le premier $end
  return substrAfter(substrBefore($string,$end),$start);
}
?>
