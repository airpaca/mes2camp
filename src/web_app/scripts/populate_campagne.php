<?php 

/* Chargement du fichier de config en fonction de l'utilisateur */
// if ($user == "admin") {
    // include 'config_su.php';
// } else {
    include 'config.php';
// }

/* Connexion à PostgreSQL */
$conn = pg_connect("dbname='campagne' user='" . $pg_lgn . "' password='" . $pg_pwd . "' host='" . $pg_host . "'");
if (!$conn) {
    echo "Not connected";
    exit;
}

// Récup des campagnes avec suppression MC
$sql = "
select distinct id_campagne, nom_campagne, color
from prod.campagne 
where id_campagne <> 36 
union all
select 0, '*', null 
order by nom_campagne;
";

$res = pg_query($conn, $sql);
if (!$res) {
    echo "An SQL error occured.\n";
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
