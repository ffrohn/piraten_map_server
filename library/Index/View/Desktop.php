<!DOCTYPE html> 
<html>
<head>
  <meta http-equiv="Content-type" content="text/html;charset=UTF-8" />
  <title>OpenStreetMap Piraten Karte</title>
  <link rel="stylesheet" href="bootstrap-1.1.0.min.css">
  <link rel="stylesheet" href="style.css">
  <script src="http://code.jquery.com/jquery-1.5.2.min.js"></script>
  <script type="text/javascript" src="js/OpenLayers.php"></script>
  <script type="text/javascript" src="js/map.js"></script>
  <script type="text/javascript" src="js/popups.js"></script>
  <script type="text/javascript">
//<![CDATA[  
    var startPos = <?php print json_encode($this->getInitialPosition()); ?>;
    var posterFlags = <?php print json_encode($this->getPosterFlags()); ?>;
    var isLoggedIn = <?php print (System::getCurrentUser() ? 'true': 'false'); ?>;
  </script>
</head>
 
<body>
<div id="mask"></div>
<div class="topbar">
  <div class="fill">
    <div class="container">
      <h3><a href="#">Plakat Karte</a></h3>
      <ul>
        <li><a class="loginEnabled" href="#" onclick="auth.logout();">Abmelden</a></li>
        <li><a class="logoutEnabled" href="#" onclick="showModalId('loginform');">Anmelden</a></li>
        <li><a class="loginEnabled" href="#" onclick="showModalId('uploadimg');">Bild hochladen</a></li>
        <li><a class="loginEnabled" href="#" onclick="showModalId('exportCity');">Export (beta!)</a></li>
        <li><a href="#" onclick="togglemapkey();">Legende / Hilfe</a></li>
      </ul>
    </div>
  </div>
</div>
  <div style="display:none;" id="dlgBag">
    <div class="modal" id="loginform">
          <div class="modal-header">
            <h3>Anmelden</h3>
      <a href="#" class="close" onclick="closeModalDlg(false);">&times;</a>
          </div>
          <div class="modal-body">
      <form id="formlogin" action="<?php echo System::getConfig('url');?>login.php" method="post">

        <div class="clearfix">
          <label for="username">Benutzer</label>
          <div class="input">
            <input type="text" size="30" class="xlarge" name="username" id="username" />
          </div>
        </div>

        <div class="clearfix">
          <label for="password">Passwort</label>
          <div class="input">
            <input type="password" size="30" class="xlarge" name="password" id="password" />
          </div>
        </div>
        
        <div class="clearfix">
          <label for="mail">Mail</label>
          <div class="input">
            <input type="text" size="30" class="xlarge" name="mail" id="mail" value="Piraten-Adresse zur Registrierung eingeben"/>
          </div>
        </div>
      </form>
          </div>
          <div class="modal-footer">
      <a href="#" class="btn primary" onclick="auth.login();">Anmelden/Account erstellen</a>
      <a href="#" class="btn secondary" onclick="javascript:closeModalDlg(false);">Abbrechen</a>
          </div>
        </div>
    <div class="modal" id="uploadimg">
      <div class="modal-header">
        <h3>Bild hochladen</h3>
        <a href="#" class="close" onclick="javascript:closeModalDlg(false);">&times;</a>
      </div>
      <div class="modal-body">
        <form enctype="multipart/form-data" method="post" id="formimgup" action="image.php">
          <div class="clearfix">
            <label for="image">Bild hochladen</label>
            <div class="input">
              <input type="file" id="image" name="image" class="xlarge">
            </div>
          </div>
          <input type="hidden" name="completed" value="1">
        </form>
      </div>
      <div class="modal-footer">
        <a href="#" class="btn primary" onclick="javascript:document.forms['formimgup'].submit();">Hochladen</a>
        <a href="#" class="btn secondary" onclick="javascript:closeModalDlg(false);">Abbrechen</a>
      </div>
    </div>
    <div class="modal" style="position: relative; top: auto; left: auto; margin: 0 auto; display:none;"
       id="exportCity">
      <div class="modal-header">
        <h3>Plakate exportieren</h3>
        <a href="#" class="close" onclick="javascript:closeModalDlg(false);">&times;</a>
      </div>
      <div class="modal-body">
        <form enctype="multipart/form-data" method="get" id="formexpup" action="export.php">
          <div class="clearfix">
            <label for="image">Welche Stadt?</label>
            <div class="input">
              <select id="city" name="city">
<?php
$cities = System::query('SELECT DISTINCT city FROM ' . System::getConfig('tbl_prefix') . 'felder');
if ($cities) {
  while ($row = $cities->fetch_assoc())
    print '<option>' . $row['city'] . '</option>';
}
?>
            </div>
          </div>
          <input type="hidden" name="completed" value="1">
        </form>
      </div>
      <div class="modal-footer">
        <a href="#" class="btn primary" onclick="javascript:document.forms['formexpup'].submit();">Download</a>
        <a href="#" class="btn secondary" onclick="javascript:closeModalDlg(false);">Abbrechen</a>
      </div>
    </div>
  </div>
  </div>
    <div class="alert-message info" id="message" style="margin-top:43px">
    <a class="close" href="#" onclick="javascript:closeMsg();">&times;</a>
        <p></p>
      </div>
    <?php if (System::getCurrentUser() && System::getConfig('show_last_x_changes') > 0) {?>
  <div style="position:absolute; top:50px; bottom:30px; width:150px; right:0px;" id="log" >
    <?php
      $res = System::query("SELECT plakat_id as id,user,timestamp,subject,what FROM ".System::getConfig('tbl_prefix')."log ORDER BY timestamp DESC LIMIT ?", 'd', System::getConfig('show_last_x_changes'));
      $num = mysql_num_rows($res);

      while ($row = $res->fetch_assoc())
      {
        echo $row["timestamp"] . " (". $row["user"] . "):<br>";
        switch ($row["subject"])
        {
          case 'add':
            echo "Neues Plakat: ".$row["id"];
            break;
          case 'del':
            echo "Plakat ".$row["id"]." gelöscht.";
            break;
          case 'change':
            echo "Plakat ".$row["id"]." geändert: ".$row["what"];
        }
        
        echo "<br>";
      }
    }?>
  </div>
  <?php 
  $mapmarginright = 0;
  if (System::getConfig('show_last_x_changes') > 0)
    $mapmarginright = 150;
  ?>
  <div style="position:absolute; top: 40px; bottom:0px; left:0px; right:<?php echo $mapmarginright?>px;" id="map" ></div>
  <div id="mapkey">
  
    <div class="modal" style="position: relative; top: auto; left: auto; margin: 0 auto; width: 256px;">
          <div class="modal-header">
            <h3>Legende</h3>
      <a href="#" onclick="javascript:togglemapkey();" class="close">&times;</a>
          </div>
          <div class="modal-body">
      <ul class="unstyled">
        <li>Anmelden: Zum Anmelden muss eine Email-Adresse mit "...@piraten..." angegeben werden. Dahin kommen dann die Login-Daten.</li>
        <li>Plakate werden erst nach dem Login editierbar.</li>
        <li>Du bist noch nicht angemeldet</li>
        <li>Bei Fragen bitte auf https://wiki.piratenpartei.de/Plakatkarte_NRW schauen. Notfalls Mail an pk@piraten-aachen.de.</li>
        <li>STRG+Mausklick: neuer Marker</li>
      <ul>
<?php foreach ($this->getPosterFlags() as $key=>$value)
{
  if ($value!="") {
?>
    <li><img  style="vertical-align:text-top;" src="./images/markers/<?php echo $key?>.png" width="20" alt="<?php echo $key?>" />=<?php echo $value?></li>
<?php
  }
} ?></ul>
      </ul>
          </div>
        </div>
  </body>
</html>
