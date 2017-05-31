--
-- PostgreSQL database dump
--

-- Dumped from database version 9.1.12
-- Dumped by pg_dump version 9.5.7

-- Started on 2017-05-31 01:40:40 PDT

SET statement_timeout = 0;
SET lock_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;
SET row_security = off;

--
-- TOC entry 7 (class 2615 OID 1564239)
-- Name: mes; Type: SCHEMA; Schema: -; Owner: -
--

CREATE SCHEMA mes;


--
-- TOC entry 8 (class 2615 OID 1564240)
-- Name: prod; Type: SCHEMA; Schema: -; Owner: -
--

CREATE SCHEMA prod;


SET search_path = prod, pg_catalog;

--
-- TOC entry 1125 (class 1255 OID 1564241)
-- Name: closest_year_val_(integer, integer, integer, integer, text, text); Type: FUNCTION; Schema: prod; Owner: -
--

CREATE FUNCTION closest_year_val_(_id_point integer, _an integer, _id_polluant integer, _id_indicateur integer, _condition text, _retour text) RETURNS integer
    LANGUAGE plpgsql
    AS $$ declare

	line record;
	result double precision;

begin

-- Sélection de l'année et du MJA le plus proche en fonction de la condition
if _condition = '<' then

	select an, val 
	into line
	from prod.mesure_an 
	where id_point = _id_point and an = (
		select max(an) 
		from prod.mesure_an  
		where id_point = _id_point and id_polluant = _id_polluant and id_indicateur = _id_indicateur and an < _an and val is not null
	);

elsif _condition = '>' then 

	select an, val 
	into line
	from prod.mesure_an 
	where id_point = _id_point and an = (
		select min(an) 
		from prod.mesure_an
		where id_point = _id_point and id_polluant = _id_polluant and id_indicateur = _id_indicateur and an > _an and val is not null
	);

else

	raise exception 'ERROR - Function closest_year_val(): Variable _condition must be ("<", ">").';

end if;


-- Retour en fonction du résultat désiré
if _retour = 'an' then
	result := line.an;
elsif _retour = 'val' then
	result := line.val;
else 
	raise exception 'ERROR - Function closest_year_val(): Variable _retour must be ("an", "val").';
end if;

-- Envoie du résultat
return result;

end $$;


--
-- TOC entry 3114 (class 0 OID 0)
-- Dependencies: 1125
-- Name: FUNCTION closest_year_val_(_id_point integer, _an integer, _id_polluant integer, _id_indicateur integer, _condition text, _retour text); Type: COMMENT; Schema: prod; Owner: -
--

COMMENT ON FUNCTION closest_year_val_(_id_point integer, _an integer, _id_polluant integer, _id_indicateur integer, _condition text, _retour text) IS '
Retrouve la mesure d''un polluant et d''un indicateur donné la plus proche d''une année (inférieure ou supérieure) pour un site
donné.

Le retour peut être null si pas d''année correspondante.

@_id_point		Identifiant du site integer
@_an			Année dont on veut recréer la valeur integer
@_id_polluant	Identifiant du polluant souhaité
@_id_indicateur Identifiant de l''indicateur souhaité
@_condition		Condition de recherche ("<" ou ">") text
@_retour		Variable à retourner ("an", "val") text


Exemple:
select prod.closest_year_val_(180, 2015, 1, 1, ''>'', ''val'');
';


--
-- TOC entry 1126 (class 1255 OID 1564242)
-- Name: point_maj(); Type: FUNCTION; Schema: prod; Owner: -
--

CREATE FUNCTION point_maj() RETURNS trigger
    LANGUAGE plpgsql
    AS $$ BEGIN

-- 	-- Vérification des contraintes sur les champs de typo
-- 	if NEW.typologie is not null and NEW.typologie not in ('T', 'U', 'R', 'P', 'O', 'I') then
-- 		raise exception 'Champ typologie non null et différent de (T, U, R, P, O, I)';
-- 	ELSIF NEW.typologie is null and NEW.situation is null and NEW.influence is NULL then
-- 		raise exception 'Au moins un des champs typologie, situation ou influence doit-être remplit';
-- 	ELSIF NEW.situation IS NOT NULL and NEW.situation not in ('Urbaine', 'Périurbaine', 'Rurale proche', 'Rurale régionale', 'nodata') then 	
-- 		raise exception 'Champ situation non null et différent de (Urbaine, Périurbaine, Rurale proche, Rurale régionale, nodata)';
-- 	ELSIF NEW.influence IS NOT NULL and NEW.influence not in ('Trafic', 'Fond', 'Industrielle') then 	
-- 		raise exception 'Champ influence non null et différent de (Trafic, Fond, Industrielle)';
-- 	end if;

	-- Calcul du champ geom à partir des données X, Y si geom null
	if NEW.geom is null then
		NEW.geom = ST_SetSRID(ST_MakePoint(NEW.x, NEW.y), 2154);
		raise info 'Champ geom calculé pour id_point %', NEW.id_point;
	elsif NEW.x is NULL and NEW.y is NULL then
		NEW.x = ST_X(NEW.geom);
		NEW.y = ST_Y(NEW.geom);
		raise info 'Champs X Y calculés pour id_point %', NEW.id_point;
	end if;

	-- Récupération de insee_comm en fonction des champs disponibles
	IF NEW.insee_comm is null then
		NEW.insee_comm = (
			SELECT insee_comm
			from prod.communes as a
			where st_intersects(NEW.geom, a.geom)
			limit 1
		);
	else
		raise exception 'Le champ insee_comm doit être null';
	end if;		

	-- Calcul du champ dep temporairement inactif,
	IF NEW.insee_comm is null OR char_length(NEW.insee_comm) <> 5 then
		raise exception 'La récupération du champ insee_comm n''a pas fonctionné correctement';
	else
		NEW.dep = left(NEW.insee_comm, 2);
		raise info 'Champ dep calculé pour le point % (%)', NEW.id_point, left(NEW.insee_comm, 2);
	end if;

    RETURN NEW;
END $$;


--
-- TOC entry 1110 (class 1255 OID 1564243)
-- Name: reglin_(integer, integer, integer); Type: FUNCTION; Schema: prod; Owner: -
--

CREATE FUNCTION reglin_(_an integer, _id_polluant integer, _id_indicateur integer) RETURNS void
    LANGUAGE plpgsql
    AS $$ 

declare 

_id_point integer;
_mesure boolean;
_an_inf integer;
_an_sup integer;
_an_closest integer;
_paramcorr RECORD; 
_result double precision;

BEGIN

For _id_point in (
	select distinct id_point 
	from prod.mesure_an 
	where _id_polluant = _id_polluant and id_indicateur = _id_indicateur and is_actif is true
) LOOP 

	_mesure = (select an from prod.mesure_an where id_point = _id_point and id_polluant = _id_polluant and id_indicateur = _id_indicateur and an = _an) = _an;
	_an_inf = prod.closest_year_val_(_id_point, _an, _id_polluant, _id_indicateur, '<', 'an'); 
	_an_sup = prod.closest_year_val_(_id_point, _an, _id_polluant, _id_indicateur, '>', 'an');
	_an_closest = case  when _mesure is true then _an
						when _an_sup is null and _an_inf is null then -999
						when _an_sup is null or _an - _an_inf < _an_sup - _an then _an_inf
						when _an_inf is null or _an - _an_inf >= _an_sup - _an then _an_sup
						end;
	

	select _an as y, 
		   _an_closest as x, 
		   regr_slope(tablecorr.y,tablecorr.x) AS a,
		   regr_intercept(tablecorr.y,tablecorr.x) AS b, 
		   regr_r2(tablecorr.y,tablecorr.x) AS rsq       
	into _paramcorr
	from (
		select id_point, val as y, a.x as x  
		from prod.mesure_an
		left join (	
			select id_point, val as x 
			from prod.mesure_an
			where id_campagne = 1 and id_polluant = _id_polluant and id_indicateur = _id_indicateur and an = _an_closest
			) as a using (id_point)
		where id_campagne = 1 and id_polluant = _id_polluant and id_indicateur = _id_indicateur and an = _an
		) as tablecorr;

insert into prod.mes_red
	select
		2017 as an_version,
		_id_point,
		_id_polluant,
		_id_indicateur,
		_an,
		CASE when _mesure is true then val
			 Else val*_paramcorr.a+_paramcorr.b
			 end as val_carto,
		case when _mesure is true then 'mesure'
			 ELSE 'reg lineaire'
			 end as val_memo,
		_an_closest as an_source
		
	from prod.mesure_an
	where id_point = _id_point and id_polluant = _id_polluant and id_indicateur = _id_indicateur and an = _an_closest;
-- 
-- raise notice '%', _result ;
END LOOP;

end $$;


SET search_path = mes, pg_catalog;

SET default_with_oids = false;

--
-- TOC entry 199 (class 1259 OID 1564244)
-- Name: campagne_periode; Type: TABLE; Schema: mes; Owner: -
--

CREATE TABLE campagne_periode (
    id_campagne integer NOT NULL,
    id_polluant smallint NOT NULL,
    id_periode smallint NOT NULL,
    debut date,
    fin date
);


--
-- TOC entry 3115 (class 0 OID 0)
-- Dependencies: 199
-- Name: TABLE campagne_periode; Type: COMMENT; Schema: mes; Owner: -
--

COMMENT ON TABLE campagne_periode IS 'table de répartition des périodes par campagne de mesure';


--
-- TOC entry 200 (class 1259 OID 1564247)
-- Name: mesure; Type: TABLE; Schema: mes; Owner: -
--

CREATE TABLE mesure (
    id_point integer NOT NULL,
    id_campagne integer NOT NULL,
    id_polluant smallint NOT NULL,
    id_periode smallint NOT NULL,
    id_indicateur smallint NOT NULL,
    mesure double precision
);


--
-- TOC entry 3116 (class 0 OID 0)
-- Dependencies: 200
-- Name: TABLE mesure; Type: COMMENT; Schema: mes; Owner: -
--

COMMENT ON TABLE mesure IS 'Table des mesures par période';


--
-- TOC entry 201 (class 1259 OID 1564250)
-- Name: periode; Type: TABLE; Schema: mes; Owner: -
--

CREATE TABLE periode (
    id_periode integer NOT NULL,
    nom_periode text
);


--
-- TOC entry 3117 (class 0 OID 0)
-- Dependencies: 201
-- Name: TABLE periode; Type: COMMENT; Schema: mes; Owner: -
--

COMMENT ON TABLE periode IS 'table des types de période de prélèvement rencontré lors des campanges de mesures
  @nom_periode -- type de période hiver moy, été moy, hiver corrigé, été corrigé, série 1 à 10 de prélèvement ordre dans l année';


SET search_path = prod, pg_catalog;

--
-- TOC entry 202 (class 1259 OID 1564256)
-- Name: campagne; Type: TABLE; Schema: prod; Owner: -
--

CREATE TABLE campagne (
    id_campagne integer NOT NULL,
    nom_campagne text,
    memo text,
    pilote_projet text,
    num_xr integer,
    code_projet text,
    an integer,
    color text DEFAULT '#585858'::text
);


--
-- TOC entry 3118 (class 0 OID 0)
-- Dependencies: 202
-- Name: TABLE campagne; Type: COMMENT; Schema: prod; Owner: -
--

COMMENT ON TABLE campagne IS 'table contenant les caractéristiques de chaque campagne
@id_campagne est par défaut un serial il prend la valeur suivante.
@pilote_projet - inital du pilote Air PACA
@num_xr -- numero de la campagne sous Xr
@code_projet -- code projet Air PACA
@an - METTRE -999 SI PLUSIEURS ANNEES
';


--
-- TOC entry 203 (class 1259 OID 1564262)
-- Name: campagne_id_campagne_seq; Type: SEQUENCE; Schema: prod; Owner: -
--

CREATE SEQUENCE campagne_id_campagne_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 3119 (class 0 OID 0)
-- Dependencies: 203
-- Name: campagne_id_campagne_seq; Type: SEQUENCE OWNED BY; Schema: prod; Owner: -
--

ALTER SEQUENCE campagne_id_campagne_seq OWNED BY campagne.id_campagne;


--
-- TOC entry 204 (class 1259 OID 1564264)
-- Name: campagne_point; Type: TABLE; Schema: prod; Owner: -
--

CREATE TABLE campagne_point (
    id_campagne integer NOT NULL,
    id_point integer NOT NULL,
    id_point_campagne text
);


--
-- TOC entry 3120 (class 0 OID 0)
-- Dependencies: 204
-- Name: TABLE campagne_point; Type: COMMENT; Schema: prod; Owner: -
--

COMMENT ON TABLE campagne_point IS 'table permettant la jointure entre les identifiants utilisés lore de la campagne et id_point de la base';


--
-- TOC entry 205 (class 1259 OID 1564270)
-- Name: communes; Type: TABLE; Schema: prod; Owner: -
--

CREATE TABLE communes (
    insee_comm character varying(5),
    id_comm integer NOT NULL,
    nom_comm character varying(100) NOT NULL,
    joli_nom_comm text,
    geom public.geometry(MultiPolygon,2154)
);


--
-- TOC entry 206 (class 1259 OID 1564276)
-- Name: indicateur; Type: TABLE; Schema: prod; Owner: -
--

CREATE TABLE indicateur (
    id_indicateur smallint NOT NULL,
    nom_indicateur text NOT NULL
);


--
-- TOC entry 3121 (class 0 OID 0)
-- Dependencies: 206
-- Name: TABLE indicateur; Type: COMMENT; Schema: prod; Owner: -
--

COMMENT ON TABLE indicateur IS '
Types d''indicateurs: MoyAn, P90.4 jour, bien caractériser le critère dans nom_indicateur...
';


--
-- TOC entry 207 (class 1259 OID 1564282)
-- Name: mes_red; Type: TABLE; Schema: prod; Owner: -
--

CREATE TABLE mes_red (
    an_version integer NOT NULL,
    id_point integer NOT NULL,
    id_polluant smallint NOT NULL,
    id_indicateur smallint NOT NULL,
    an integer NOT NULL,
    val_carto double precision,
    val_memo text,
    an_source integer NOT NULL,
    is_actif boolean
);


--
-- TOC entry 3122 (class 0 OID 0)
-- Dependencies: 207
-- Name: TABLE mes_red; Type: COMMENT; Schema: prod; Owner: -
--

COMMENT ON TABLE mes_red IS 'table contenant les valeurs annuelles redressées sur l’année la plus ressente,
   cette base correspond à la meilleure estimation disponible en chaque point,
    une table sera faite tous les ans afin de disposer d’une traçabilité 
    des données utilisées pour les cartographies
	@an_version -- annee de la version des cartographies
	@val_carto -- mesure ou mesure redressée
	@val_memo -- préciser : mesure ou reg linéaire à parit évolution ensemble des données stations
	@an_source -- préciser : année mesure utilisée
    ';


--
-- TOC entry 208 (class 1259 OID 1564288)
-- Name: mesure_an; Type: TABLE; Schema: prod; Owner: -
--

CREATE TABLE mesure_an (
    id_point integer NOT NULL,
    id_campagne integer NOT NULL,
    id_polluant smallint NOT NULL,
    id_indicateur smallint NOT NULL,
    an integer NOT NULL,
    val double precision,
    is_actif boolean,
    memo text
);


--
-- TOC entry 3123 (class 0 OID 0)
-- Dependencies: 208
-- Name: TABLE mesure_an; Type: COMMENT; Schema: prod; Owner: -
--

COMMENT ON TABLE mesure_an IS 'table contenant les valeurs annuelles issues des campagnes
@is_actif - Des évolutions structurelles importantes à proximité du point peuvent rendre inexploitable une mesure ancienne
@memo - remplir memo pour expliquer si point inactif
';


--
-- TOC entry 209 (class 1259 OID 1564294)
-- Name: point; Type: TABLE; Schema: prod; Owner: -
--

CREATE TABLE point (
    id_point integer NOT NULL,
    typologie text,
    situation text,
    influence text,
    adresse text,
    insee_comm text,
    dep text,
    carac_prox text,
    x double precision NOT NULL,
    y double precision NOT NULL,
    z double precision,
    pb_mesmod boolean,
    pb_mesmod_memo text,
    photo_url text,
    photo bytea,
    geom public.geometry(Point,2154)
);


--
-- TOC entry 3124 (class 0 OID 0)
-- Dependencies: 209
-- Name: TABLE point; Type: COMMENT; Schema: prod; Owner: -
--

COMMENT ON TABLE point IS '
Table contenant les caractéristiques de chaque point.
@id_point est par défaut un serial il prend la valeur suivante.
@typologie -- Ancient format, attention textes contraint en écriture par trigger
@situation et influence -- nouveau format , attention textes contraint en écriture par trigger
@dep -- Calculé automatiquement à partir du champ insee_comm
@carac_prox -- précision si spécificité à proximité (station service, source émission potentielle)
@x@y -- Coordonnées X et Y du point en RGF93 (EPSG 2154)
@z -- Altitude en m
@pb_mesmod -- champs issu mod permettant d’identifier les points pour lesquels la carto n’aboutit pas à un résultats satisfaisant par rapport à la mesure
@pb_mesmod_memo -- explication de l’écart mes mod si identifié
@photo_url -- lien vers la photo site
@photo -- Stockage de la photo dans la bdd
@geom -- Calculé automatiquement à partir des champs X et Y.
';


--
-- TOC entry 210 (class 1259 OID 1564300)
-- Name: polluant; Type: TABLE; Schema: prod; Owner: -
--

CREATE TABLE polluant (
    id_polluant integer NOT NULL,
    nom_polluant text NOT NULL,
    unite_mes text NOT NULL,
    id_polluant_espace integer
);


--
-- TOC entry 3125 (class 0 OID 0)
-- Dependencies: 210
-- Name: TABLE polluant; Type: COMMENT; Schema: prod; Owner: -
--

COMMENT ON TABLE polluant IS 'table contenant les caractéristiques des polluants
@id_polluant est par défaut un serial il prend la valeur suivante
@unite_mes - unité de la mesure du polluant
@id_polluant_espace sert à faire le lien avec id polluant émission
';


--
-- TOC entry 211 (class 1259 OID 1564306)
-- Name: no2_ma_2016_v2017; Type: VIEW; Schema: prod; Owner: -
--

CREATE VIEW no2_ma_2016_v2017 AS
SELECT b.id_point, p.adresse, po.nom_polluant, b.an, (round(b.val_carto))::integer AS valeur, b.val_memo, b.an_source, p.geom FROM (((SELECT m.an_version, m.id_point, m.id_polluant, m.id_indicateur, m.an, m.val_carto, m.val_memo, m.an_source, m.is_actif FROM mes_red m WHERE (((m.id_polluant = 1) AND (m.id_indicateur = 1)) AND (m.an = 2016))) b LEFT JOIN point p USING (id_point)) LEFT JOIN polluant po USING (id_polluant)) ORDER BY b.id_point;


--
-- TOC entry 212 (class 1259 OID 1564311)
-- Name: pm10_p904_2016_v2017; Type: VIEW; Schema: prod; Owner: -
--

CREATE VIEW pm10_p904_2016_v2017 AS
SELECT b.id_point, p.adresse, po.nom_polluant, (round(b.valeur))::integer AS valeur, b.an_mesure, c.nom_campagne, c.an AS annee_campagne, p.geom FROM ((((SELECT m.id_point, m.id_polluant, m.id_campagne, m.val AS valeur, m.an AS an_mesure FROM mesure_an m WHERE (((m.id_polluant = 2) AND (m.id_indicateur = 2)) AND (m.an = 2016))) b LEFT JOIN point p USING (id_point)) LEFT JOIN polluant po USING (id_polluant)) LEFT JOIN campagne c USING (id_campagne)) ORDER BY c.an, b.id_point, b.an_mesure;


--
-- TOC entry 215 (class 1259 OID 1565518)
-- Name: pm25_ma_2016_v2017; Type: VIEW; Schema: prod; Owner: -
--

CREATE VIEW pm25_ma_2016_v2017 AS
SELECT b.id_point, p.adresse, po.nom_polluant, b.an_mesure, (round(b.valeur))::integer AS valeur, c.nom_campagne, c.an AS annee_campagne, p.geom FROM ((((SELECT m.id_point, m.id_polluant, m.id_campagne, m.val AS valeur, m.an AS an_mesure FROM mesure_an m WHERE (((m.id_polluant = 3) AND (m.id_indicateur = 1)) AND (m.an = 2016))) b LEFT JOIN point p USING (id_point)) LEFT JOIN polluant po USING (id_polluant)) LEFT JOIN campagne c USING (id_campagne)) ORDER BY c.an, b.id_point, b.an_mesure;


--
-- TOC entry 213 (class 1259 OID 1564316)
-- Name: point_id_point_seq; Type: SEQUENCE; Schema: prod; Owner: -
--

CREATE SEQUENCE point_id_point_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 3126 (class 0 OID 0)
-- Dependencies: 213
-- Name: point_id_point_seq; Type: SEQUENCE OWNED BY; Schema: prod; Owner: -
--

ALTER SEQUENCE point_id_point_seq OWNED BY point.id_point;


--
-- TOC entry 216 (class 1259 OID 1568368)
-- Name: points_campagne_v2017; Type: VIEW; Schema: prod; Owner: -
--

CREATE VIEW points_campagne_v2017 AS
SELECT b.id_point, p.adresse, c.nom_campagne, c.id_campagne, c.an AS annee_campagne, c.color, CASE WHEN (no.val > 0) THEN true ELSE false END AS no2_ma, CASE WHEN (pm10p.val > 0) THEN true ELSE false END AS pm10_p904, CASE WHEN (pm25ma.val > 0) THEN true ELSE false END AS pm25_ma, p.geom FROM ((((((SELECT campagne_point.id_campagne, campagne_point.id_point, campagne_point.id_point_campagne FROM campagne_point) b LEFT JOIN point p USING (id_point)) LEFT JOIN campagne c USING (id_campagne)) LEFT JOIN (SELECT mesure_an.id_point, count(mesure_an.val) AS val FROM mesure_an WHERE (mesure_an.id_polluant = 1) GROUP BY mesure_an.id_point) no USING (id_point)) LEFT JOIN (SELECT mesure_an.id_point, count(mesure_an.val) AS val FROM mesure_an WHERE ((mesure_an.id_polluant = 2) AND (mesure_an.id_indicateur = 2)) GROUP BY mesure_an.id_point) pm10p USING (id_point)) LEFT JOIN (SELECT mesure_an.id_point, count(mesure_an.val) AS val FROM mesure_an WHERE ((mesure_an.id_polluant = 3) AND (mesure_an.id_indicateur = 1)) GROUP BY mesure_an.id_point) pm25ma USING (id_point)) ORDER BY c.an, b.id_point;


--
-- TOC entry 214 (class 1259 OID 1564318)
-- Name: polluant_id_polluant_seq; Type: SEQUENCE; Schema: prod; Owner: -
--

CREATE SEQUENCE polluant_id_polluant_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 3127 (class 0 OID 0)
-- Dependencies: 214
-- Name: polluant_id_polluant_seq; Type: SEQUENCE OWNED BY; Schema: prod; Owner: -
--

ALTER SEQUENCE polluant_id_polluant_seq OWNED BY polluant.id_polluant;


--
-- TOC entry 2936 (class 2604 OID 1564320)
-- Name: id_campagne; Type: DEFAULT; Schema: prod; Owner: -
--

ALTER TABLE ONLY campagne ALTER COLUMN id_campagne SET DEFAULT nextval('campagne_id_campagne_seq'::regclass);


--
-- TOC entry 2938 (class 2604 OID 1564321)
-- Name: id_point; Type: DEFAULT; Schema: prod; Owner: -
--

ALTER TABLE ONLY point ALTER COLUMN id_point SET DEFAULT nextval('point_id_point_seq'::regclass);


--
-- TOC entry 2939 (class 2604 OID 1564322)
-- Name: id_polluant; Type: DEFAULT; Schema: prod; Owner: -
--

ALTER TABLE ONLY polluant ALTER COLUMN id_polluant SET DEFAULT nextval('polluant_id_polluant_seq'::regclass);


SET search_path = mes, pg_catalog;

--
-- TOC entry 2941 (class 2606 OID 1565403)
-- Name: pk_campagne_polluant_periode; Type: CONSTRAINT; Schema: mes; Owner: -
--

ALTER TABLE ONLY campagne_periode
    ADD CONSTRAINT pk_campagne_polluant_periode PRIMARY KEY (id_campagne, id_polluant, id_periode);


--
-- TOC entry 2945 (class 2606 OID 1565405)
-- Name: pk_periode; Type: CONSTRAINT; Schema: mes; Owner: -
--

ALTER TABLE ONLY periode
    ADD CONSTRAINT pk_periode PRIMARY KEY (id_periode);


--
-- TOC entry 2943 (class 2606 OID 1565407)
-- Name: pk_point_campagne_polluant_periode; Type: CONSTRAINT; Schema: mes; Owner: -
--

ALTER TABLE ONLY mesure
    ADD CONSTRAINT pk_point_campagne_polluant_periode PRIMARY KEY (id_point, id_campagne, id_polluant, id_periode);


SET search_path = prod, pg_catalog;

--
-- TOC entry 2950 (class 2606 OID 1565409)
-- Name: pk__prod.indicateur; Type: CONSTRAINT; Schema: prod; Owner: -
--

ALTER TABLE ONLY indicateur
    ADD CONSTRAINT "pk__prod.indicateur" PRIMARY KEY (id_indicateur);


--
-- TOC entry 2947 (class 2606 OID 1565411)
-- Name: pk_id_campagne; Type: CONSTRAINT; Schema: prod; Owner: -
--

ALTER TABLE ONLY campagne
    ADD CONSTRAINT pk_id_campagne PRIMARY KEY (id_campagne);


--
-- TOC entry 2957 (class 2606 OID 1565413)
-- Name: pk_id_point; Type: CONSTRAINT; Schema: prod; Owner: -
--

ALTER TABLE ONLY point
    ADD CONSTRAINT pk_id_point PRIMARY KEY (id_point);


--
-- TOC entry 2959 (class 2606 OID 1565415)
-- Name: pk_id_polluant; Type: CONSTRAINT; Schema: prod; Owner: -
--

ALTER TABLE ONLY polluant
    ADD CONSTRAINT pk_id_polluant PRIMARY KEY (id_polluant);


--
-- TOC entry 2954 (class 2606 OID 1565417)
-- Name: pk_point_campagne_polluant; Type: CONSTRAINT; Schema: prod; Owner: -
--

ALTER TABLE ONLY mesure_an
    ADD CONSTRAINT pk_point_campagne_polluant PRIMARY KEY (id_point, id_campagne, id_polluant, id_indicateur, an);


--
-- TOC entry 2952 (class 2606 OID 1565419)
-- Name: pk_point_polluant_an; Type: CONSTRAINT; Schema: prod; Owner: -
--

ALTER TABLE ONLY mes_red
    ADD CONSTRAINT pk_point_polluant_an PRIMARY KEY (id_point, id_polluant, an, id_indicateur);


--
-- TOC entry 2948 (class 1259 OID 1565420)
-- Name: gidx__prod.communes; Type: INDEX; Schema: prod; Owner: -
--

CREATE INDEX "gidx__prod.communes" ON communes USING gist (geom);


--
-- TOC entry 2955 (class 1259 OID 1565421)
-- Name: gidx__prod.point; Type: INDEX; Schema: prod; Owner: -
--

CREATE INDEX "gidx__prod.point" ON point USING gist (geom);


SET search_path = mes, pg_catalog;

--
-- TOC entry 2960 (class 2606 OID 1565422)
-- Name: fk_camapgne_periode_periode; Type: FK CONSTRAINT; Schema: mes; Owner: -
--

ALTER TABLE ONLY campagne_periode
    ADD CONSTRAINT fk_camapgne_periode_periode FOREIGN KEY (id_periode) REFERENCES periode(id_periode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 2961 (class 2606 OID 1565427)
-- Name: fk_campagne_periode_campagne; Type: FK CONSTRAINT; Schema: mes; Owner: -
--

ALTER TABLE ONLY campagne_periode
    ADD CONSTRAINT fk_campagne_periode_campagne FOREIGN KEY (id_campagne) REFERENCES prod.campagne(id_campagne) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 2962 (class 2606 OID 1565432)
-- Name: fk_campagne_periode_polluant; Type: FK CONSTRAINT; Schema: mes; Owner: -
--

ALTER TABLE ONLY campagne_periode
    ADD CONSTRAINT fk_campagne_periode_polluant FOREIGN KEY (id_polluant) REFERENCES prod.polluant(id_polluant) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 2963 (class 2606 OID 1565437)
-- Name: fk_mesure_periode; Type: FK CONSTRAINT; Schema: mes; Owner: -
--

ALTER TABLE ONLY mesure
    ADD CONSTRAINT fk_mesure_periode FOREIGN KEY (id_periode) REFERENCES periode(id_periode) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 2964 (class 2606 OID 1565442)
-- Name: fk_mesure_periode_campagne; Type: FK CONSTRAINT; Schema: mes; Owner: -
--

ALTER TABLE ONLY mesure
    ADD CONSTRAINT fk_mesure_periode_campagne FOREIGN KEY (id_campagne) REFERENCES prod.campagne(id_campagne) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 2965 (class 2606 OID 1565447)
-- Name: fk_mesure_periode_indicateur; Type: FK CONSTRAINT; Schema: mes; Owner: -
--

ALTER TABLE ONLY mesure
    ADD CONSTRAINT fk_mesure_periode_indicateur FOREIGN KEY (id_indicateur) REFERENCES prod.indicateur(id_indicateur) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 2966 (class 2606 OID 1565452)
-- Name: fk_mesure_periode_point; Type: FK CONSTRAINT; Schema: mes; Owner: -
--

ALTER TABLE ONLY mesure
    ADD CONSTRAINT fk_mesure_periode_point FOREIGN KEY (id_point) REFERENCES prod.point(id_point) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 2967 (class 2606 OID 1565457)
-- Name: fk_mesure_periode_polluant; Type: FK CONSTRAINT; Schema: mes; Owner: -
--

ALTER TABLE ONLY mesure
    ADD CONSTRAINT fk_mesure_periode_polluant FOREIGN KEY (id_polluant) REFERENCES prod.polluant(id_polluant) ON UPDATE CASCADE ON DELETE RESTRICT;


SET search_path = prod, pg_catalog;

--
-- TOC entry 2968 (class 2606 OID 1565462)
-- Name: fk__camp_pt_campe; Type: FK CONSTRAINT; Schema: prod; Owner: -
--

ALTER TABLE ONLY campagne_point
    ADD CONSTRAINT fk__camp_pt_campe FOREIGN KEY (id_campagne) REFERENCES campagne(id_campagne) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 2969 (class 2606 OID 1565467)
-- Name: fk__camp_pt_pt; Type: FK CONSTRAINT; Schema: prod; Owner: -
--

ALTER TABLE ONLY campagne_point
    ADD CONSTRAINT fk__camp_pt_pt FOREIGN KEY (id_point) REFERENCES point(id_point) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 2970 (class 2606 OID 1565472)
-- Name: fk_mes_red_indicateur; Type: FK CONSTRAINT; Schema: prod; Owner: -
--

ALTER TABLE ONLY mes_red
    ADD CONSTRAINT fk_mes_red_indicateur FOREIGN KEY (id_indicateur) REFERENCES indicateur(id_indicateur) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 2971 (class 2606 OID 1565477)
-- Name: fk_mes_red_point; Type: FK CONSTRAINT; Schema: prod; Owner: -
--

ALTER TABLE ONLY mes_red
    ADD CONSTRAINT fk_mes_red_point FOREIGN KEY (id_point) REFERENCES point(id_point) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 2972 (class 2606 OID 1565482)
-- Name: fk_mes_red_polluant; Type: FK CONSTRAINT; Schema: prod; Owner: -
--

ALTER TABLE ONLY mes_red
    ADD CONSTRAINT fk_mes_red_polluant FOREIGN KEY (id_polluant) REFERENCES polluant(id_polluant) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 2973 (class 2606 OID 1565487)
-- Name: fk_mesure_an_campagne; Type: FK CONSTRAINT; Schema: prod; Owner: -
--

ALTER TABLE ONLY mesure_an
    ADD CONSTRAINT fk_mesure_an_campagne FOREIGN KEY (id_campagne) REFERENCES campagne(id_campagne) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 2974 (class 2606 OID 1565492)
-- Name: fk_mesure_an_indicateur; Type: FK CONSTRAINT; Schema: prod; Owner: -
--

ALTER TABLE ONLY mesure_an
    ADD CONSTRAINT fk_mesure_an_indicateur FOREIGN KEY (id_indicateur) REFERENCES indicateur(id_indicateur) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 2975 (class 2606 OID 1565497)
-- Name: fk_mesure_an_point; Type: FK CONSTRAINT; Schema: prod; Owner: -
--

ALTER TABLE ONLY mesure_an
    ADD CONSTRAINT fk_mesure_an_point FOREIGN KEY (id_point) REFERENCES point(id_point) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 2976 (class 2606 OID 1565502)
-- Name: fk_mesure_an_polluant; Type: FK CONSTRAINT; Schema: prod; Owner: -
--

ALTER TABLE ONLY mesure_an
    ADD CONSTRAINT fk_mesure_an_polluant FOREIGN KEY (id_polluant) REFERENCES polluant(id_polluant) ON UPDATE CASCADE ON DELETE RESTRICT;


-- Completed on 2017-05-31 01:40:47 PDT

--
-- PostgreSQL database dump complete
--

