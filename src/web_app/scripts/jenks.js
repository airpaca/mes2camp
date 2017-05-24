console.log('jenks.js');

function httpGet(theUrl) {
    /* 
    Retourne un texte GeoJSON à partir de la requête "GET" vers geoserver.
    NOTE: Requête synchrone pour pouvoir utiliser la variable mais pas top.
    Exemple: var response = httpGet('http://host:8080/geoserver/websig/ows?service=WFS&version=1.0.0&request=GetFeature&typeName=websig:communes_multipoll&outputFormat=application%2Fjson');
    FIXME: Better be Asynchrone but doesn't work
    */
    var xmlHttp = new XMLHttpRequest();
    xmlHttp.open( "GET", theUrl, false ); // false for synchronous request
    xmlHttp.send( null );
    // console.log('HTTP - GETR Synchrone: \n' + xmlHttp.responseText);  // DEBUG
    return xmlHttp.responseText;
};

function stats(data, the_field, n_jenks) {
    /* 
    Retourne des statistiques sur un champ JSON.

    var response = httpGet('http://aa/geoser....&outputFormat=application%2Fjson');
    var resultat = stats_json(response, jenks_field, jenks_nb);
    var items = resultat.items; 
    var jenksResult = resultat.jenksResult; 
    var color_x = resultat.color_x;
    var ranges = resultat.ranges;
    */
    console.log(data)
    items = [];
    $.each(data.features, function (key, val) {
        $.each(val.properties, function(i,j){
            if (i == the_field) {
                console.log(j);
                items.push(j);
            };
        }); 
    });
    
    classifier = new geostats(items);
    jenksResult = classifier.getJenks(n_jenks);
    ranges = classifier.getRanges(n_jenks);
    var color_x = chroma.scale('PuRd').colors(n_jenks)
    console.log(items);    
    console.log(jenksResult);
    console.log(color_x);
    
    // return items, jenksResult, color_x;
    return {
        items: items, 
        jenksResult: jenksResult,
        color_x:color_x,
        ranges: ranges
    };  
};

function stats_json(json_text, the_field, n_jenks) {
    /* 
    Retourne des statistiques sur un champ JSON.
    @json_text = a "xmlHttp.responseText"
    Ex: 
    var response = httpGet('http://aa/geoser....&outputFormat=application%2Fjson');
    var resultat = stats_json(response, jenks_field, jenks_nb);
    var items = resultat.items; 
    var jenksResult = resultat.jenksResult; 
    var color_x = resultat.color_x;
    var ranges = resultat.ranges;
    */
    console.log('Function stats_json(json_text, the_field, n_jenks)'); 
    var data = jQuery.parseJSON(json_text);
    items = [];
    $.each(data.features, function (key, val) {
        $.each(val.properties, function(i,j){
            if (i == the_field) {
                items.push(j);
            };
        }); 
    });
    
    classifier = new geostats(items);
    jenksResult = classifier.getJenks(n_jenks);
    ranges = classifier.getRanges(n_jenks);
    var color_x = chroma.scale('PuRd').colors(n_jenks)
    console.log(items);    
    console.log(jenksResult);
    console.log(color_x);
    
    // return items, jenksResult, color_x;
    return {
        items: items, 
        jenksResult: jenksResult,
        color_x:color_x,
        ranges: ranges
    };  
};

function jenks_stats(json_text, the_field, n_jenks) {
    /* 
    
    Ex: 

    var items = resultat.items; 
    var jenksResult = resultat.jenksResult; 
    var color_x = resultat.color_x;
    var ranges = resultat.ranges;
    */
    console.log('Function stats_json(json_text, the_field, n_jenks)'); 
    var data = json_text; //jQuery.parseJSON(json_text);
    items = [];
    $.each(data.features, function (key, val) {
        $.each(val.properties, function(i,j){
            if (i == the_field) {
                items.push(j);
            };
        }); 
    });
    
    classifier = new geostats(items);
    jenksResult = classifier.getJenks(n_jenks);
    ranges = classifier.getRanges(n_jenks);
    var color_x = chroma.scale('PuRd').colors(n_jenks)
    console.log(items);    
    console.log(jenksResult);
    console.log(color_x);
    
    // return items, jenksResult, color_x;
    return {
        items: items, 
        jenksResult: jenksResult,
        color_x:color_x,
        ranges: ranges
    };  
};

function create_ol3_styles(jenksResult, color_x) {
    /* Creates the ol3 styles liste according to stats
    Ex: var ol3_styles = create_ol3_styles(jenksResult, color_x);
    */
    var ol3_styles = [];
    var index, len;
    for (index = 0, len = jenksResult.length; index < len; ++index) { 
        if (index != 0) {
            
            console.log(index, jenksResult[index], color_x[index-1]);
            
            var style = new ol.style.Style({
                stroke: new ol.style.Stroke({
                    color: 'rgba(200, 0, 0, 1.)',
                    width: 2
                }),
                    fill: new ol.style.Fill({
                    color: color_x[index-1]
                })
            }); 
            ol3_styles.push(style);
        };
    };
    return ol3_styles;
}

function create_wfs_style_jenks(feature, resolution, jenksResult, color_x, ol3_styles) {
    /*
    Dans la référence style d'un objet ol.layer.Vector, 
    attribue une classe à chaque objet.
    Ex:
    var lyr_geojson = new ol.layer.Vector({
        name: '', 
        [...]
        strategy: ol.loadingstrategy.bbox(), 
        style: function(feature, resolution){
            return create_wfs_style_jenks(feature, resolution, jenksResult, color_x, ol3_styles);
        },
    });
    */
    for (index = 0, len = jenksResult.length; index < len; ++index) { 
        if (index != 0) {        
            if ( feature.get(jenks_field) <= jenksResult[index]) {
                return ol3_styles[index - 1];
            };
        };
    };   
};

function create_wfs_legend(layer_title, jenksResult, color_x) {
    console.log('Creating legend for ' + layer_title);
    
    wfs_layer_legend = layer_title + '<br/>';
    
    var index, len;
    for (index = 0, len = jenksResult.length; index < len; ++index) { 
        if (index != 0) {
            wfs_layer_legend += '<div style="color:' + color_x[index-1] + ';">'+ ranges[index-1]+'</div>';
        };
    };

    /**
    PERMETS DE RECUPERER LA LEGENDE GEOSERVER !!!
    
    wfs_layer_legend='<img src="http://5.39.86.180:8080/geoserver/wms?REQUEST=GetLegendGraphic&VERSION=1.0.0&FORMAT=image/png&WIDTH=20&HEIGHT=20&LAYER=websig:communes_multipoll&STYLE=basicstyle9">';
    */


   
    // wfs_layer_legend = '\
    // <svg width="400" height="110">\
    // <rect width="300" height="100" style="fill:rgb(0,0,255);stroke-width:3;stroke:rgb(0,0,0)" />\
    // </svg>'; 
    
    // wfs_layer_legend = '\
    // <svg width="200" height="50">\
    // <rect x="0" y="5" width="20" height="10" style="fill:rgb(0,0,255);stroke-width:3;stroke:rgb(0,0,0)" /><text x="25" y="15" style="fill:black;">Mes classes</text>\
    // </svg>';     

    console.log('###########################');
    wfs_layer_legend = layer_title + '<svg width="200" height="500">';
    var index, len;
    var y = 15;
    var compteur = 1;
    for (index = 0, len = jenksResult.length; index < len; ++index) { 
        if (index != 0) {
            var y_pos = y * compteur + 1;
            var y_pos_text = y * compteur + 1 + 10;
            console.log(y_pos);
            // wfs_layer_legend += '<div style="color:' + color_x[index-1] + ';">'+ ranges[index-1]+'</div>';
            wfs_layer_legend += '<rect x="0" y="' + y_pos + '" width="40" height="10" style="fill:' + color_x[index-1] + ';stroke-width:1;stroke:rgb(0,0,0)" />';
            wfs_layer_legend += '<text x="50" y="' + y_pos_text + '" style="fill:black;">' + ranges[index-1] + '</text>';
        };
        compteur += 1;
        console.log('Compteur = ' + compteur);
    };         
    wfs_layer_legend += '</svg>';





    
    return wfs_layer_legend;
};

/* Tests
var response = httpGet('http://5.39.86.180:8080/geoserver/websig/ows?service=WFS&version=1.0.0&request=GetFeature&typeName=websig:communes_multipoll&outputFormat=application%2Fjson');
var items, jenksResult, color_x = stats_json(response, 'indice');
var ol3_styles = create_ol3_styles(jenksResult, color_x);
*/


