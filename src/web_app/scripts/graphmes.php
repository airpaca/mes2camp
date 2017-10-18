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

/* Requête SQL selon le polluant */
if ($id_polluant == 189) {
    $sql = "
    select id_point, id_compose, an, val_carto::int, val_memo
    from prod.mes_red 
    where id_point = " . $id_point . " and id_compose = " . $id_polluant . " and id_indicateur = 1
    order by id_point, id_compose, an, val_carto, val_memo
    ;
    ";
} elseif ($id_polluant == 125) {
    $sql = "   
    select 
        id_point, nom_compose, an_mesure, valeur::int, 
        case when 
            nom_campagne in ('UNIPER', 'Monaco stations') then 'Sites permanents' 
            else nom_campagne 
        end as nom_campagne    
    from prod.pm10_p904_v2017
    where id_point = " . $id_point . "
    order by id_point, nom_compose, an_mesure, nom_campagne
    ;    
    "; 
} elseif ($id_polluant == 270) {
    $sql = "   
    select 
        id_point, nom_compose, an_mesure, valeur::int, 
        case when 
            nom_campagne in ('UNIPER', 'Monaco stations') then 'Sites permanents' 
            else nom_campagne 
        end as nom_campagne    
    from prod.pm25_ma_v2017
    where id_point = " . $id_point . "
    order by id_point, nom_compose, an_mesure, nom_campagne
    ;    
    ";     
}

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
