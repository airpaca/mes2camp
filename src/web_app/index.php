<!DOCTYPE html>
<html lang="en">
<head>

    <!-- 
    NOTE: Bon lien pour création légende et colorramp: 
          http://jsfiddle.net/nathansnider/oguh5t94/
    NOTE: Pour le NO2 filtre sur val_memo, pour PM10 filtre sur station virtuelle/permanente
    NOTE: Faire un code propre avec https://blog.webkid.io/rarely-used-leaflet-features/
    TODO: Quand on sélectionne une campagne, zoomer sur les points
    TODO: Mettre une icone de chargement au début et au chargement de chaque couche.
    TODO: Faire les mesures PM10 (table différente de celle du NO2)
    -->

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>mes2camp</title>
    
    <!-- JQuery 3.2.1 -->
    <script src="libs/jquery/jquery-3.2.1.min.js"></script>    
    
    <!-- Leaflet 3.2.1 -->
    <script src="libs/leaflet/leaflet_v1.0.3/leaflet.js"></script> 
    <link rel="stylesheet" href="libs/leaflet/leaflet_v1.0.3/leaflet.css"/>

    <!-- Bootstrap -->
    <link rel="stylesheet" href="libs/bootstrap/bootstrap-3.3.7-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="libs/bootstrap/bootstrap-3.3.7-dist/css/bootstrap-theme.min.css">
    <script src="libs/bootstrap/bootstrap-3.3.7-dist/js/bootstrap.min.js"></script>

    <!-- Leaflet Sidebar -->
    <script src="libs/leaflet-sidebar-master/src/L.Control.Sidebar.js"></script>
    <link rel="stylesheet" href="libs/leaflet-sidebar-master/src/L.Control.Sidebar.css"/>    
    
    <!-- Chart.js -->
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.1.4/Chart.min.js"></script>
    
    <!-- Config -->
    <script type="text/javascript" src="config.js"></script></script>
    
    <!-- CSS -->
    <link rel="stylesheet" href="style.css"/>
   
</head>
    
<!------------------------------------------------------------------------------ 
                                    Body
------------------------------------------------------------------------------->
<body>

<!-- ......... Side bar -->
<div id="container">
    <div id="sidebar-left">
        <div class="list-group">
        
            <img class="img-titre" align="middle" src="img/logo-Air-PACA_small.png">
        
            <h5>mes2camp</h5>
            <a href="#" class="list-group-item active no2" id="no2">
            NO₂
            <span class="glyphicon glyphicon-chevron-right"></span>
            <span class="badge">Ready</span>
            </a>

            <a href="#" class="list-group-item pm10" id="pm10">
            PM10
            <span class="glyphicon hide glyphicon-chevron-right"></span>
            <span class="badge">Ready</span>
            </a>

            <a href="#" class="list-group-item pm25" id="pm25">
            PM2.5
            <span class="glyphicon hide glyphicon-chevron-right"></span>
            <span class="badge">Ready</span>
            </a>            

            <a href="#" class="list-group-item campagnes" id="campagnes">
            Campagnes
            <span class="glyphicon hide glyphicon-chevron-right"></span>
            <span class="badge">Ready</span>
            </a>  
            
            </a>
        </div>
        
        <select class="form-control campagnes-select hidden"></select>  
        
    </div>
    <!-- Leaflet sidebar -->
    <div id="sidebar">
        <h1>leaflet-sidebar</h1>
    </div>    
    
    <!-- Element carte -->
    <div id="map"></div>
    
    
</div>

<!------------------------------------------------------------------------------ 
                                    Map script
------------------------------------------------------------------------------->
<script type="text/javascript">

/* 
Variables générales 
*/
var my_layers = {};

var no2 = {
    table: "prod.no2_ma_2016_v2017", 
    geom: "geom", 
    srid: "4326", 
    fields: "id_point, adresse, nom_polluant, an, valeur, val_memo, an_source", 
    where: "WHERE valeur >= 0", 
    onMap: true, 
    layerName: "no2", 
    filter: 99999,
    grades: [10,20,30,40,50,60,70,80],
    legendTitle: ["<strong>NO₂ moy.an</strong>"], 
    reset: function() {
        // restore initial values
        this.legendTitle = ["<strong>NO₂ moy.an</strong>"]
    }
};

var pm10 = {
    table: "prod.pm10_p904_2016_v2017", 
    geom: "geom", 
    srid: "4326", 
    fields: "id_point, adresse, nom_polluant, valeur, an_mesure, nom_campagne, annee_campagne", 
    where: "WHERE valeur >= 0", 
    onMap: false, 
    layerName: "pm10", 
    filter: 99999,
    grades: [10,20,30,40,50,60,70,80,90,100],
    legendTitle: ["<strong>PM10µ p90.4</strong>"],
    reset: function() {
        // restore initial values
        this.legendTitle = ["<strong>PM10µ p90.4</strong>"]
    }    
};

var pm25 = {
    table: "prod.pm25_ma_2016_v2017", 
    geom: "geom", 
    srid: "4326", 
    fields: "id_point, adresse, nom_polluant, an_mesure, valeur, nom_campagne, annee_campagne", 
    where: "WHERE valeur >= 0", 
    onMap: false, 
    layerName: "pm25", 
    filter: 99999,
    grades: [10,20,30,40,50],
    legendTitle: ["<strong>PM2.5µ moy.an</strong>"],
    reset: function() {
        // restore initial values
        this.legendTitle = ["<strong>PM2.5µ moy.an</strong>"]
    }  
};

var campagnes = {
    table: "prod.points_campagne_v2017", 
    geom: "geom", 
    srid: "4326", 
    fields: "id_point, adresse, nom_campagne, color, annee_campagne, no2_ma, pm10_p904, pm25_ma", 
    where: "WHERE id_point IS NOT NULL", 
    onMap: false, 
    layerName: "campagnes", 
    filter: 99999,
    grades: [0],
    legendTitle: ["<strong>Campagnes de mesure</strong>"],
    reset: function() {
        // restore initial values
        this.legendTitle = ["<strong>Campagnes de mesure</strong>"]
    }  
};

var yearlegend = L.control({position: 'bottomright'});

/*
Fonctions
*/

$(function() /* Ecoute des actions de l'utilisateur*/ {
    /*
    Fonction qui se déclanche lorsque l'on clique sur l'un des éléments listes
    */
    $('.list-group-item').click( function() {
        
        // Gestion de l'affichage des listes
        $(this).addClass('active').siblings().removeClass('active');
        $("a .glyphicon-chevron-right").addClass('hide');		
        $("#" + $(this)[0].id + " .glyphicon-chevron-right").removeClass('hide');

        // Gestion de l'affichage des couches
        for (alayer in my_layers) {        
            if (!($(this)[0].id == alayer)){
                map.removeLayer(my_layers[alayer]);
                window[my_layers[alayer].options.name].onMap = false;
            };           
        };		
        my_layers[$(this)[0].id].addTo(map);
        window[my_layers[$(this)[0].id].options.name].onMap = true;
       
        // Gestion de la légende
        generate_legend($(this)[0].id);
        
        // Si on active la couche des campagnes alors on montre la liste déroulante et inversement
        if (my_layers[$(this)[0].id].options.name == "campagnes") {
            $('.campagnes-select').removeClass("hidden");
        } else {  
            $('.campagnes-select').addClass("hidden");
        };
        
    });
});	

$('.campagnes-select').change(function() /* Sélection liste déroulante campagnes */ {
    
    if ($(this).val() == 0) {
        update_layer(campagnes, "WHERE id_point IS NOT NULL");
    } else {
        update_layer(campagnes, "WHERE id_campagne = " + $(this).val());
    };
    
});

function createMap(){
    /* Création de la carte */
    var map = L.map('map', {layers: []}).setView([43.9, 6.0], 8);    
    map.attributionControl.addAttribution('mes2camp &copy; <a href="http://www.airpaca.org/">Air PACA - 2017</a>');    

    /* Chargement des fonds carto */    
    var Hydda_Full = L.tileLayer('http://{s}.tile.openstreetmap.se/hydda/full/{z}/{x}/{y}.png', {
        maxZoom: 18,
        opacity: 0.5,
        attribution: 'Fond de carte &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    });
    Hydda_Full.addTo(map);

    // Prise en compte du click sur la carte 
    map.on('click', function(e)  {   
        sidebar.hide();
    });
    
    return map;
};

function getColorNo2(y){
  return y > 81.0000 ? '#800000':
    y > 80.0000 ? '#830000':
    y > 79.0000 ? '#860000':
    y > 78.0000 ? '#890000':
    y > 77.0000 ? '#8c0000':
    y > 76.0000 ? '#8f0000':
    y > 75.0000 ? '#930000':
    y > 74.0000 ? '#960000':
    y > 73.0000 ? '#990000':
    y > 72.0000 ? '#9c0000':
    y > 71.0000 ? '#9f0000':
    y > 70.0000 ? '#a20000':
    y > 69.0000 ? '#a60000':
    y > 68.0000 ? '#a90000':
    y > 67.0000 ? '#ac0000':
    y > 66.0000 ? '#af0000':
    y > 65.0000 ? '#b20000':
    y > 64.0000 ? '#b50000':
    y > 63.0000 ? '#b90000':
    y > 62.0000 ? '#bc0000':
    y > 61.0000 ? '#bf0000':
    y > 60.0000 ? '#c20000':
    y > 59.0000 ? '#c50000':
    y > 58.0000 ? '#c90000':
    y > 57.0000 ? '#cc0000':
    y > 56.0000 ? '#cf0000':
    y > 55.0000 ? '#d20000':
    y > 54.0000 ? '#d50000':
    y > 53.0000 ? '#d80000':
    y > 52.0000 ? '#dc0000':
    y > 51.0000 ? '#df0000':
    y > 50.0000 ? '#e20000':
    y > 49.0000 ? '#e50000':
    y > 48.0000 ? '#e80000':
    y > 47.0000 ? '#eb0000':
    y > 46.0000 ? '#ef0000':
    y > 45.0000 ? '#f20000':
    y > 44.0000 ? '#f50000':
    y > 43.0000 ? '#f80000':
    y > 42.0000 ? '#fb0000':
    y > 41.0000 ? '#ff0000':
    y > 40.0000 ? '#ff1500':
    y > 39.0000 ? '#ff2a00':
    y > 38.0000 ? '#ff3f00':
    y > 37.0000 ? '#ff5500':
    y > 36.0000 ? '#ff6a00':
    y > 35.0000 ? '#ff7f00':
    y > 34.0000 ? '#ff9400':
    y > 33.0000 ? '#ffaa00':
    y > 32.0000 ? '#ffb400':
    y > 31.0000 ? '#ffbf00':
    y > 30.0000 ? '#ffc900':
    y > 29.0000 ? '#ffd400':
    y > 28.0000 ? '#ffdf00':
    y > 27.0000 ? '#ffe900':
    y > 26.0000 ? '#fff400':
    y > 25.0000 ? '#ffff00':
    y > 24.0000 ? '#f2fb00':
    y > 23.0000 ? '#e5f800':
    y > 22.0000 ? '#d8f500':
    y > 21.0000 ? '#ccf200':
    y > 20.0000 ? '#bfef00':
    y > 19.0000 ? '#b2ec00':
    y > 18.0000 ? '#a5e900':
    y > 17.0000 ? '#99e600':
    y > 16.0000 ? '#85e215':
    y > 15.0000 ? '#72df2a':
    y > 14.0000 ? '#5fdc3f':
    y > 13.0000 ? '#4cd955':
    y > 12.0000 ? '#39d56a':
    y > 11.0000 ? '#26d27f':
    y > 10.0000 ? '#13cf94':
    y > 9.0000 ? '#00ccaa':
    y > 8.0000 ? '#00ccaa':
    y > 7.0000 ? '#00ccaa':
    y > 6.0000 ? '#00ccaa':
    y > 5.0000 ? '#00ccaa':
    y > 4.0000 ? '#00ccaa':
    y > 3.0000 ? '#00ccaa':
    y > 2.0000 ? '#00ccaa':
    y > 1.0000 ? '#00ccaa':  
    '#00ccaa';     
};

function getColorPm10(y){
    return y > 101.0000 ? '#800000':
    y > 100.0000 ? '#820000':
    y > 99.0000 ? '#850000':
    y > 98.0000 ? '#870000':
    y > 97.0000 ? '#8a0000':
    y > 96.0000 ? '#8c0000':
    y > 95.0000 ? '#8f0000':
    y > 94.0000 ? '#910000':
    y > 93.0000 ? '#940000':
    y > 92.0000 ? '#960000':
    y > 91.0000 ? '#990000':
    y > 90.0000 ? '#9b0000':
    y > 89.0000 ? '#9e0000':
    y > 88.0000 ? '#a10000':
    y > 87.0000 ? '#a30000':
    y > 86.0000 ? '#a60000':
    y > 85.0000 ? '#a80000':
    y > 84.0000 ? '#ab0000':
    y > 83.0000 ? '#ad0000':
    y > 82.0000 ? '#b00000':
    y > 81.0000 ? '#b20000':
    y > 80.0000 ? '#b50000':
    y > 79.0000 ? '#b70000':
    y > 78.0000 ? '#ba0000':
    y > 77.0000 ? '#bc0000':
    y > 76.0000 ? '#bf0000':
    y > 75.0000 ? '#c20000':
    y > 74.0000 ? '#c40000':
    y > 73.0000 ? '#c70000':
    y > 72.0000 ? '#c90000':
    y > 71.0000 ? '#cc0000':
    y > 70.0000 ? '#ce0000':
    y > 69.0000 ? '#d10000':
    y > 68.0000 ? '#d30000':
    y > 67.0000 ? '#d60000':
    y > 66.0000 ? '#d80000':
    y > 65.0000 ? '#db0000':
    y > 64.0000 ? '#dd0000':
    y > 63.0000 ? '#e00000':
    y > 62.0000 ? '#e30000':
    y > 61.0000 ? '#e50000':
    y > 60.0000 ? '#e80000':
    y > 59.0000 ? '#ea0000':
    y > 58.0000 ? '#ed0000':
    y > 57.0000 ? '#ef0000':
    y > 56.0000 ? '#f20000':
    y > 55.0000 ? '#f40000':
    y > 54.0000 ? '#f70000':
    y > 53.0000 ? '#f90000':
    y > 52.0000 ? '#fc0000':
    y > 51.0000 ? '#ff0000':
    y > 50.0000 ? '#ff1100':
    y > 49.0000 ? '#ff2200':
    y > 48.0000 ? '#ff3300':
    y > 47.0000 ? '#ff4300':
    y > 46.0000 ? '#ff5500':
    y > 45.0000 ? '#ff6600':
    y > 44.0000 ? '#ff7700':
    y > 43.0000 ? '#ff8800':
    y > 42.0000 ? '#ff9900':
    y > 41.0000 ? '#ffaa00':
    y > 40.0000 ? '#ffb200':
    y > 39.0000 ? '#ffbb00':
    y > 38.0000 ? '#ffc300':
    y > 37.0000 ? '#ffcc00':
    y > 36.0000 ? '#ffd400':
    y > 35.0000 ? '#ffdc00':
    y > 34.0000 ? '#ffe500':
    y > 33.0000 ? '#ffee00':
    y > 32.0000 ? '#fff600':
    y > 31.0000 ? '#ffff00':
    y > 30.0000 ? '#f4fc00':
    y > 29.0000 ? '#eafa00':
    y > 28.0000 ? '#e0f700':
    y > 27.0000 ? '#d6f500':
    y > 26.0000 ? '#ccf200':
    y > 25.0000 ? '#c1f000':
    y > 24.0000 ? '#b7ed00':
    y > 23.0000 ? '#adeb00':
    y > 22.0000 ? '#a3e800':
    y > 21.0000 ? '#99e600':
    y > 20.0000 ? '#89e311':
    y > 19.0000 ? '#7ae022':
    y > 18.0000 ? '#6bde32':
    y > 17.0000 ? '#5bdb44':
    y > 16.0000 ? '#4cd955':
    y > 15.0000 ? '#3dd665':
    y > 14.0000 ? '#2dd377':
    y > 13.0000 ? '#1ed188':
    y > 12.0000 ? '#0fce99':
    y > 11.0000 ? '#00ccaa':
    y > 10.0000 ? '#00ccaa':
    y > 9.0000 ? '#00ccaa':
    y > 8.0000 ? '#00ccaa':
    y > 7.0000 ? '#00ccaa':
    y > 6.0000 ? '#00ccaa':
    y > 5.0000 ? '#00ccaa':
    y > 4.0000 ? '#00ccaa':
    y > 3.0000 ? '#00ccaa':
    y > 2.0000 ? '#00ccaa':
    y > 1.0000 ? '#00ccaa':
    '#00ccaa';      
};

function getColorPm25(y){
    return y > 51.0000 ? '#800000':
    y > 50.0000 ? '#850000':
    y > 49.0000 ? '#8a0000':
    y > 48.0000 ? '#8f0000':
    y > 47.0000 ? '#940000':
    y > 46.0000 ? '#990000':
    y > 45.0000 ? '#9e0000':
    y > 44.0000 ? '#a30000':
    y > 43.0000 ? '#a80000':
    y > 42.0000 ? '#ad0000':
    y > 41.0000 ? '#b20000':
    y > 40.0000 ? '#b70000':
    y > 39.0000 ? '#bc0000':
    y > 38.0000 ? '#c20000':
    y > 37.0000 ? '#c70000':
    y > 36.0000 ? '#cc0000':
    y > 35.0000 ? '#d10000':
    y > 34.0000 ? '#d60000':
    y > 33.0000 ? '#db0000':
    y > 32.0000 ? '#e00000':
    y > 31.0000 ? '#e50000':
    y > 30.0000 ? '#ea0000':
    y > 29.0000 ? '#ef0000':
    y > 28.0000 ? '#f40000':
    y > 27.0000 ? '#f90000':
    y > 26.0000 ? '#ff0000':
    y > 25.0000 ? '#ff2200':
    y > 24.0000 ? '#ff4300':
    y > 23.0000 ? '#ff6600':
    y > 22.0000 ? '#ff8800':
    y > 21.0000 ? '#ffaa00':
    y > 20.0000 ? '#ffbb00':
    y > 19.0000 ? '#ffcc00':
    y > 18.0000 ? '#ffdc00':
    y > 17.0000 ? '#ffee00':
    y > 16.0000 ? '#ffff00':
    y > 15.0000 ? '#eafa00':
    y > 14.0000 ? '#d6f500':
    y > 13.0000 ? '#c1f000':
    y > 12.0000 ? '#adeb00':
    y > 11.0000 ? '#99e600':
    y > 10.0000 ? '#7ae022':
    y > 9.0000 ? '#5bdb44':
    y > 8.0000 ? '#3dd665':
    y > 7.0000 ? '#1ed188':
    y > 6.0000 ? '#00ccaa':
    y > 5.0000 ? '#00ccaa':
    y > 4.0000 ? '#00ccaa':
    y > 3.0000 ? '#00ccaa':
    y > 2.0000 ? '#00ccaa':
    y > 1.0000 ? '#00ccaa':
    '#00ccaa';      
};

function generate_legend(layerName){
    /*
    Exemple: generate_legend("pm10");
    */   
    yearlegend.onAdd = function (map) {
        
        //set up legend grades and labels
        var div = L.DomUtil.create('div', 'info legend'),
        from, to;
        var labels = [];
                
        if (layerName == "no2") {
            no2.reset();
            grades = no2.grades;
            labels = no2.legendTitle;
        } else if (layerName == "pm10") {
            pm10.reset();
            grades = pm10.grades;
            labels = pm10.legendTitle; 
        } else if (layerName == "pm25") {
            pm25.reset();
            grades = pm25.grades;
            labels = pm25.legendTitle;   
        } else if (layerName == "campagnes") {
            campagnes.reset();
            grades = campagnes.grades;
            labels = campagnes.legendTitle;                
        };
        
        // iterate through grades and create a color field and label for each
        for (var i = 0; i < grades.length; i++) {
            from = grades[i];
            to = grades[i + 1];

            if (layerName == "no2") {
                labels.push('<i class="colorcircle" style="background:' + getColorNo2(from + 1) + '"></i> ' + from + (to ? '&ndash;' + to : '+'));
            } else if (layerName == "pm10") {
                labels.push('<i class="colorcircle" style="background:' + getColorPm10(from + 1) + '"></i> ' + from + (to ? '&ndash;' + to : '+'));         
            } else if (layerName == "pm25") {
                labels.push('<i class="colorcircle" style="background:' + getColorPm25(from + 1) + '"></i> ' + from + (to ? '&ndash;' + to : '+'));         
            } else if (layerName == "campagnes") {
                labels.push('<i class="colorcircle" style="background:#585858"></i>');         
            };            
           
        };
        
        div.innerHTML = labels.join('<br>');
        
        
        return div;
    };
    yearlegend.addTo(map);    
};

function response2json(response){
    /*
    Transforme une réponse constituée d'array de lignes PostgreSQL
    en objet geojson prêt à être utilisé par leaflet
    */
    var geojson = {"type": "FeatureCollection", "features": []};    
      
    response.forEach(function(d){
        
        fields = {};
        for (propertie in d){
            if (propertie != "geom") {
                fields[propertie] = d[propertie];
            };  
        };        
        
        var feature = {"type": "Feature", "properties": fields, "geometry": JSON.parse(d.geom)};
        geojson.features.push(feature);     
    });
    
    return geojson;
};

function get_postgis_layer(table, geom, srid, fields, where, onMap, layerName, filter){
    /*
    Récupère les données d'une couche PostGIS (attributs et geom) avec le script 
    script/get_postgis_layer.php.
    Transforme la réponse en json avec la fonction response2json().
    Crée une couche de points.
    */
    $.ajax({
        type: "GET",
        url: "scripts/get_postgis_layer.php",
        data: { 
            geotable: table,
            geomfield: geom,
            srid: srid,
            fields: fields,    
            where: where  
        },
        dataType: 'json',
        beforeSend:function(jqXHR, settings){
            jqXHR.onMap = onMap;
        },        
        success: function(response,textStatus,jqXHR){
         
            geojson = response2json(response);

            
            my_layers[layerName] = new L.GeoJSON(geojson, {
                name: layerName,
                pointToLayer: function (feature, latlng) {
                    
                    if (layerName == "no2"){
                        var markerStyle = {
                            fillColor: getColorNo2(feature.properties.valeur),
                            color: "#FFF",
                            fillOpacity: 0.7,
                            opacity: 1,
                            weight: 1,
                            radius: 8,
                        };
                    } else if (layerName == "pm10") {
                        var markerStyle = {
                            fillColor: getColorPm10(feature.properties.valeur),
                            color: "#FFF",
                            fillOpacity: 0.7,
                            opacity: 1,
                            weight: 1,
                            radius: 8,
                        };
                    } else if (layerName == "pm25") {
                        var markerStyle = {
                            fillColor: getColorPm25(feature.properties.valeur),
                            color: "#FFF",
                            fillOpacity: 0.7,
                            opacity: 1,
                            weight: 1,
                            radius: 8,                            
                        };
                   } else if (layerName == "campagnes") {                       
                        var markerStyle = {
                            fillColor: feature.properties.color,
                            color: "#FFF",
                            fillOpacity: 0.7,
                            opacity: 1,
                            weight: 1,
                            radius: 8,                            
                        }; 
                    } else {
                            console.log("ERROR get_postgis_layer LayerName non pris en compte");
                    };     
                       
                    if (layerName == "campagnes") {
                        return L.circleMarker(latlng, markerStyle).bindTooltip(feature.properties["nom_campagne"], {permanent: false, direction: 'top', opacity: 0.9});
                    } else {
                        return L.circleMarker(latlng, markerStyle).bindTooltip(feature.properties["valeur"], {permanent: false, direction: 'top', opacity: 0.9});
                    };
 
                },               
                filter: function(feature, layer) {
                    if (layerName == "campagnes") {
                       return true;
                    } else if (feature.properties.valeur < filter) {  
                        return true;
                    };
                },
                onEachFeature: function (feature, layer) {
                    
                    // Ajout d'un popup
                    var html = '<div id="popup">';
                    for (prop in feature.properties) {
                        html += "<b>" + prop + ':</b> ' + feature.properties[prop]+"<br>";
                    };
                    
                    // Ajout du lien vers la fonction de graphiques + passage arguments 
                    if (feature.properties["nom_polluant"] == "NO2"){
                        html += '<div class="show-graph"><a href="#" onclick="graphiques(' + feature.properties["id_point"] + ',\'' + feature.properties["nom_polluant"] +'\')">Voir Toutes les mesures</a></div>';
                    };
                    
                    html += "</div>";
                    layer.bindPopup(html);

                    // Prise en compte du hover
                    layer.on('mouseover', function(){
                        layer.setStyle({color: '#FFF', weight: 3});
                    });
                    layer.on('mouseout', function(){
                        layer.setStyle({color: "#FFF",weight: 1});
                    });

                    // Prise en compte du cklic
                    layer.on('click', function(){
                        sidebar.hide();
                    });                    

                },
            });
            
            // Si voulu, affichage sur la carte et création de la légende
            if (jqXHR.onMap == true) {
                my_layers[layerName].addTo(map);
                generate_legend(layerName);
            };  

        },
        error: function (request, error) {
            console.log(arguments);
            console.log("Ajax error: " + error);
            $("#error_tube").show();
        },        
    });	
};

function update_layer(obj, where){  
    /*
    Exemple: update_layer(no2, 'WHERE valeur >= 70');
    */
      
    // Maj de la clause WHERE de l'objet avec nouvelles valeurs
    obj.where = where;
    
    // Suppression de l'ancient layer et création du nouveau
    map.removeLayer(my_layers[obj.layerName]);    
    get_postgis_layer(obj.table, obj.geom, obj.srid, obj.fields, obj.where, obj.onMap, obj.layerName, obj.filter);  
};

function populate_campagnes() {
    /*
    Fill la liste déroulante avec les campagnes de mesures 
    récupérées dans PostgreSQL
    */
    $.ajax({
        type: "GET",
        url: "scripts/populate_campagne.php",
        dataType: 'json',      
        success: function(response,textStatus,jqXHR){
            $.each(response, function (i, response) {
                $('.campagnes-select').append($('<option>', { 
                    value: response.id_campagne,
                    text : response.nom_campagne
                }));
            });               
        },
        error: function (request, error) {
            console.log(arguments);
            console.log("Ajax error: " + error);
            $("#error_tube").show();
        },        
    });	  
};

function create_sidebar(){
    /*
    Initialisation de la slidebar popup
    Ex: var sidebar = create_sidebar();
    */
    var sidebar = L.control.sidebar('sidebar', {
        closeButton: true,
        position: 'right',
        autoPan: true,
    });

    // Modification de le fonction show de la sidebar.
    sidebar.show = function () {
        // RS ADD - Always Pan on show()
        this._map.panBy([-this.getOffset() / 2, 0], {
            duration: 0.5
        });
        // ---

        if (!this.isVisible()) {
            L.DomUtil.addClass(this._container, 'visible');
            if (this.options.autoPan) {
            }
            this.fire('show');
        }
    };


    map.addControl(sidebar);
    sidebar.hide();
    
    return sidebar;
};

function graphiques(id_point, nom_polluant){
    
    sidebar.hide();
    
    // Définition de l'id_polluant en fonction de son nom
    if (nom_polluant == "NO2"){
        id_polluant = 1;
    } else if (nom_polluant == "PM10") {
        id_polluant = 2;
    } else if (nom_polluant == "PM2.5") {
        id_polluant = 3;
    };

     // Requête AJAX pour récupérer les mesures
    $.ajax({
        url: "scripts/graphmes.php", // query_tubes.php
        type: 'GET',
        data : {
            id_point: id_point, 
            id_polluant: id_polluant
        },
        dataType: 'json',
        error: function (request, error) {
            console.log(arguments);
            console.log("Ajax error: " + error);
        },       
        success: function(response,textStatus,jqXHR){  
            
            // Prépare le ou les élément(s) HTML du graph
            sidebar.setContent('<h4>Mesures de ' + nom_polluant + ' point ' + id_point + '</h4>' + '<canvas id="graph" width="600" height="350"></canvas><br><font color="blue">Mesure</font><br><font color="orange">Régression Linéaire</font>');  
               
            // Ne crée le graphique que si la requête a retournée un résultat
            if (typeof response[0] !== "undefined") {
                
                var graph_labels = [];
                for (var i in response) {
                    graph_labels.push(response[i].an);
                };              
            
                var graph_title = 'Valeurs mesurées ou estimées';

                var graph_data = [];
                for (var i in response) {
                    graph_data.push(response[i].val_carto);
                };  
                
                var bg_colors = [];
                var bd_colors = [];
                for (var i in response) {
                    if (response[i].val_memo == "reg lineaire") {
                        bg_colors.push('rgba(255, 206, 86, 0.8)');
                        bd_colors.push('rgba(255, 206, 86, 0.8)');
                    } else {
                        bg_colors.push('rgba(54, 162, 235, 0.8)');
                        bd_colors.push('rgba(54, 162, 235, 0.8)');
                    };
                };  
                
                var ctx = document.getElementById("graph");
                var graph_no2 = new Chart(ctx, {
                    type: 'bar', // 'horizontalBar',          
                    data: {
                        labels: graph_labels,
                        datasets: [{
                            label: 'NO2',
                            data: graph_data,
                            backgroundColor: bg_colors,
                            borderColor: bd_colors,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        title: {
                            display: false,
                            fontSize: 20,
                            text: graph_title
                        },
                        legend: {
                            position: 'bottom',
                            display: false,
                        },
                        scales: {
                            yAxes: [{
                                ticks: {
                                    // beginAtZero:true,
                                    min:0,
                                    max: 150,
                                }
                            }]
                        }
                    }
                });                 
                
            };
    
            // Affiche la sidebar et le graphique qu'elle contient
            sidebar.show();
    
        }
    }); 
};    

/*
Appel des fonctions
*/
var map = createMap();
var sidebar = create_sidebar();
populate_campagnes();
get_postgis_layer(no2.table, no2.geom, no2.srid, no2.fields, no2.where, no2.onMap, no2.layerName, no2.filter);
get_postgis_layer(pm10.table, pm10.geom, pm10.srid, pm10.fields, pm10.where, pm10.onMap, pm10.layerName, pm10.filter);
get_postgis_layer(pm25.table, pm25.geom, pm25.srid, pm25.fields, pm25.where, pm25.onMap, pm25.layerName, pm25.filter);
get_postgis_layer(campagnes.table, campagnes.geom, campagnes.srid, campagnes.fields, campagnes.where, campagnes.onMap, campagnes.layerName, campagnes.filter);




</script>

</body>
</html>
