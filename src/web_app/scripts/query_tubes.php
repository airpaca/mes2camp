<?php 

/* Récupération de variables */
$tube_id = $_GET['tube_id'];
$user = $_GET['user'];

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

/* [0] Sélection du nombre de polluants mesurés par le tube */
$sql = "
/** 
Combien de polluants sont mesurés
*/
select count(*) as nb_poll
from (
    select distinct id_polluant
    from " . $sch . ".mesures
    where tube_id = " . $tube_id . "
) as a;
";

$res = pg_query($conn, $sql);
if (!$res) {
    echo "An SQL error occured.\n";
    exit;
}

$array_nb_poll = array();
while ($row = pg_fetch_assoc( $res )) {
  $array_nb_poll[] = $row;
}

/* [1] Mesures de NO2 */
$sql = "
/** 
Récupération des valeurs NO2 pour un tube 
*/
-- Sélection du tube
with tube as (
	select 
		id_polluant, nom_polluant, tube_id, tube_nom, id_periode, nom_periode, 
		id_unite, nom_unite, val, val_corrigee
	from c_template.mesures as a
	left join " . $sch . ".polluants as b using (id_polluant)
	left join " . $sch . ".tubes as c using (tube_id)
	left join " . $sch . ".mesures_periodes as d using (id_periode)
	left join " . $sch . ".unites as e using (id_unite)
	where 
		tube_id = " . $tube_id . " 
		and id_polluant = 1	
)

-- Mise en forme des valeurs été / hivert brutes
select 
	id_polluant, nom_polluant, tube_id, tube_nom, id_periode, 
	nom_periode || ' - brute' as nom_periode, 
	id_unite, nom_unite, val as val
from tube
where id_periode in (1, 2)

union all

-- Mise en forme des valeurs été / hivert corrigées
select 
	id_polluant, nom_polluant, tube_id, tube_nom, id_periode, 
	nom_periode || ' - corrigee' as nom_periode, 
	id_unite, nom_unite, val_corrigee as val
from tube
where id_periode in (1, 2)

union all

-- Mise en forme des valeurs annuelles selon correction
select 
	id_polluant, nom_polluant, tube_id, tube_nom, id_periode, 
	'Annuel - ' || nom_periode as nom_periode,
	id_unite, nom_unite, val_corrigee as val
from tube
where id_periode in (3, 4)

-- Classement
order by id_polluant, tube_id, id_periode
;
";

$res = pg_query($conn, $sql);
if (!$res) {
    echo "An SQL error occured.\n";
    exit;
}

$array_no2 = array();
while ($row = pg_fetch_assoc( $res )) {
  $array_no2[] = $row;
}

/* [2] Mesures BTEX */
$sql = "
select 
	id_polluant, nom_polluant, tube_id, tube_nom, id_periode, nom_periode, 
	id_unite, nom_unite, val, val_corrigee
from c_template.mesures as a
left join " . $sch . ".polluants as b using (id_polluant)
left join " . $sch . ".tubes as c using (tube_id)
left join " . $sch . ".mesures_periodes as d using (id_periode)
left join " . $sch . ".unites as e using (id_unite)
where 
	tube_id = " . $tube_id . "
	and id_polluant in (2,3,4,5,6)
order by id_polluant, tube_id, id_periode;
";

$res = pg_query($conn, $sql);
if (!$res) {
    echo "An SQL error occured.\n";
    exit;
}

$array_btex = array();
while ($row = pg_fetch_assoc( $res )) {
  $array_btex[] = $row;
}

/* [3] Mesures BTEX tous tubes */
$sql = "
select *
from (
	select 'btex' as polluant, tube_id, tube_nom, nom_unite, sum(val) as val 
	from c_template.mesures as a
	left join " . $sch . ".tubes as b using (tube_id)
    left join " . $sch . ".unites as c using (id_unite)
	where id_polluant in (2,3,4,5,6)
	group by tube_id, tube_nom, nom_unite
) as a
order by val desc;
";

$res = pg_query($conn, $sql);
if (!$res) {
    echo "An SQL error occured.\n";
    exit;
}

$array_btex_tous = array();
while ($row = pg_fetch_assoc( $res )) {
  $array_btex_tous[] = $row;
}

/* [4] Mesures NO2 tous tubes */
$sql = "
select *
from (
	select 'no2' as polluant, tube_id, tube_nom, nom_unite, sum(val_corrigee) as val 
	from c_template.mesures as a
	left join " . $sch . ".tubes as b using (tube_id)
    left join " . $sch . ".unites as c using (id_unite)
	where id_polluant in (1) and id_periode = 4
	group by tube_id, tube_nom, nom_unite
) as a
order by val desc;
";

$res = pg_query($conn, $sql);
if (!$res) {
    echo "An SQL error occured.\n";
    exit;
}

$array_no2_tous = array();
while ($row = pg_fetch_assoc( $res )) {
  $array_no2_tous[] = $row;
}

/* Stockage des résultats */
$array_result = array(
    $array_nb_poll,
    $array_no2,
    $array_btex,
    $array_btex_tous,
    $array_no2_tous,
);

/* Export en JSON */
header('Content-Type: application/json');
echo json_encode($array_result);

?>
