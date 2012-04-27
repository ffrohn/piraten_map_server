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
 require_once('library/System.php');
 System::init();
  require_once("includes.php");
  
  function logout()
  {
      global $snoopy, $apiPath, $_SESSION;
      if ($_SESSION['wikisession']) {
          $snoopy->cookies = $_SESSION['wikisession'];
          
          $request_vars = array('action' => 'logout', 'format' => 'php');
          if (!$snoopy->submit($apiPath, $request_vars))
              die("Snoopy error: {$snoopy->error}");
      }
      $loginok = 0;
      unset($_SESSION['siduser']);
      unset($_SESSION['wikisession']);
      unset($_SESSION['sidip']);
  }
  
  
  
  function login($username, $password, $mail)
  {
      global $apiPath, $snoopy, $_SESSION;

      if ($username == "") return "Wrong password";
      $res = System::query("SELECT username, password FROM " . System::getConfig('tbl_prefix') . 'users WHERE username=?', array($username));
      if ($res->num_rows == 0) return createAccount($username, md5($password), $mail);
      $res = System::query("SELECT username, password FROM " . System::getConfig('tbl_prefix') . 'users WHERE username=? AND active=true', array($username));
      if ($res->num_rows == 0) return "Account not yet activated";
      $res = System::query("SELECT id, username, password FROM " . System::getConfig('tbl_prefix') . 'users WHERE username=? AND password=? AND active=true', array($username, md5($password)));
      if ($res->num_rows == 0) {
        return "Wrong password";
      }
      $entry = $res->fetch_assoc();
      $_SESSION['siduserid'] = $entry['id'];
      $_SESSION['siduser'] = $entry['username'];
      $_SESSION['sidip'] = $_SERVER["REMOTE_ADDR"];
      
      // Try to get the users location...
      if ($_SESSION['siduser']) {
          $request_vars = array('action' => 'query', 'prop' => 'categories', 'titles' => 'Benutzer:' . $_SESSION['siduser'], 'format' => 'php');
          if ($snoopy->submit($apiPath, $request_vars)) {
              $array = unserialize($snoopy->results);
              $categories = array('Germany');
              if (($array) && ($array['query']) && ($array['query']['pages'])) {
                  $pages = $array['query']['pages'];
                  reset($pages);
                  while (list($key, $val) = each($pages)) {
                      if (($val) && ($val['categories'])) {
                          $cats = $val['categories'];
                          reset($cats);
                          while (list($k, $cat) = each($cats)) {
                              if (($cat) && ($cat['title']))
                                  $categories[] = $cat['title'];
                          }
                      }
                  }
              }
              $query = "SELECT lat, lon,zoom FROM " . System::getConfig('tbl_prefix') . "regions WHERE category in (?" . str_repeat(', ?', count($categories) - 1) . ") order by zoom desc limit 1";
              $res = System::query($query, $categories);
              if ($res->num_rows == 1) {
		$entry = $res->fetch_assoc();
                  $_SESSION['deflat'] = $entry['lat'];
                  $_SESSION['deflon'] = $entry['lon'];
                  $_SESSION['defzoom'] = $entry['zoom'];
              }
          }
      }
	  return "Login OK";
  }
  
  function createAccount($username, $password, $mail){
	  if (!strstr($mail, '@piraten')) return "eMail-Addresse muss @piraten enthalten";
	  $res = System::query("SELECT id FROM ".System::getConfig('tbl_prefix')."users WHERE username=?", array($username)) OR dieDB();
	  if ($res->num_rows > 0) return "Username already exists";
	  $date = new DateTime();
	  $hash = md5($date->getTimestamp().$username);
	  System::query("INSERT INTO ".System::getConfig('tbl_prefix') . 'users (username, password, hash) VALUES(?, ?, ?)', 'sss', array($username, $password, $hash)) OR dieDB();
	  $header = 'From: noreply@piratenpartei-aachen.de';
	  if (mail($mail, "Piraten Karte Account", "Hallo, um die Registrierung auf http://pk.piratenpartei-nrw.de abzuschliessen, klicke bitte auf den Link unten. Bei Fragen bitte zuerst im Wiki schauen: http://wiki.piratenpartei.de/Plakatekarte_NRW. \r\n".
			$_SERVER["SERVER_NAME"].$_SERVER['PHP_SELF']."?action=activate&hash=".$hash."&username=".$username, $header))
		return "Account created";
	  else return "Delivering mail failed";
  }
  
  function activateAccount($hash, $username){
	  $res = System::query("SELECT id FROM ".System::getConfig('tbl_prefix')."users WHERE username=? AND hash=?", array($username, $hash)) OR dieDB();
	  if ($res->num_rows != 1) return;
	  System::query("UPDATE ".System::getConfig('tbl_prefix')."users set active=true WHERE username=? AND hash=?", array($username, $hash)) OR dieDB();
	  header("Location: ./?message=Account%20activated");
  }
	  
  
  if ($_GET['action'] == 'logout') {
      logout();
      header("Location: ./?message=Logout%20OK");
  } else if ($_GET['action'] == 'activate') {
	  activateAccount($_GET['hash'], $_GET['username']);
  } else {
      if (isset($_POST['username']) && isset($_POST['password']) && isset($_POST['mail'])) {
		  $res = login($_POST['username'], $_POST['password'], $_POST['mail']);
		  header("Location: ./?message=".htmlspecialchars($res));
      }
  }
?>
