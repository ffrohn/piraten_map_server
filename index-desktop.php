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
?><!DOCTYPE html 
   PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
<head>
  <meta http-equiv="Content-type" content="text/html;charset=UTF-8" />
  <title>OpenStreetMap Piraten Karte</title>
  <link rel="stylesheet" href="bootstrap-1.1.0.min.css">
  <style type="text/css">
  <!--
  .photo {
    height: 120px;
  }
  #mask {
    position:absolute;
    z-index:10100;
    background-color:#888;
    display:none;
  }
  #mapkey {
    position:absolute;
    z-index:3000;
    bottom:0px; 
    left:0px; 
    display:none;
  }
  .topbar a {
    font-weight: bold;
  }
  -->
  
  
  </style>
  <script src="http://code.jquery.com/jquery-1.5.2.min.js"></script>
  <script type="text/javascript" src="./js/OpenLayers.php"></script>
 
  <script type="text/javascript">
//<![CDATA[  
<?php
$lat = get_float('lat');
$lon = get_float('lon');
$zoom = get_int('zoom');

if ($lat)
  echo "var lat = ".json_encode($lat).";";
else if ($_SESSION['deflat'])
  echo "var lat = ".json_encode($_SESSION['deflat']).";";
else
  echo "var lat = 51.17;";
if ($lon)
  echo "var lon = ".json_encode($lon).";";
else if ($_SESSION['deflon'])
  echo "var lon = ".json_encode($_SESSION['deflon']).";";
else
  echo "var lon = 6.95;";
if ($zoom)
  echo "var zoom = ".json_encode($zoom).";";
else if ($_SESSION['defzoom'])
  echo "var zoom = ".json_encode($_SESSION['defzoom']).";";
else
  echo "var zoom = 8;";
?> 
    
    var map;
    var gmlLayers = new Array();
 
    function makeAJAXrequest(url, data) {
      $.ajax({
        url: url,
        data: data,
        success: function(msg){
          gmlreload();
        }
      });
    }
    
    function closeModal() {
      if (selectedFeature != null) {
        sf = selectedFeature;
        selectedFeature = null;
        closeModalDlg(true);
        selectControl.unselect(sf);
      }
    }
    
    function closeModalDlg(shouldRemove) {
      $('#mask').fadeTo("fast",0, function() {$(this).css('display', 'none')});  
      $('body > .modal').fadeOut(function() { 
        $(this).remove(); 
        if(!shouldRemove)
          $('#dlgBag').append($(this));
      }); 
    }
    
    function showModal(content) {
      var maskHeight = $(window).height();
      var maskWidth = $(window).width();
     
      //Set height and width to mask to fill up the whole screen
      $('#mask').css({'width':maskWidth,'height':maskHeight});
       
      //transition effect         
      $('#mask').fadeTo("fast",0.8);  
      //Get the window height and width
      var winH = $(window).height();
           
      $('body').append(content);
      //Set the popup window to center
      $('body > .modal')
        .css('z-index', '10101')
        .css('top',  maskHeight/2-$('body > .modal').height()/2)
        .fadeIn();
    }
    
    function showModalId(id) {
      showModal($('#'+id));
    }
 
    <?php include "popups.php" ?>
 
    function getGML(filter, display) {
      if (!display)
        display = "Unbearbeitet";

      var filterurl = "./kml.php?filter="+filter;
      
      var mygml = new OpenLayers.Layer.Vector(display, {
        projection: map.displayProjection,
        strategies: [
          new OpenLayers.Strategy.BBOX()
        ],
        protocol: new OpenLayers.Protocol.HTTP({
          url: filterurl,
          format: new OpenLayers.Format.KML({
                        extractStyles: true, 
                        extractAttributes: true
                    }),
        })
      });

      map.addLayer(mygml);

      return mygml;
    }

    //Initialise the 'map' object
    function init() {
      OpenLayers.ImgPath = "./theme/default/";
      var options = {
        controls:[
          new OpenLayers.Control.Navigation(),
          new OpenLayers.Control.PanZoomBar(),
          new OpenLayers.Control.Attribution(),
          new OpenLayers.Control.LayerSwitcher({
            roundedCornerColor: 'black'
          }),
          new OpenLayers.Control.Permalink()],
        maxResolution: 156543.0399,
        maxExtent: new OpenLayers.Bounds(-2037508.34,-2037508.34,2037508.34,2037508.34),
        numZoomLevels: 19,
        units: 'm',
        projection: new OpenLayers.Projection("EPSG:900913"),
        displayProjection: new OpenLayers.Projection("EPSG:4326")
      };    
    
      map = new OpenLayers.Map ("map",  options );
      layerMapnik = new OpenLayers.Layer.OSM.Mapnik("Mapnik");
      map.addLayer(layerMapnik);
      layerTilesAtHome = new OpenLayers.Layer.OSM.Osmarender("Osmarender");
      map.addLayer(layerTilesAtHome);
      layerCycleMap = new OpenLayers.Layer.OSM.CycleMap("CycleMap");
      map.addLayer(layerCycleMap);

      var control = new OpenLayers.Control();
      OpenLayers.Util.extend(control, {
        draw: function () {
          this.point = new OpenLayers.Handler.Point( control,
            {"done": this.notice},
            {keyMask: OpenLayers.Handler.MOD_CTRL});
          this.point.activate();
        },
        notice: function (point) {
          lonlat = point.transform(
            map.getProjectionObject(),new OpenLayers.Projection("EPSG:4326"));

          makeAJAXrequest("./kml.php", {
            "action": "add",
            "lon": lonlat.x,
            "lat" :lonlat.y 
          });
        }
      });
      map.addControl(control);
  
      <?php
      foreach ($options as $key=>$value)
      {
      ?>
        gmlLayers.push(getGML('<?php echo $key ?>','<?php echo $value ?>'));
      <?php
      }
      ?>

      selectControl = new OpenLayers.Control.SelectFeature(gmlLayers,
            {onSelect: onFeatureSelect, onUnselect: onFeatureUnselect});
      
      map.addControl(selectControl);
      selectControl.activate();

      var lonLat = new OpenLayers.LonLat(lon, lat).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject());
      map.setCenter (lonLat, zoom);       
    }
    
  function onFeatureUnselect(feature) {
    closeModal();
  }
    
  function onFeatureSelect(feature) {
    selectedFeature = feature;
    showModal(createPopup(feature.attributes.description));
  }
  
  function delid(id){
    selectControl.unselect(selectedFeature);
    makeAJAXrequest("./kml.php", {"action":"del", "id":id});
  }

  function change(id){
    makeAJAXrequest("./kml.php", {
      "id"      : id,
      "action"  : "change",
      "type"    : document.getElementById('typ['+id+']').value,
      "comment" : document.getElementById('comment['+id+']').value,
      "city"    : document.getElementById('city['+id+']').value,
      "street"  : document.getElementById('street['+id+']').value,
      "image"   : document.getElementById('image['+id+']').value
    });
    selectControl.unselect(selectedFeature);
  }
  function gmlreload(){
    for(var i = 0; i < gmlLayers.length; i++) {
      var val = gmlLayers[i];
      //setting loaded to false unloads the layer//
      val.loaded = false;
      //setting visibility to true forces a reload of the layer//
      val.setVisibility(true);
      //the refresh will force it to get the new KML data//
      val.refresh({ force: true, params: { 'random': Math.random()} });
    }
  }
  
  function togglemapkey() {
    show = $('#mapkey').css('display') == 'none';
    if (show)
       $('#mapkey').fadeIn();
    else
       $('#mapkey').fadeOut(function() { $('#mapkey').css('display', 'none') });
  }
  
  function closeMsg() {
     $('#message').fadeOut(function() { $(this).remove() });
     $('#map').animate({top: '40px'});
  }
  
  $(document).ready(function(e) {
    init();
    $(window).resize(function() {
      var maskHeight = $(window).height();
      var maskWidth = $(window).width();
      $('#mask').css({'width':maskWidth,'height':maskHeight});
      $('body > .modal').css('top',  maskHeight/2-$('body > .modal').height()/2);
    });<?php if ($_GET['message']) { ?>
    setTimeout("closeMsg()", 2500);
    <?php } ?>
  });
//]]>
  </script>
</head>
 
<body>
<div id="mask"></div>

<div class="topbar">
  <div class="fill">
    <div class="container">
      <h3><a href="#">Plakat Karte</a></h3>
      <ul>
      <?php if ($loginok != 0) { ?>
        <form id="formLogout" action="<?php echo System::getConfig('url');?>login.php?action=logout" method="post"></form>
    <li><a href="#" onclick="document.forms['formLogout'].submit()">Abmelden</a></li>
    <li><a href="#" onclick="showModalId('uploadimg');">Bild hochladen</a></li>
    <li><a href="#" onclick="showModalId('exportCity');">Export (beta!)</a></li>
    <?php } else { ?>
      <li><a href="#" onclick="showModalId('loginform');">Anmelden</a></li>    
    <?php } ?>
      <li><a href="#" onclick="togglemapkey();">Legende / Hilfe</a></li>
          </ul>
        </div>
      </div> <!-- /fill -->
    </div> <!-- /topbar -->
  <div style="display:none;" id="dlgBag">
  <?php if ($loginok == 0) { ?>
    <div class="modal" style="position: relative; top: auto; left: auto; margin: 0 auto; display:none;"
       id="loginform">
          <div class="modal-header">
            <h3>Anmelden</h3>
      <a href="#" class="close" onclick="javascript:closeModalDlg(false);">&times;</a>
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
            <input type="text" size="30" class="xlarge" name="mail" id="mail" value="Piraten-Email nur bei Registrierung eingeben"/>
          </div>
        </div>
      </form>
          </div>
          <div class="modal-footer">
      <a href="#" class="btn primary" onclick="javascript:document.forms['formlogin'].submit();">Anmelden/Account erstellen</a>
      <a href="#" class="btn secondary" onclick="javascript:closeModalDlg(false);">Abbrechen</a>
          </div>
        </div>
  <?php } else {?>
    <div class="modal" style="position: relative; top: auto; left: auto; margin: 0 auto; display:none;"
       id="uploadimg">
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
  <?php } ?>
  </div>
  </div>
  </div>
  <?php
  if ($_GET['message'])
  {
  ?>
    <div class="alert-message info" id="message" style="margin-top:43px">
    <a class="close" href="#" onclick="javascript:closeMsg();">&times;</a>
        <p><?php echo $_GET['message']?></p>
      </div>
  <?php
  }
  ?>
    <?php if (System::getConfig('show_last_x_changes') > 0) {?>
  <div style="position:absolute; top:50px; bottom:30px; width:150px; right:0px;" id="log" >
    <?php if ($loginok) {
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
  <?php } 
  $mapmarginright = 0;
  if (System::getConfig('show_last_x_changes') > 0)
    $mapmarginright = 150;
  $mapmargintop = 40;
  if ($_GET['message'])
    $mapmargintop = 81;
  ?>
  <div style="position:absolute; top:<?php echo $mapmargintop?>px; bottom:0px; left:0px; right:<?php echo $mapmarginright?>px;" id="map" ></div>
  <div id="mapkey">
  
    <div class="modal" style="position: relative; top: auto; left: auto; margin: 0 auto; width: 420px;">
          <div class="modal-header">
            <h3>Legende</h3>
      <a href="#" onclick="javascript:togglemapkey();" class="close">&times;</a>
          </div>
          <div class="modal-body">
      <ul class="unstyled">
        <?php if ($loginok==0) { ?>
        <li><b>Du bist noch nicht angemeldet</b></li>
        <li>Anmelden: Zum Anmelden muss eine Email-Adresse mit "...@piraten..." angegeben werden. Dahin kommen dann die Login-Daten.</li>
        <li>Plakate werden erst nach dem Login editierbar.</li>
        <li>Bei Fragen bitte auf https://wiki.piratenpartei.de/Plakatkarte_NRW schauen.<br />Notfalls Mail an pk@piraten-aachen.de.</li>
        <? } else {  ?>
        <li>STRG+Mausklick: neuer Marker</li>
        <?php } ?>
        <ul>
<?php foreach ($options as $key=>$value)
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
