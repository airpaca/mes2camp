<?php 

/* Chargement du fichier de config en fonction de l'utilisateur */
if ($user == "admin") {
    include 'config_su.php';
} else {
    include 'config.php';
}

/* Récupération des données */
$id_point = $_GET['id_point'];
$id_polluant = $_GET['id_polluant'];

/* Connexion à PostgreSQL */
$conn = pg_connect("dbname='campagne' user='" . $pg_lgn . "' password='" . $pg_pwd . "' host='" . $pg_host . "'");
if (!$conn) {
    echo "Not connected";
    exit;
}

$sql = "
select id_point, id_polluant, an, val_carto::int, val_memo
from prod.mes_red 
where id_point = " . $id_point . " and id_polluant = " . $id_polluant . " and id_indicateur = 1
order by id_point, id_polluant, an, val_carto, val_memo
;
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
