<?php

function varDump($mixed = null) {
  //Version améliorée de var_dump qui ajoute des <pre> pour faciliter la lecture
  echo '<pre>';
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
