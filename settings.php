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


// Url zur Seite
$url = 'http://piraten.boombuler.de/';
// SSL Wiki Verbindung benutzen?
$use_ssl = true;
$curl_path="/usr/bin/curl";
$allow_view_public = true;

// MySQL Verbindung:
// =================
// Server
$mysql_server="localhost";
// Benutzer
$mysql_user="";
// Passwort
$mysql_password="";
// Datenbank
$mysql_database="";

// Tabellen Prefix
$tbl_prefix = "plakate_";


// OpenLayers Pfad
$openlayers_path = "http://www.openlayers.org/api/";
// OpenStreetMap Pfad
$openstreetmap_path="http://www.openstreetmap.org/openlayers/";