<?php

class ov2file
{
  // ov2file
  // (c) 2006 Sid Baldwin
  // Created on 06-Feb-2006
  var $content = "";
  var $filename = "default.ov2";
  function add_POI($lat,$long,$text) {
    $this -> content .= "\x02";
    $this -> content .= pack("I", 14 + strlen($text));
    $this -> content .= pack("i", round($long*100000));
    $this -> content .= pack("i", round($lat*100000));
    $this -> content .= $text;
    $this -> content .= "\x00";
    return;
  }
}

function setHeaders($filename){
  header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // some day in the past
  header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
  header("Content-type: application/x-download");
  header("Content-Disposition: attachment; filename={$filename}");
  header("Content-Transfer-Encoding: binary");
}

$poiFile = new ov2file();
$poiFile->filename = "touringcars.ov2";

// HALTES
$json = file_get_contents("http://api.tourbuzz.nl/haltes/");
$rs = json_decode($json);
foreach ($rs->haltes as $halte) {
        //$halte->naam = str_replace(",", "/", $halte->naam);
        //echo "{$halte->coordinaten->lng},\t{$halte->coordinaten->lat},\t{$halte->haltenummer},\thalte\n";
	$poiFile->add_POI($halte->coordinaten->lat, $halte->coordinaten->lng, $halte->haltenummer);
}

// PARKEERPLAATSEN
$json = file_get_contents("http://api.tourbuzz.nl/parkeerplaatsen/");
$rs = json_decode($json);
foreach ($rs->parkeerplaatsen as $parkeerplaats) {
        if (!$parkeerplaats->naam) $parkeerplaats->naam = $parkeerplaats->nummer;
        //$parkeerplaats->naam = str_replace(",", "/", $parkeerplaats->naam);
        //echo "{$parkeerplaats->coordinaten->lng},\t{$parkeerplaats->coordinaten->lat},\t{$parkeerplaats->naam},\tparkeerplaats\n";
	$poiFile->add_POI($parkeerplaats->coordinaten->lat, $parkeerplaats->coordinaten->lng, $parkeerplaats->naam);
}

// ATTRACTIES
$json = file_get_contents("http://api.tourbuzz.nl/attracties/");
$rs = json_decode($json);
foreach ($rs->attracties as $attractie) {
        //$attractie->naam = str_replace(",", "/", $attractie->naam);
        //echo "{$attractie->coordinaten->lng},\t{$attractie->coordinaten->lat},\t{$attractie->naam},\tattractie\n";
	$poiFile->add_POI($attractie->coordinaten->lat, $attractie->coordinaten->lng, $attractie->naam);
}

setHeaders($poiFile->filename);
echo $poiFile->content;
