<?php 

/* Récupération de variables */
$geotable = $_GET['geotable'];
$fields = $_GET['fields'];
$geomfield = $_GET['geomfield'];
$srid = $_GET['srid'];
$where = $_GET['where'];

/* Chargement du fichier de config en fonction de l'utilisateur */
if ($user == "admin") {
    include 'config_su.php';
} else {
    include 'config.php';
}

/* Connexion à PostgreSQL */
$conn = pg_connect("dbname='campagne' user='" . $pg_lgn . "' password='" . $pg_pwd . "' host='" . $pg_host . "'");
if (!$conn) {
    echo "Not connected";
    exit;
}

$sql = "
select distinct 
	" . $fields . ", 
	ST_AsGeoJSON(st_transform(" . $geomfield . ", 4326)) as geom
from " . $geotable . "  
" . $where . "
;
";

$res = pg_query($conn, $sql);
if (!$res) {
    echo "An SQL error occured TOTOT.\n";
    exit;
}

$array_result = array();
while ($row = pg_fetch_assoc( $res )) {
  $array_result[] = $row;
} 

/* Export en JSON */
header('Content-Type: application/json');
echo json_encode($array_result);

?>
