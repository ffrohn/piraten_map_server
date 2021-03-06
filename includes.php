<?php
/*
       Licensed to the Apache Software Foundation (ASF) under one
       or more contributor license agreements.  See the NOTICE file
       distributed with this work for additional information
       regarding copyright ownership.  The ASF licenses this file
       to you under the Apache License, Version 2.0 (the
       "License"); you may not use this file except in compliance
       with the License.  You may obtain a copy of the License at

         http://www.apache.org/licenses/LICENSE-2.0

       Unless required by applicable law or agreed to in writing,
       software distributed under the License is distributed on an
       "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
       KIND, either express or implied.  See the License for the
       specific language governing permissions and limitations
       under the License.
*/

include "settings.php";

function get_inner_html( $node ) { 
    $innerHTML= ''; 
    $children = $node->childNodes; 
    foreach ($children as $child) { 
        $innerHTML .= $child->ownerDocument->saveXML( $child ); 
    } 

    return $innerHTML; 
} 

$snoopy = new Snoopy;

if (System::getConfig('use_ssl')) {
  $snoopy->curl_path=System::getConfig('curl_path');
  $wikiPath = "https://wiki.piratenpartei.de";
} else {
  $snoopy->curl_path=false;
  $wikiPath = "http://wiki.piratenpartei.de";
}
$apiPath = "$wikiPath/wiki/api.php";

session_start();



$options = array("default"=>"",
	"plakat_ok"=>'Plakat hängt',
	"plakat_a0"=>'A0-Plakat steht',
	"plakat_dieb"=>'Plakat wurde gestohlen',
	"plakat_niceplace"=>'Gute Stelle für ein Plakat',
	"plakat_wrecked"=>'Plakat beschädigt',
	"wand"=>'Plakatwand der Gemeinde',
	"wand_ok"=>'Plakat an der Plakatwand');

$image_upload_typ = 'plakat_ok';

if ($_SESSION['siduser'] || $_SESSION['sidip']) {
	// Check if the session is still valid.
	if ($_SESSION['wikisession']) {
		$snoopy->cookies = $_SESSION['wikisession'];

		$request_vars = array('action' => 'query', 'meta' => 'userinfo',  'format' => 'php');
		if(!$snoopy->submit($apiPath, $request_vars))
			die("Snoopy error: {$snoopy->error}");
		$array = unserialize($snoopy->results);

		if ($_SESSION['siduser'] == $array['query']['userinfo']['name'] && $_SESSION['sidip']==$_SERVER["REMOTE_ADDR"])
			$loginok=1;
		else
		{
			$loginok=0;
			unset($_SESSION['siduser']);
			unset($_SESSION['wikisession']);
			unset($_SESSION['sidip']);
		}
	} else {
		if ($_SESSION['sidip']==$_SERVER["REMOTE_ADDR"])
			$loginok=1;
		else
		{
			$loginok=0;
			unset($_SESSION['siduser']);
			unset($_SESSION['wikisession']);
			unset($_SESSION['sidip']);
		}
       }
}

function get_float($name) {
  return filter_input(INPUT_GET, $name, FILTER_VALIDATE_FLOAT);
}

function get_int($name) {
  return filter_input(INPUT_GET, $name, FILTER_VALIDATE_INT);
}
function get_typ($typ) {
	global $options;
	$t = $_GET[$typ];
	if (!($t))
		return null;
	foreach($options as $key=>$value) {
		if ($t == $key) {
			return $t;
		}
	}
}

function map_add($lon, $lat, $typ) {
	global $_SESSION;
	
	$src = new DOMDocument('1.0', 'utf-8');
	$src->formatOutput = true;
	$src->preserveWhiteSpace = false;
	$src->load("http://nominatim.openstreetmap.org/reverse?format=xml&zoom=18&addressdetails=1&lon=".$lon."&lat=".$lat);
	$city = get_inner_html($src->getElementsByTagName('city')->item(0));
	$street = get_inner_html($src->getElementsByTagName('road')->item(0));
	
	if ($typ != '')
	  $felder = System::query("INSERT INTO ".System::getConfig('tbl_prefix')."felder (lon,lat,user,type,city,street) VALUES (?, ?, ?, ?, ?, ?)", 
                                  array($lon, $lat, $_SESSION['siduser'], $typ, $city, $street));
	else
	  $felder = System::query("INSERT INTO ".System::getConfig('tbl_prefix')."felder (lon,lat,user,city,street) VALUES (?, ?, ?, ?, ?)",
                                  array($lon, $lat, $_SESSION['siduser'], $city, $street));
	
	$plakat = System::query("INSERT INTO ".System::getConfig('tbl_prefix')."plakat (actual_id, del) VALUES(?, false)", array($felder));
	System::query("UPDATE ".System::getConfig('tbl_prefix').'felder SET plakat_id = ? WHERE id = ?', array($plakat, $felder));
	$res = System::query("INSERT INTO ".System::getConfig('tbl_prefix')."log (plakat_id, user, subject) VALUES(?, ?, 'add')", array($plakat, $_SESSION['siduser']));
	return $plakat;
}

function map_del($id) {
	global $_SESSION;
	
	$res = System::query("UPDATE ".System::getConfig('tbl_prefix')."plakat SET del = true where id = ?", $id);
	
	$res = System::query("INSERT INTO ".System::getConfig('tbl_prefix')."log (plakat_id, user, subject) VALUES(?, ?, 'del')", array($id, $_SESSION['siduser']));
	return;
}

function map_change($id, $type, $comment, $city, $street, $imageurl)
{
  global $_SESSION, $options;
  if (!isset($options[$type])) {
    $type = null;
  }

  $query = "INSERT INTO ".System::getConfig('tbl_prefix')."felder (plakat_id, lon, lat, user, type, comment, city, street, image) "
    . "SELECT plakat_id, lon, lat, ? as user, IFNULL(?, type) type, IFNULL(?, comment) comment, IFNULL(?, city) city, IFNULL(?, street) street, IFNULL(?, image) image "
    . "FROM ".System::getConfig('tbl_prefix')."felder f "
    . "JOIN ".System::getConfig('tbl_prefix')."plakat p ON p.actual_id=f.id "
    . "WHERE p.id=?";

  $newid = System::query($query, array($_SESSION['siduser'], $type, $comment, $city, $street, $imageurl, $id));

  $res = System::query("INSERT INTO ".System::getConfig('tbl_prefix')."log (plakat_id, user, subject, what) VALUES(?, ?, 'change', ?)", array($id, $_SESSION['siduser'], 'Type: ' . $type));

  System::query("UPDATE ".System::getConfig('tbl_prefix')."plakat SET actual_id = ? where id = ?", array($newid, $id));

  return;
}
