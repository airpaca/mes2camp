<?php 

include 'config.php';

# Connexion à PostgreSQL
$conn = pg_connect("dbname='" . $pg_db . "' user='" . $pg_lgn . "' password='" . $pg_pwd . "' host='" . $pg_host . "'");
if (!$conn) {
    echo "Not connected";
    exit;
}

# Retrouve le nom de la campagne
$sql = "SELECT valeur_info FROM c_template.info WHERE nom_info = 'Nom';";

$result = pg_query($conn, $sql);
if (!$result) {
    echo "An SQL error occured.\n";
    exit;
}

while ($row = pg_fetch_assoc($result)) {
	$campagne_nom = $row['valeur_info'];
};

# Retrouve l'image de la campagne
$sql = "SELECT encode(pj_info, 'base64'::text) AS valeur_info FROM c_template.info WHERE nom_info = 'Image principale';";

$result = pg_query($conn, $sql);
if (!$result) {
    echo "An SQL error occured.\n";
    exit;
}

while ($row = pg_fetch_assoc($result)) {
	$campagne_image = $row['valeur_info'];
};

echo 
	'<div class="title"><img src="data:image/png;base64, ' 
	. $campagne_image . 
	'" height="80" width="80"/>' 
	. $campagne_nom . 
	'</div>';
// echo '<img src="data:image/png;base64, ' . $campagne_image . '" height="80" width="80"/>';
?>