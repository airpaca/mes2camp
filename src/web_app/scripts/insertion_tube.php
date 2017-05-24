<?php 

/* Récupération de variables 
if (isset($_POST['tube_nom'])) {
    $tube_nom = strip_tags($_POST['tube_nom']);
    $tube_type = strip_tags($_POST['tube_type']);   
};
*/
    
$lat = $_POST['lat'];
$lng = $_POST['lng'];
$nom = $_POST['nom'];
$type = $_POST['type'];
$image= base64_decode($_POST['image']);

/* Chargement du fichier de config en fonction de l'utilisateur */
if ($user == "admin") {
    include 'config_su.php';
} else {
    include 'config.php';
}    

/* Connexion à PostgreSQL */
$conn = pg_connect("dbname='" . $pg_db . "' user='" . $pg_lgn . "' password='" . $pg_pwd . "' host='" . $pg_host . "'");
if (!$conn) {
    echo "Not connected";
    exit;
}

/* Insertion du tube */
$sql = "
insert into c_template.tubes (tube_id, tube_nom, typo_id, tube_ville, tube_image, geom) 
select 
	max(tube_id) + 1 as tube_id,
	'".$nom."' as tube_nom,
	(SELECT typo_id FROM c_template.tubes_typos WHERE typo_description = '".$type."') as typo_id,
	'TODO!' as tube_ville,
	'".pg_escape_bytea($image)."',
	ST_Transform(ST_SetSrid(ST_MakePoint(".$lng.", ".$lat."), 4326), 2154)
from c_template.tubes;

-- Sélection des attributs du nouveau tube pour les renvoyer 
select *
from c_template.tubes_mef
order by tube_id desc 
limit 1;
";

$res = pg_query($conn, $sql);
if (!$res) {
    echo "Erreure de mise à jour.";
    exit;
}

$tube_attributs = array();
while ($row = pg_fetch_assoc( $res )) {
  $tube_attributs[] = $row;
}

/* Export en JSON */
header('Content-Type: application/json');
echo json_encode($tube_attributs);
?>    
