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
  if (selectedFeature == null) {
    return;
  }
  sf = selectedFeature;
  selectedFeature = null;
  closeModalDlg(true);
  selectControl.unselect(sf);
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
  for (var i in posterFlags) {
    gmlLayers.push(getGML(i, posterFlags[i]));
  }

  selectControl = new OpenLayers.Control.SelectFeature(gmlLayers,
        {onSelect: onFeatureSelect, onUnselect: onFeatureUnselect});
  
  map.addControl(selectControl);
  selectControl.activate();

  var lonLat = new OpenLayers.LonLat(startPos.lon, startPos.lat).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject());
  map.setCenter (lonLat, startPos.zoom);
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

var message = {
  display: function(msg) {
    $('#message').find('p').text(msg)
    $('#message').fadeIn();
    $('#map').animate({down: '40px'});
    setTimeout(message.close, 3000);
  },
  close: function() {
    $('#message').fadeOut();
    $('#map').animate({top: '40px'});
  }
};

var auth = {
  isLoggedIn: false,

  goToLoggedInState: function()
  {
    init();
    $('#logoutBtn').css({display: "block"});
    $('#uploadBtn').css({display: "block"});
    $('#loginBtn').css({display: "none"});
    this.isLoggedIn = true;
  },

  goToLoggedOutState: function()
  {
    init();
    $('#logoutBtn').css({display: "none"});
    $('#uploadBtn').css({display: "none"});
    $('#loginBtn').css({display: "block"});
    this.isLoggedIn = false;
  },

  login: function()
  {
    $.ajax({
      type: "POST",
      url: "login.php",
      data: $('#formlogin').serialize(),
      dataType: 'json',
      success: function(data) {
        message.display(data.message);
        if (data.success) {
          auth.goToLoggedInState();
        }
      }
    });
    closeModalDlg(false);
  },

  logout: function() {
    $.ajax({
      type: "GET",
      url: "login.php",
      data: "?action=logout",
      dataType: 'json',
      success: function(data) {
        message.display(data.message);
        if (data.success) {
          auth.goToLoggedOutState();
        }
      }
    });
    closeModalDlg(false);
  }
}
$(document).ready(function(e) {
  if (isLoggedIn)
    auth.goToLoggedInState();
  else
    auth.goToLoggedOutState();
  $(window).resize(function() {
    var maskHeight = $(window).height();
    var maskWidth = $(window).width();
    $('#mask').css({'width':maskWidth,'height':maskHeight});
    $('body > .modal').css('top',  maskHeight/2-$('body > .modal').height()/2);
  });
});
