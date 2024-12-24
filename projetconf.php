<?php
include_once 'connectbdd.php';
include_once 'addon/simplehtmldom_1_9_1/simple_html_dom.php';

function isInDatabase($city, $country, $pdo) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM biblio._city WHERE namecity = :city AND namecountry = :country");
    $stmt->bindParam(':city', $city);
    $stmt->bindParam(':country', $country);
    $stmt->execute();
    $count = $stmt->fetchColumn();
    return $count > 0;
}

$query_vp = "SELECT namecity, namecountry FROM biblio._city";
    // Préparation de la requête SQL
    $stmt_vp = $db->prepare($query_vp);

    // Exécuter la requête SQL
    $stmt_vp->execute();
    
    // Récupérer les résultats
    $result_vp = $stmt_vp->fetchAll(PDO::FETCH_ASSOC);

$query_conf= "SELECT acronym, rankc FROM biblio._conf";
    // Préparation de la requête SQL
    $stmt_conf = $db->prepare($query_conf);

    // Exécuter la requête SQL
    $stmt_conf->execute();

    // Récupérer les résultats
    $result_conf = $stmt_conf->fetchAll(PDO::FETCH_ASSOC);


function findRank($inputString, $dataconf){
    if ($dataconf && is_array($dataconf)) {
        // Parcourir les résultats de la requête
        foreach ($dataconf as $row) {
            // Vérifier si le nom de la conférence est présent dans la chaîne d'entrée
            if (!empty($row["acronym"])) {
                if (strpos($inputString, $row["acronym"]) !== false) {
                    $rank=$row["rankc"];
                    if ($rank=="A*"){
                        $rankval = 6;
                     }elseif ($rank=="A"){
                        $rankval = 4;
                    }elseif ($rank=="B"){
                        $rankval = 2;
                    }elseif ($rank=="C"){
                        $rankval = 1;
                    }else {
                        $rankval = 0;
                    }
                    return $rankval;
                }
            }
        }
    }
    return 0;
}

function findCityAndCountry($res, $inputString, $datavp) {
    if ($datavp && is_array($datavp)) {
        // Parcourir les résultats de la requête
        foreach ($datavp as $row) {
            // Vérifier si le nom de la ville est présent dans la chaîne d'entrée
            if (strpos(strtolower($inputString), strtolower($row["namecity"])) !== false) {
                
                if (strpos(strtolower($inputString), strtolower($row["namecountry"])) !== false) {
                    $ecrc = $row["namecity"];
                    $ecrp = $row["namecountry"];
                    //error_log("$ecrc, $ecrp", 0);
                    // Retourner la ville et le pays correspondants
                    return array(
                        'city' => $row["namecity"],
                        'country' => $row["namecountry"]
                    );
                }
            }
        }
    }
    error_log("$res: pas trouvé, $inputString", 0);
    // Si aucune correspondance n'est trouvée, retourner un tableau vide
    return array();
}

///debut first requete api
if (isset($_GET["REQ"])) {
    $req = $_GET["REQ"];
    if (isset($_GET["numberInput"])) {
        $nb = $_GET["numberInput"];
        $url = "https://dblp.org/search/publ/api?q=$req&format=json&h=$nb";
    }else {
        // URL à partir de laquelle récupérer le contenu JSON h max = 1000 
        $url = "https://dblp.org/search/publ/api?q=$req&format=json&h=100";
    }
    

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $json = curl_exec($ch);
    $data = json_decode($json, true);

}else {
    $req="artificial";
    $nb=100;
}

if (!isset($data)  and isset($_GET["REQ"])) {
    echo "error dblp api non accessible";
}elseif (!isset($data)){

}else{
    $res = 0;
    $resC = 0;
    $resCc = 0;
    $resE = 0;
    $resEc = 0;
    for ($i=0; $i < count($data['result']['hits']['hit']); $i++) {
        //echo "$res";
        $res += 1;
        //error_log("$res", 0);
        if ($data['result']['hits']['hit'][$i]['info']['type']=="Conference and Workshop Papers" ) { //Cas conference normal
            sleep(1);
            $resC += 1;
            $ville = "";
            $pays = "";
            //error_log("$res", 0);
            $autor=[];
            if (isset($data['result']['hits']['hit'][$i]['info']['authors']['author'])) {
                if (isset($data['result']['hits']['hit'][$i]['info']['authors']['author']["@pid"])) { // un seul auteur
                    array_push($autor, array($data['result']['hits']['hit'][$i]['info']['authors']['author']['@pid'],$data['result']['hits']['hit'][$i]['info']['authors']['author']['text']));
                }
                else { //plusieurs acteurs
                    for ($j=0; $j < count($data['result']['hits']['hit'][$i]['info']['authors']['author']); $j++) { 
                        array_push($autor, array($data['result']['hits']['hit'][$i]['info']['authors']['author'][$j]['@pid'],$data['result']['hits']['hit'][$i]['info']['authors']['author'][$j]['text']));
                    }
                }
            }
            
            if (isset($data['result']['hits']['hit'][$i]['info']['pages'])){
                $pages = explode("-", $data['result']['hits']['hit'][$i]['info']['pages']);
                $minPages = $pages[0];
                if (strpos($data['result']['hits']['hit'][$i]['info']['pages'], "-") !== false) {
                    $maxPages = $pages[1];
                }else {
                    $maxPages = $pages[0];
                }
            }else {
                $minPages = null;
                $maxPages = null;
            }

            if (isset($data['result']['hits']['hit'][$i]['info']['doi'])) {
                $doi_rec = $data['result']['hits']['hit'][$i]['info']['doi'];
            }else {
                $doi_rec = "0";
            }
            ////////////////////// URL 1 //////////////////////////
            $url = $data['result']['hits']['hit'][$i]['info']['url'];
            
            $html = file_get_contents($url);
            // Analyse du contenu HTML
            if (isset($html) && !empty($html)) {
                $dom = str_get_html($html);
            }else {
                $dom = null;
            }
            
            // Recherche de la div avec la classe 'tts-content'
            if (!is_bool($dom) && isset($dom) && $dom != null) {
                $divConfPres = $dom->find('cite.data.tts-content', 0);
            }
            
            if (isset($divConfPres) && !is_bool($divConfPres) && !empty($divConfPres) && $divConfPres!=null && is_object($divConfPres)) {
                
                foreach ($divConfPres->find('span') as $span) {
                    $span->outertext = '';
                }
                
                ob_start(); // Démarre la mise en mémoire tampon de la sortie
                echo $divConfPres;
                $aaaa = ob_get_clean(); // Récupère le contenu du tampon et le stocke dans $aaaa
                //$pattern = '/<a\s+(?:[^>]*?\s+)?href=([\'"])(.*?)\1/'; //  à changer en recup uniq href
                $pattern = '/<a\s+[^>]*?href=([\'"])(.*?)\1[^>]*?>/';
                
                // Recherche de l'attribut href dans la chaîne HTML
                if (preg_match($pattern, $aaaa, $matches)) {
                    // $matches[2] contient la valeur de l'attribut href
                    $hrefValue = $matches[2];
                    //echo "h = $hrefValue ,";
                }
                
                if ($hrefValue) {
                    
                    ///////////////// URL 2 //////////////////////
                    $html_t = file_get_contents($hrefValue);
                    
                    // Analyse du contenu HTML
                    $dom_t = str_get_html($html_t);
                    // Vérification si l'analyse a réussi
                    if (!$dom_t) {
                        libxml_use_internal_errors(true);
                        $dom_t = new DOMDocument();
                        $dom_t->loadHTML($html_t);
                        // Récupérer tous les éléments <h1>
                        $h1Tags = $dom_t->getElementsByTagName('h1');

                        // Vérifier s'il y a au moins un élément <h1>
                        if ($h1Tags->length > 0) {
                            // Récupérer le premier élément <h1>
                            $firstH1 = $h1Tags->item(0);
                            $divh1 = $firstH1->textContent;
                            // Afficher le contenu de la balise <h1>
                            //echo "<a href=\"$hrefValue\"> $divh1 </a><br>";
                            if ($divh1) {
                                $result = findCityAndCountry($res, $divh1, $result_vp);
                                if (isset($result['city']) && isset($result['country'])) {
                                    $ville = $result['city'];
                                    $pays = $result['country'];
                                }
                                $rankc = findRank($divh1, $result_conf);
                                if ($ville == "Catalonia") {
                                    $ville = "Barcelona";
                                } elseif ($ville == "New York City") {
                                    $ville = "New York";
                                } elseif ($ville == "Brandenburg an der Havel") {
                                    $ville = "Brandenburg";
                                }
                                if ($pays == "USA") {
                                    $pays = "United States";
                                }elseif ($pays == "The Netherlands") {
                                    $pays="Netherlands";
                                }elseif ($pays == "UK") {
                                    $pays="United Kingdom";
                                }
                            }
                            
                        } else {
                            echo "Aucune balise h1 trouvée.";
                        }

                        // Rétablir les avertissements de libxml
                        libxml_clear_errors();
                        libxml_use_internal_errors(false);
                    }else{
                        // Recherche de la div avec la classe 'h1'
                        $divh1 = $dom_t->find('h1', 0);
                        $divh1 = strip_tags($divh1);
                        //echo "<a href=\"$hrefValue\"> $divh1 </a><br>";
                        if ($divh1) {
                            //$divh1 = str_replace("\xC2\xA0", ' ', $divh1);

                            $result = findCityAndCountry($res, $divh1, $result_vp);
                            if (isset($result['city']) && isset($result['country'])) {
                                $ville = $result['city'];
                                $pays = $result['country'];
                            }
                            $rankc = findRank($divh1, $result_conf);
                            if ($ville == "Catalonia") {
                                $ville = "Barcelona";
                            } elseif ($ville == "New York City") {
                                $ville = "New York";
                            } elseif ($ville == "Brandenburg an der Havel") {
                                $ville = "Brandenburg";
                            }
                            if ($pays == "USA") {
                                $pays = "United States";
                            }elseif ($pays == "The Netherlands") {
                                $pays="Netherlands";
                            }elseif ($pays == "UK") {
                                $pays="United Kingdom";
                            }
                        }
                    }
                }
            }
         
            if (isset($dom) && !empty($dom)) {
                $dom->clear();
                unset($dom);
            }
            $info = array(
                'iddoc' => $data['result']['hits']['hit'][$i]['@id'],
                'type' => $data['result']['hits']['hit'][$i]['info']['type'],
                'authors' => $autor,
                'title' => $data['result']['hits']['hit'][$i]['info']['title'],
                'venue' => $data['result']['hits']['hit'][$i]['info']['venue'],
                'minpages' => $minPages,
                'maxpages' => $maxPages,
                'year' => $data['result']['hits']['hit'][$i]['info']['year'],
                'doi' => $doi_rec,
                'ee' => $data['result']['hits']['hit'][$i]['info']['ee'],
                'url' => $data['result']['hits']['hit'][$i]['info']['url'],
            );
            // $conf = array(
            //     'venue' => $data['result']['hits']['hit'][$i]['info']['venue'],
            //     'year' => $data['result']['hits']['hit'][$i]['info']['year']
            // );

            if ($ville == "Catalonia") {
                $ville = "Barcelona";
            } elseif ($ville == "New York City") {
                $ville = "New York";
            }

            if ($pays == "USA") {
                $pays="United States";
            }elseif ($pays == "The Netherlands") {
                $pays="Netherlands";
            }elseif ($pays == "UK") {
                $pays="United Kingdom";
            }

            $edit = array(
                'venue' => $data['result']['hits']['hit'][$i]['info']['venue'],
                'year' => $data['result']['hits']['hit'][$i]['info']['year'],
                'city' => $ville,
                'country' => $pays,
                'rank' => $rankc
            );
            if (isInDatabase($edit['city'], $edit['country'], $db)) {
                $resCc +=1;
            }
            $edits[]=$edit;
            //print_r($edit);
            // $confs[]=$conf;
            $infos[]=$info;
            //echo "<br><br>";
        }elseif ($data['result']['hits']['hit'][$i]['info']['type']=="Editorship") { //Cas conference bis
            sleep(0.5);
            $resE += 1;
            $autor=[];
            $ville ="";
            $pays = "";
            if (isset($data['result']['hits']['hit'][$i]['info']['authors']['author'])) {
                if (isset($data['result']['hits']['hit'][$i]['info']['authors']['author']["@pid"])) { // un seul auteur
                    array_push($autor, array($data['result']['hits']['hit'][$i]['info']['authors']['author']['@pid'],$data['result']['hits']['hit'][$i]['info']['authors']['author']['text']));
                }
                else { //plusieurs acteurs
                    for ($j=0; $j < count($data['result']['hits']['hit'][$i]['info']['authors']['author']); $j++) { 
                        array_push($autor, array($data['result']['hits']['hit'][$i]['info']['authors']['author'][$j]['@pid'],$data['result']['hits']['hit'][$i]['info']['authors']['author'][$j]['text']));
                    }
                }
            }
            
            
            if (isset($data['result']['hits']['hit'][$i]['info']['pages'])){
                $pages = explode("-", $data['result']['hits']['hit'][$i]['info']['pages']);
                $minPages = $pages[0];
                if (strpos($data['result']['hits']['hit'][$i]['info']['pages'], "-") !== false) {
                    $maxPages = $pages[1];
                }else {
                    $maxPages = $pages[0];
                }
            }else {
                $minPages = null;
                $maxPages = null;
            }

            if (isset($data['result']['hits']['hit'][$i]['info']['doi'])) {
                $doi_rec = $data['result']['hits']['hit'][$i]['info']['doi'];
            }else {
                $doi_rec = "0";
            }

            if (isset($data['result']['hits']['hit'][$i]['info']['ee'])) {
                $info = array(
                    'iddoc' => $data['result']['hits']['hit'][$i]['@id'],
                    'type' => $data['result']['hits']['hit'][$i]['info']['type'],
                    'authors' => $autor,
                    'title' => $data['result']['hits']['hit'][$i]['info']['title'],
                    'venue' => $data['result']['hits']['hit'][$i]['info']['venue'],
                    'minpages' => $minPages,
                    'maxpages' => $maxPages,
                    'year' => $data['result']['hits']['hit'][$i]['info']['year'],
                    'doi' => $doi_rec,
                    'ee' => $data['result']['hits']['hit'][$i]['info']['ee'],
                    'url' => $data['result']['hits']['hit'][$i]['info']['url'],
                );
            }else {
                $info = array(
                    'iddoc' => $data['result']['hits']['hit'][$i]['@id'],
                    'type' => $data['result']['hits']['hit'][$i]['info']['type'],
                    'authors' => $autor,
                    'title' => $data['result']['hits']['hit'][$i]['info']['title'],
                    'venue' => $data['result']['hits']['hit'][$i]['info']['venue'],
                    'minpages' => $minPages,
                    'maxpages' => $maxPages,
                    'year' => $data['result']['hits']['hit'][$i]['info']['year'],
                    'doi' => $doi_rec,
                    'url' => $data['result']['hits']['hit'][$i]['info']['url'],
                );
            }
            

            $string_title = $info['title'];
            $result = findCityAndCountry($res, $string_title, $result_vp);
            if (isset($result['city']) && isset($result['country'])) {
                $ville = $result['city'];
                $pays = $result['country'];
            }
            $rankc = findRank($string_title, $result_conf);
            
            if ($ville == "Catalonia") {
                $ville = "Barcelona";
            } elseif ($ville == "New York City") {
                $ville = "New York";
            }

            if ($pays == "USA") {
                $pays="United States";
            }elseif ($pays == "The Netherlands") {
                $pays="Netherlands";
            }elseif ($pays == "UK") {
                $pays="United Kingdom";
            }

            $year_xp = $data['result']['hits']['hit'][$i]['info']['year'];
            $venue_xp = $data['result']['hits']['hit'][$i]['info']['venue'];
            //echo " $year_xp, $ville, $pays <br>";
            $edit = array(
                'venue' => $data['result']['hits']['hit'][$i]['info']['venue'],
                'year' => $data['result']['hits']['hit'][$i]['info']['year'],
                'city' => $ville,
                'country' => $pays,
                'rank' => $rankc
            );
            $edits[]=$edit;
            if (isInDatabase($edit['city'], $edit['country'], $db)) {
                $resEc +=1;
            }
            $infos[]=$info;
        }
        elseif ($data['result']['hits']['hit'][$i]['info']['type']=="Journal Articles") { //Cas Article 
            $autor=[];
            if (isset($data['result']['hits']['hit'][$i]['info']['authors']['author'])) {
                if (isset($data['result']['hits']['hit'][$i]['info']['authors']['author']["@pid"])) { // un seul auteur
                    array_push($autor, array($data['result']['hits']['hit'][$i]['info']['authors']['author']['@pid'],$data['result']['hits']['hit'][$i]['info']['authors']['author']['text']));
                }
                else { //plusieurs acteurs
                    for ($j=0; $j < count($data['result']['hits']['hit'][$i]['info']['authors']['author']); $j++) { 
                        array_push($autor, array($data['result']['hits']['hit'][$i]['info']['authors']['author'][$j]['@pid'],$data['result']['hits']['hit'][$i]['info']['authors']['author'][$j]['text']));
                    }
                }
            }
            
            if (isset($data['result']['hits']['hit'][$i]['info']['pages'])){
                $pages = explode("-", $data['result']['hits']['hit'][$i]['info']['pages']);
                $minPages = $pages[0];
                if (strpos($data['result']['hits']['hit'][$i]['info']['pages'], "-") !== false) {
                    $maxPages = $pages[1];
                }else {
                    $maxPages = $pages[0];
                }
            }else {
                $minPages = null;
                $maxPages = null;
            }
            if (isset($data['result']['hits']['hit'][$i]['info']['doi'])) {
                $doi=$data['result']['hits']['hit'][$i]['info']['doi'];
            }else {
                $doi=null;
            }
            if (isset($data['result']['hits']['hit'][$i]['info']['number'])){ //Cas revue
                $number =$data['result']['hits']['hit'][$i]['info']['number'];
            }
            else { //Cas autres articles
                $number = null;
            }
            if (isset($data['result']['hits']['hit'][$i]['info']['volume'])){
                $volume = $data['result']['hits']['hit'][$i]['info']['volume'];
            }else {
                $volume = null;
            }
            
            $info = array(
                'iddoc' => $data['result']['hits']['hit'][$i]['@id'],
                'type' => $data['result']['hits']['hit'][$i]['info']['type'],
                'authors' => $autor,
                'title' => $data['result']['hits']['hit'][$i]['info']['title'],
                'venue' => $data['result']['hits']['hit'][$i]['info']['venue'],
                'volume' => $volume, 
                'number' => $number,
                'minpages' => $minPages,
                'maxpages' => $maxPages,
                'year' => $data['result']['hits']['hit'][$i]['info']['year'],
                'doi' => $doi,
                'ee' => $data['result']['hits']['hit'][$i]['info']['ee'],
                'url' => $data['result']['hits']['hit'][$i]['info']['url'],
            );
            $infos[]=$info;
        }else {
            //echo "error not journal, venue, or conferences";
        }
    }
    $_SESSION['doc']=$infos;
}

$city = array();
// $city = array(
//     'Paris' => array(
//         'France' => array(
//              '2022' => [2,0,0],
//              '2014' => [1,0,0],
//         ),
//         'United States' => array(
//             '2015' => [1,0,0],
//         )
//     ),
//     'Berlin' => array(
//         'Germany' => array(
//             '2022' => [2,0,0],
//             '2014' => [6,0,0],
//        ),
//     ),
//     'Tokyo' => array(
//         'Japan' => array(
//             '2022' => [4,0,0],
//             '2014' => [1,0,0],
//        ),
//     )
// );
function addOrUpdateCity(&$city, $cityName, $countryName, $year, $rank) {
    if (array_key_exists($cityName, $city)) {
        // If the city exists
        if (array_key_exists($countryName, $city[$cityName])) {
            // If the country exists
            if (array_key_exists($year, $city[$cityName][$countryName])) {
                // If the year exists, increment its value
                $city[$cityName][$countryName][$year][0] += 1;
            } else {
                // If the year doesn't exist, add it with value 1
                $city[$cityName][$countryName][$year] = [1, 0, 0];
            }
        } else {
            // If the country doesn't exist, add it with the year and value 1
            $city[$cityName][$countryName] = array($year => [1, 0, 0]);
        }
    } else {
        // If the city doesn't exist, add it with the country and year with value 1
        $city[$cityName] = array($countryName => array($year => [1, 0, 0]));
    }
    if ($rank > $city[$cityName][$countryName][$year][1]) {
        $city[$cityName][$countryName][$year][1] = $rank;
    }
}

//print_r($edits);
foreach ($edits as $edit) {
    addOrUpdateCity($city, $edit['city'], $edit['country'], $edit['year'], $edit['rank']);
}

$coordon= array();

function addOrUpdateCoordon(&$coordon, $city, $db) {
    foreach ($city as $cit => $cntr) {
        foreach ($cntr as $countr => $year_c) {
            $query = "SELECT lng, lat FROM biblio._city WHERE namecity = :cityName AND namecountry = :countryName";
            $stmt = $db->prepare($query);
            $stmt->execute(array(':cityName' => $cit, ':countryName' => $countr));
            // Boucler à travers les résultats de la requête
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Ajouter une sphère pour chaque résultat de la requête
                foreach ($year_c as $key => $value) {
                    $coordon[] = array('lng' => $row['lng'],'lat' => $row['lat'],'val' => $value[0],'year' => $key,'rank' => $value[1]);
                }
            }
        }
    }
}

addOrUpdateCoordon($coordon, $city, $db);


/*
if (isset($_GET['cityc']) && isset($_GET['countryc']) && !empty($_GET['cityc']) && !empty($_GET['countryc']) ) {
    $query = "SELECT lng, lat FROM biblio._city WHERE namecity = :cityName AND namecountry = :countryName";
    $stmt = $db->prepare($query);
    $stmt->execute(array(':cityName' => $_GET['cityc'], ':countryName' => $_GET['countryc']));
        // Boucler à travers les résultats de la requête
        $value = 1;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Ajouter une sphère pour chaque résultat de la requête
            $coordon[] = array('lng' => $row['lng'],'lat' => $row['lat'],'val' => $value);
        }
}else{ 
    if(isset($_GET['cityc']) && !empty($_GET['cityc']) && empty($_GET['countryc'])){
        $query = "SELECT lng, lat FROM biblio._city WHERE namecity = :cityName ";
        $stmt = $db->prepare($query);
        $stmt->execute(array(':cityName' => $_GET['cityc']));
        // Boucler à travers les résultats de la requête
        $value = 1;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Ajouter une sphère pour chaque résultat de la requête
            $coordon[] = array('lng' => $row['lng'],'lat' => $row['lat'],'val' => $value);
        }
    }
    if (isset($_GET['countryc']) && empty($_GET['cityc'])) {
        $query = "SELECT lng, lat FROM biblio._city WHERE namecountry = :countryName";
        $stmt = $db->prepare($query);
        $stmt->execute(array(':countryName' => $_GET['countryc']));
        // Boucler à travers les résultats de la requête
        $value = 1;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Ajouter une sphère pour chaque résultat de la requête
            $coordon[] = array('lng' => $row['lng'],'lat' => $row['lat'],'val' => $value);
        }
    }
}
*/
//coordonnées Editorship
/*
// Préparer la requête SQL
$query = "SELECT lng, lat FROM biblio._city WHERE namecity = :cityName AND namecountry = :countryName";
$stmt = $db->prepare($query);

// Boucler à travers chaque ville dans le tableau $city
foreach ($city as $cityName => $countries) {
    // Boucler à travers chaque pays associé à la ville
    foreach ($countries as $countryName => $value) {
        // Exécuter la requête avec le nom de la ville actuelle
        $stmt->execute(array(':cityName' => $cityName, ':countryName' => $countryName));
        // Boucler à travers les résultats de la requête
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Ajouter une sphère pour chaque résultat de la requête
            $coordon[] = array('lng' => $row['lng'],'lat' => $row['lat'],'val' => $value);
        }
    }
}
// Fermer le curseur
$stmt->closeCursor();
*/
/*
if (isset($data['result']['hits']['hit'])) {
    $cnt=count($data['result']['hits']['hit']);
}
$cntp = 1000;
/// boucle requete api
while ($cnt==1000) {
    if (isset($_GET["REQ"])) {
        $req = $_GET["REQ"];
        // URL à partir de laquelle récupérer le contenu JSON h max = 1000 et f progressif
        $url = "https://dblp.org/search/publ/api?q=$req&format=json&h=1000&f=$cntp";
        $cntp+=1000;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $json = curl_exec($ch);
        $data = json_decode($json, true);

        $t=0;
        
        if (isset($data['result']['hits']['hit'])) {
            $cnt=count($data['result']['hits']['hit']);
        }
    }
}
*/

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3D Map of world conferences</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="search">
        <h2>Form DATA API</h2>
        <form method="get" action="projetconf.php">
            <label for="champ_texte">Enter the subject :</label><br>
            <input type="text" id="champ_texte" value="<?php echo $req?>" name="REQ"><br><br>
            <label for="numberInput">Choice the range of result(more result = more time) :</label><br>
            <input type="range" id="numberInput" value="<?=$nb?>" name="numberInput" min="1" max="1000">
            <span id="selectedNumberDisplay">1</span><br>
            <input type="submit" value="Search">
        </form>
        <script>
            const numberInput = document.getElementById('numberInput');
            const selectedNumberDisplay = document.getElementById('selectedNumberDisplay');

            // Fonction pour mettre à jour l'affichage du nombre sélectionné
            function updateSelectedNumber() {
                selectedNumberDisplay.textContent = numberInput.value;
            }

            // Écouter les changements de valeur de la barre coulissante
            numberInput.addEventListener('input', updateSelectedNumber);

            // Mettre à jour l'affichage initial
            updateSelectedNumber();
            </script>

        <?php 
        if (isset($_GET["REQ"])) {
            echo "<p>c:$resCc / $resC, e:$resEc / $resE</p>";
        }
        ?>
        <h2>Manage Sphere</h2>
        <button onclick="removeSpheres()">Suppr Sphere</button>
        <button onclick="addSpheresAY(coordons)">Show all sphere</button><br><br>
    </div>
    <div class="bibtex">
        <?php
        if ($infos === null) {
            
        }else {
            for ($i=0; $i < count($infos); $i++) { ?>
                <div class="tex">
                    <h2><?php echo $infos[$i]["title"] ?></h2>
                    <button class="butt" onclick="copierBibTex(<?php echo htmlspecialchars(json_encode($infos[$i]), ENT_QUOTES, 'UTF-8'); ?>)">Copier BibTex</button>
                </div><?php
            }
        }
        ?>
    </div>
    <script>

function copierBibTex(info) {
    // Utiliser le DOI comme clé de citation si disponible, sinon utiliser l'URL
    let citationKey = info.doi && info.doi !== "0" ? info.doi : info.url;
    let bibTexString = "@article{" + citationKey + ",\n";

    for (let key in info) {
        if (info[key] !== null && key !== "iddoc") { // Exclure l'iddoc de la sortie
            if (key === "authors") {
                bibTexString += "    " + key + " = {";
                for (let i = 0; i < info[key].length; i++) {
                    if (Array.isArray(info[key][i]) && info[key][i].length === 2) {
                        bibTexString += "pid: " + info[key][i][0] + ", " + info[key][i][1];
                        if (i < info[key].length - 1) {
                            bibTexString += ", ";
                        }
                    }
                }
                bibTexString += "},\n";
            } else {
                bibTexString += "    " + key + " = {" + info[key] + "},\n";
            }
        }
    }

    bibTexString += "}";

    navigator.clipboard.writeText(bibTexString);
}
    </script>
    <div class="map">
        <div id="controls">
            <button class="butt" id="moveUp">Top</button>
            <button class="butt" id="moveDown">Bot</button>
            <button class="butt" id="moveLeft">left</button>
            <button class="butt" id="moveRight">Right</button>
        </div>
        <button id="resetButton">Reset</button>
        <div class="year-selector">
            <button id="prevYear">&lt;</button>
            <span id="selectedYear">2024</span>
            <button id="nextYear">&gt;</button>
        </div>

        <script>
            var coordons = <?php echo json_encode($coordon); ?>;

            document.addEventListener("DOMContentLoaded", function() {
                var selectedYearElement = document.getElementById("selectedYear");
                var prevYearButton = document.getElementById("prevYear");
                var nextYearButton = document.getElementById("nextYear");
                
                // Current year
                var currentYear = new Date().getFullYear();
                
                // Initial selected year
                var selectedYear = currentYear;
                selectedYearElement.textContent = selectedYear;

                function addSphere(x, y, z, color, opacity) {
                    const sphereGeometry = new THREE.SphereGeometry(1, 20, 20); // Définir la géométrie de la sphère
                    const sphereMaterial = new THREE.MeshStandardMaterial({ color: color, transparent: true, opacity: opacity }); // Définir le matériau de la sphère
                    const sphere = new THREE.Mesh(sphereGeometry, sphereMaterial); // Créer la sphère
                    sphere.position.set((x - 7) * 1.7 + 3, (-0.00*(y**2) +1.49*y - 16.10 ) , z); // Définir les coordonnées de position de la sphère
                    scene.add(sphere); // Ajouter la sphère à la scène
                }

                addSpheresYR(coordons, selectedYear);
                const ambientLight = new THREE.AmbientLight(0xffffff, 0.5); // Couleur blanche, intensité 0.5
                scene.add(ambientLight); // Ajouter l'éclairage ambiant à la scène
                
                // Function to update the selected year
                function updateSelectedYear(year) {
                    selectedYear = year;
                    selectedYearElement.textContent = selectedYear;
                    // You can add further actions here when the year changes
                }
                
                // Event listener for previous year button
                prevYearButton.addEventListener("click", function() {
                    if (selectedYear - 1 >= 1990) {
                        updateSelectedYear(selectedYear - 1);
                        removeSpheres();
                        addSpheresYR(coordons, selectedYear);
                        chgcolorrank();
                    }
                });
                
                // Event listener for next year button
                nextYearButton.addEventListener("click", function() {
                    if (selectedYear + 1 <= currentYear) {
                        updateSelectedYear(selectedYear + 1);
                        removeSpheres();
                        addSpheresYR(coordons, selectedYear);
                        chgcolorrank();
                    }
                });
            });
        </script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
        <script>
            // Créer une scène
            const scene = new THREE.Scene();
            scene.background = new THREE.Color(0xffffff);

            // Créer une caméra
            const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
            camera.position.z = 170;

            // Créer un rendu
            const renderer = new THREE.WebGLRenderer();
            renderer.setSize(window.innerWidth, window.innerHeight);
            document.body.appendChild(renderer.domElement);

            // Créer une surface plane (au lieu d'une sphère)
            const geometry = new THREE.PlaneGeometry(538, 200, 32, 32);
            const texture = new THREE.TextureLoader().load('texture-carte.jpg'); // Utilisez une texture de carte du monde
            const material = new THREE.MeshBasicMaterial({ map: texture, side: THREE.DoubleSide });
            const plane = new THREE.Mesh(geometry, material);
            scene.add(plane);

            const ambientLight = new THREE.AmbientLight(0xffffff, 0.5); // Couleur blanche, intensité 0.5
            scene.add(ambientLight); // Ajouter l'éclairage ambiant à la scène


            function degresToPi(degres) {
                return degres * Math.PI / 180;
            }

            // Fonction pour ajouter une sphère et enregistrer ses coordonnées
            function addSphere(x, y, z, color, opacity, taille) {
                const sphereGeometry = new THREE.SphereGeometry((taille+5)/5, 20, 20); // Définir la géométrie de la sphère
                const sphereMaterial = new THREE.MeshStandardMaterial({ color: color, transparent: true, opacity: opacity }); // Définir le matériau de la sphère
                const sphere = new THREE.Mesh(sphereGeometry, sphereMaterial); // Créer la sphère
                sphere.position.set(1.555 * x - 5.960493234728794, (-0.00*(y**2) +1.48*y - 16.43 ) , z); // Définir les coordonnées de position de la sphère
                scene.add(sphere); // Ajouter la sphère à la scène

                // Créer un objet texte pour afficher les coordonnées
                // const label = createLabel(`(${1.5519897144583912 * x - 7.960493234728794}, ${(-0.00*(y**2) +1.48*y - 16.43 )}, ${y})`);
                // label.position.set((x - 7) * 1.7, (-0.00*(y**2) +1.48*y - 16.43 )+ 3, z); // Positionner le label au-dessus de la sphère
                // scene.add(label); // Ajouter le label à la scène
            }

            // Fonction pour créer un objet texte (label) avec une taille de police réduite
            function createLabel(text) {
                const canvas = document.createElement('canvas');
                const context = canvas.getContext('2d');
                const fontSize = 6; // Taille de police augmentée
                context.font = fontSize + 'px Arial'; // Taille de police augmentée
                const width = context.measureText(text).width;
                const height = fontSize + 2; // Taille de l'objet texte ajustée
                canvas.width = width * 2; // Double résolution pour une meilleure netteté
                canvas.height = height * 2; // Double résolution pour une meilleure netteté
                context.font = fontSize + 'px Arial'; // Taille de police augmentée
                context.scale(2, 2); // Échelle pour prendre en compte la double résolution
                context.fillStyle = 'rgba(255, 255, 255, 0)'; // Couleur transparente pour l'arrière-plan
                context.fillRect(0, 0, width, height); // Remplir l'arrière-plan avec une couleur transparente
                context.fillStyle = 'black'; // Couleur du texte
                context.fillText(text, 0, fontSize); // Ajustement de la position de dessin du texte
                const texture = new THREE.Texture(canvas);
                texture.needsUpdate = true;
                const material = new THREE.MeshBasicMaterial({ map: texture, transparent: true, side: THREE.DoubleSide });
                const mesh = new THREE.Mesh(new THREE.PlaneGeometry(width, height), material);
                return mesh;
            }

            // Appel de la fonction pour ajouter une sphère avec couleur rouge et opacité 0.5
            //mid
            addSphere(0, 0, 0.1, 0xffffff, 0.7, 0.5); // Vous pouvez remplacer les valeurs par celles que vous souhaitez x y z
            //etalon
            // addSphere(4.2, 48.86, 0.1, 0xff0000, 0.7);
            // addSphere(2.7, 51.51, 0.1, 0xff0000, 0.7);
            // addSphere(14.5, 52.52, 0.1, 0xff0000, 0.7);
            // addSphere(22, 52.23, 0.1, 0xff0000, 0.7);
            // addSphere(37, 55.76, 0.1, 0xff0000, 0.7);
            // addSphere(144, 35.69, 0.1, 0xff0000, 0.7);

            function addSpheres(coordons) {
                // Parcourir chaque élément du tableau coordons
                for (var i = 0; i < coordons.length; i++) {
                    // Récupérer les valeurs de lng, lat et val pour l'élément actuel
                    var lng = coordons[i]['lng'];
                    var lat = coordons[i]['lat'];
                    var val = coordons[i]['val'];

                    // Exécuter la fonction addSphere() avec les valeurs récupérées
                    addSphere(lng, lat, 0.1, 0xff0000, 0.8, val); // Exemple de couleur jaune et d'opacité 0.8
                }
            }

            function addSpheresY(coordons, year) {
                // Parcourir chaque élément du tableau coordons
                for (var i = 0; i < coordons.length; i++) {
                    if (coordons[i]['year']==year) {
                        // Récupérer les valeurs de lng, lat et val pour l'élément actuel
                        var lng = coordons[i]['lng'];
                        var lat = coordons[i]['lat'];
                        var val = coordons[i]['val'];

                        // Exécuter la fonction addSphere() avec les valeurs récupérées
                        addSphere(lng, lat, 0.1, 0xff0000, 0.8, val); // Exemple de couleur jaune et d'opacité 0.8
                    }
                }
            }

            function addSpheresYR(coordons, year) {
                
                // Parcourir chaque élément du tableau coordons
                var colorRank = [
                    0x000000, //noir unknow 0
                    0x00ff00, //vert C 1
                    0xff6600,//jaune //0x87ceeb, //bleu B 2
                    0xffffff, 
                    0xff3300,//orange //0x4b0082, //violet A 4 
                    0xffffff, 
                    0x990000, //rouge A* 6
                ];
                for (var i = 0; i < coordons.length; i++) {
                    if (coordons[i]['year']==year) {
                        // Récupérer les valeurs de lng, lat et val pour l'élément actuel
                        var lng = coordons[i]['lng'];
                        var lat = coordons[i]['lat'];
                        var val = coordons[i]['val'];
                        var colrank = colorRank[coordons[i]['rank']]
                        // Exécuter la fonction addSphere() avec les valeurs récupérées
                        addSphere(lng, lat, 0.1, colrank, 0.9, val); // Exemple de couleur jaune et d'opacité 0.8
                    }
                }
            }

            function addSpheresAY(coordons) {
                removeSpheres();
                chgcolordate();
                // Colors for each year
                var col = [
                    0x00ff00,
                    0x0af500,
                    0x14eb00,
                    0x1de200,
                    0x27d800,
                    0x31ce00,
                    0x3bc400,
                    0x45ba00,
                    0x4eb100,
                    0x58a700,
                    0x629d00,
                    0x6c9300,
                    0x768900,
                    0x808000,
                    0x897600,
                    0x936c00,
                    0x9d6200,
                    0xa75800,
                    0xb14e00,
                    0xba4500,
                    0xc43b00,
                    0xce3100,
                    0xd82700,
                    0xe21d00,
                    0xeb1400,
                    0xf50a00,
                    0xff0000
                ];

                // Loop through each element of the coordons array
                for (var i = 0; i < coordons.length; i++) {
                    if (coordons[i]['year'] > 2000) {
                        var high = coordons[i]['year'] - 2000;
                        // Retrieve lng, lat, and val values for the current element
                        var lng = coordons[i]['lng'];
                        var lat = coordons[i]['lat'];
                        var val = coordons[i]['val'];

                        // Call addSphere() function with the retrieved values
                        //addSphere(x, y, z, color, opacity, taille) col[high - 1]
                        addSphere(lng, lat, high / 2, col[high - 1] , 0.9, (val + 5) / 8);
                    }
                }
            }

            // Exemple de variable PHP contenant des coordonnées
            var coordons = <?php echo json_encode($coordon); ?>;

            // Appeler la fonction addSpheres() avec les coordonnées récupérées de PHP
            //addSpheres(coordons);
            
            function removeSpheres() {
                // Parcourir tous les enfants de la scène
                for (let i = scene.children.length - 1; i >= 0; i--) {
                    const object = scene.children[i];
                    // Vérifier si l'enfant est une instance de Mesh (sphère)
                    if (object instanceof THREE.Mesh) {
                        // Supprimer l'objet de la scène
                        scene.remove(object);
                    }
                }
                // Créer une surface plane (au lieu d'une sphère)
                const geometry = new THREE.PlaneGeometry(538, 200, 32, 32);
                const texture = new THREE.TextureLoader().load('texture-carte.jpg'); // Utilisez une texture de carte du monde
                const material = new THREE.MeshBasicMaterial({ map: texture, side: THREE.DoubleSide });
                const plane = new THREE.Mesh(geometry, material);
                scene.add(plane);

                const ambientLight = new THREE.AmbientLight(0xffffff, 0.5); // Couleur blanche, intensité 0.5
                scene.add(ambientLight); // Ajouter l'éclairage ambiant à la scène
            }

            // Ajouter des écouteurs d'événements pour suivre les mouvements de la molette de la souris
            renderer.domElement.addEventListener('wheel', (event) => {
                const delta = Math.sign(event.deltaY);
                camera.position.z -= -4*delta;
            });

            // Ajouter un écouteur d'événement pour réinitialiser la position de la caméra
            document.getElementById('resetButton').addEventListener('click', () => {
                camera.position.set(0, 0, 170);
                camera.rotation.set(0, 0, 0);
                
            });

            let isDragging = false;
            let previousMousePosition = {
                x: 0,
                y: 0
            };
            renderer.domElement.addEventListener('mousedown', (event) => {
                isDragging = true;
            });
            renderer.domElement.addEventListener('mousemove', (event) => {
                const deltaMove = {
                    x: event.offsetX - previousMousePosition.x,
                    y: event.offsetY - previousMousePosition.y
                };

                if (isDragging) {
                    const deltaRotationQuaternion = new THREE.Quaternion()
                        .setFromEuler(new THREE.Euler(
                            toRadians(deltaMove.y * 0.02),
                            toRadians(deltaMove.x * 0.02),
                            0,
                            'XYZ'
                        ));

                    camera.quaternion.multiplyQuaternions(deltaRotationQuaternion, camera.quaternion);
                }

                previousMousePosition = {
                    x: event.offsetX,
                    y: event.offsetY
                };
            });
            renderer.domElement.addEventListener('mouseup', (event) => {
                isDragging = false;
            });

            // Fonction pour convertir des degrés en radians
            function toRadians(angle) {
                return angle * (Math.PI / 180);
            }

            // Fonction pour déplacer la caméra vers le haut
            function moveCameraUp() {
                camera.position.y += 1;
            }

            // Fonction pour déplacer la caméra vers le bas
            function moveCameraDown() {
                camera.position.y -= 1;
            }

            // Fonction pour déplacer la caméra vers la gauche
            function moveCameraLeft() {
                camera.position.x -= 1;
            }

            // Fonction pour déplacer la caméra vers la droite
            function moveCameraRight() {
                camera.position.x += 1;
            }

            // Ajouter des écouteurs d'événements pour les boutons de déplacement
            document.getElementById('moveUp').addEventListener('click', moveCameraUp);
            document.getElementById('moveDown').addEventListener('click', moveCameraDown);
            document.getElementById('moveLeft').addEventListener('click', moveCameraLeft);
            document.getElementById('moveRight').addEventListener('click', moveCameraRight);

            function moveCamera(event) {
                const speed = 1;
                switch (event.key) {
                    case 'ArrowUp':
                        camera.position.y += 2*speed;
                        break;
                    case 'ArrowDown':
                        camera.position.y -= 2*speed;
                        break;
                    case 'ArrowLeft':
                        camera.position.x -= 2*speed;
                        break;
                    case 'ArrowRight':
                        camera.position.x += 2*speed;
                        break;
                }
            }

            // Ajouter un écouteur d'événements pour les touches de direction
            window.addEventListener('keydown', moveCamera);

            // Boucle de rendu
            function animate() {
                requestAnimationFrame(animate);
                renderer.render(scene, camera);
            }
            animate();
        </script>
    </div>
    <div id="colorScaleContainer">
        <h1>Rank scale</h1>
        <div class="colorBox" style="background-color: #FF0000;"><p>A*</p></div>
        <div class="colorBox" style="background-color: #FFA500;"><p>A</p></div>
        <div class="colorBox" style="background-color: #FFFF00;"><p>B</p></div>
        <div class="colorBox" style="background-color: #00ff00;"><p>C</p></div>
        <div class="colorBox" style="background-color: #000000;"><p class="white">unknown</p></div>
    </div>
    <script>
    function chgcolordate() {
        var boxes = document.querySelectorAll('.colorBox p');
        boxes[0].innerText = 'now';
        boxes[0].parentNode.style.backgroundColor = '#ff0000'; // rouge
        boxes[1].innerText = '~2015';
        boxes[1].parentNode.style.backgroundColor = '#a75800'; 
        boxes[2].innerText = '~2010';
        boxes[2].parentNode.style.backgroundColor = '#6c9300'; // mid
        boxes[3].innerText = '~2005';
        boxes[3].parentNode.style.backgroundColor = '#31ce00';
        boxes[4].innerText = '~2000';
        boxes[4].parentNode.style.backgroundColor = '#00ff00'; // vert
    }
    function chgcolorrank() {
        var boxes = document.querySelectorAll('.colorBox p');
        boxes[0].innerText = 'A*';
        boxes[0].parentNode.style.backgroundColor = '#ff0000'; // rouge
        boxes[1].innerText = 'A';
        boxes[1].parentNode.style.backgroundColor = '#FFA500'; // orange
        boxes[2].innerText = 'B';
        boxes[2].parentNode.style.backgroundColor = '#FFFF00';
        boxes[3].innerText = 'C';
        boxes[3].parentNode.style.backgroundColor = '#00ff00';
        boxes[4].innerText = 'Unknown';
        boxes[4].parentNode.style.backgroundColor = '#000000';
    }
    </script>
</body>
</html>
