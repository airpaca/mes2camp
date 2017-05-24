<?php

function sizeUpload($tmp_name,$poids){
	if((empty($tmp_name))or($poids > 2000000)){
		$GLOBALS["error"] = true;
		return "Fichier trop lourd ou impossible à uploader";
	}
}


function cleanNameFile($name){
	$pattern =  array(
		' ' => '-',
		'à' => 'a',
		'á' => 'a',
		'Á' => 'a',
		'À' => 'a',
		'ă' => 'a',
		'Ă' => 'a',
		'â' => 'a',
		'Â' => 'a',
		'å' => 'a',
		'Å' => 'a',
		'ä' => 'a',
		'Ä' => 'a',
		'ã' => 'a',
		'Ã' => 'a',
		'ą' => 'a',
		'Ą' => 'a',
		'ā' => 'a',
		'Ā' => 'a',
		'æ' => 'ae',
		'Æ' => 'ae',
		'ḃ' => 'b',
		'Ḃ' => 'b',
		'ć' => 'c',
		'Ć' => 'c',
		'ĉ' => 'c',
		'Ĉ' => 'c',
		'č' => 'c',
		'Č' => 'c',
		'ċ' => 'c',
		'Ċ' => 'c',
		'ç' => 'c',
		'Ç' => 'c',
		'ď' => 'd',
		'Ď' => 'd',
		'ḋ' => 'd',
		'Ḋ' => 'd',
		'đ' => 'd',
		'Đ' => 'd',
		'ð' => 'dh',
		'Ð' => 'dh',
		'é' => 'e',
		'É' => 'e',
		'è' => 'e',
		'È' => 'e',
		'ĕ' => 'e',
		'Ĕ' => 'e',
		'ê' => 'e',
		'Ê' => 'e',
		'ě' => 'e',
		'Ě' => 'e',
		'ë' => 'e',
		'Ë' => 'e',
		'ė' => 'e',
		'Ė' => 'e',
		'ę' => 'e',
		'Ę' => 'e',
		'ē' => 'e',
		'Ē' => 'e',
		'ḟ' => 'f',
		'Ḟ' => 'f',
		'ƒ' => 'f',
		'Ƒ' => 'f',
		'ğ' => 'g',
		'Ğ' => 'g',
		'ĝ' => 'g',
		'Ĝ' => 'g',
		'ġ' => 'g',
		'Ġ' => 'g',
		'ģ' => 'g',
		'Ģ' => 'g',
		'ĥ' => 'h',
		'Ĥ' => 'h',
		'ħ' => 'h',
		'Ħ' => 'h',
		'İ' => 'Ii',
		'í' => 'i',
		'Í' => 'i',
		'ì' => 'i',
		'Ì' => 'i',
		'î' => 'i',
		'Î' => 'i',
		'ï' => 'i',
		'Ï' => 'i',
		'ĩ' => 'i',
		'Ĩ' => 'i',
		'į' => 'i',
		'Į' => 'i',
		'ī' => 'i',
		'Ī' => 'i',
		'ı' => 'i',
		'ĵ' => 'j',
		'Ĵ' => 'j',
		'ķ' => 'k',
		'Ķ' => 'k',
		'ĺ' => 'l',
		'Ĺ' => 'l',
		'ľ' => 'l',
		'Ľ' => 'l',
		'ļ' => 'l',
		'Ļ' => 'l',
		'ł' => 'l',
		'Ł' => 'l',
		'ṁ' => 'm',
		'Ṁ' => 'm',
		'ń' => 'n',
		'Ń' => 'n',
		'ň' => 'n',
		'Ň' => 'n',
		'ñ' => 'n',
		'Ñ' => 'n',
		'ņ' => 'n',
		'Ņ' => 'n',
		'ó' => 'o',
		'Ó' => 'o',
		'ò' => 'o',
		'Ò' => 'o',
		'ô' => 'o',
		'Ô' => 'o',
		'ö' => 'o',
		'Ö' => 'o',
		'ő' => 'o',
		'Ő' => 'o',
		'õ' => 'o',
		'Õ' => 'o',
		'ø' => 'o',
		'Ø' => 'o',
		'ō' => 'o',
		'Ō' => 'o',
		'ơ' => 'o',
		'Ơ' => 'o',
		'ṗ' => 'p',
		'Ṗ' => 'p',
		'ŕ' => 'r',
		'Ŕ' => 'r',
		'ř' => 'r',
		'Ř' => 'r',
		'ŗ' => 'r',
		'Ŗ' => 'r',
		'ś' => 's',
		'Ś' => 's',
		'ŝ' => 's',
		'Ŝ' => 's',
		'š' => 's',
		'Š' => 's',
		'ṡ' => 's',
		'Ṡ' => 's',
		'ş' => 's',
		'Ş' => 's',
		'ș' => 's',
		'Ș' => 's',
		'ß' => 'ss',
		'ť' => 't',
		'Ť' => 't',
		'ṫ' => 't',
		'Ṫ' => 't',
		'ţ' => 't',
		'Ţ' => 't',
		'ț' => 't',
		'Ț' => 't',
		'ŧ' => 't',
		'Ŧ' => 't',
		'ú' => 'u',
		'Ú' => 'u',
		'ù' => 'u',
		'Ù' => 'u',
		'ŭ' => 'u',
		'Ŭ' => 'u',
		'û' => 'u',
		'Û' => 'u',
		'ů' => 'u',
		'Ů' => 'u',
		'ü' => 'u',
		'Ü' => 'u',
		'ű' => 'u',
		'Ű' => 'u',
		'ũ' => 'u',
		'Ũ' => 'u',
		'ų' => 'u',
		'Ų' => 'u',
		'ū' => 'u',
		'Ū' => 'u',
		'ư' => 'u',
		'Ư' => 'u',
		'ẃ' => 'w',
		'Ẃ' => 'w',
		'ẁ' => 'w',
		'Ẁ' => 'w',
		'ŵ' => 'w',
		'Ŵ' => 'w',
		'ẅ' => 'w',
		'Ẅ' => 'w',
		'ý' => 'y',
		'Ý' => 'y',
		'ỳ' => 'y',
		'Ỳ' => 'y',
		'ŷ' => 'y',
		'Ŷ' => 'y',
		'ÿ' => 'y',
		'Ÿ' => 'y',
		'ź' => 'z',
		'Ź' => 'z',
		'ž' => 'z',
		'Ž' => 'z',
		'ż' => 'z',
		'Ż' => 'z',
		'þ' => 'th',
		'Þ' => 'th',
		'µ' => 'u'
	);
	$marquage = uniqid();
	return $marquage."-".strtr($name,$pattern);
}


function upload($photo,$nom_photo,$directory,$new_largeur,$new_hauteur){	
	
	$destination = $directory."/".$nom_photo;
	
	if(is_uploaded_file($photo)){
		list($largeur,$hauteur,$type,$attr) = getimagesize($photo);
		
		if($type == 2){
			
			if(move_uploaded_file($photo,$destination)){
				
				if($largeur > $new_largeur){
					
					$new = imagecreatetruecolor($new_largeur,$new_hauteur);
					$copie = imagecreatefromjpeg($destination);
					
					$coef = min($largeur/$new_largeur,$hauteur/$new_hauteur);
					$deltax = $largeur-($coef * $new_largeur); 
					$deltay = $hauteur-($coef * $new_hauteur);

					if(imagecopyresampled($new,$copie,0,0,$deltax/2,$deltay/2,$new_largeur,$new_hauteur,$largeur-$deltax,$hauteur-$deltay)){	
						
						if(imagejpeg($new,$destination)){
							imagedestroy($copie);
						}
					}
				}
					
			}else{
				echo "<p class=\"error\">Le fichier n'a pas pu être enregistré au bon endroit</p>";
			}
		}else{
			echo "<p class=\"error\">Votre fichier n'est pas un jpg</p>";
		}
	}else{
		echo "<p class=\"error\">Erreur lors de l'upload du fichier</p>";
	}
}


function uploadPDF($fiche,$nom_fiche,$directory){	
	
	$destination = $directory."/".$nom_fiche;
	
	if(is_uploaded_file($fiche)){

		if(move_uploaded_file($fiche,$destination)){	
		}
	}
}


function suppFile($dossier, $file_name){
	
	$chemin = $dossier."/".$file_name;
	unlink($chemin);

}
?>