<?php

/*********************************************************
 *        Bibliothèque de fonctions spécifiques          *
 *               à l'application Cuiteur                 *
 *********************************************************/

 // Force l'affichage des erreurs
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting( E_ALL );

// Définit le fuseau horaire par défaut à utiliser. Disponible depuis PHP 5.1
date_default_timezone_set('Europe/Paris');

//définition de l'encodage des caractères pour les expressions rationnelles multi-octets
mb_regex_encoding ('UTF-8');

define('IS_DEV', true);//true en phase de développement, false en phase de production

 // Paramètres pour accéder à la base de données
define('BD_SERVER', 'localhost');
define('BD_NAME', 'cuiteur_bd');
define('BD_USER', 'root');
define('BD_PASS', '');
/*define('BD_NAME', 'merlet_cuiteur');
define('BD_USER', 'merlet_u');
define('BD_PASS', 'merlet_p');*/

define('CLE_CRYPTAGE', 'ocKSOzNlBxCu1hsziDoUCQ==');

// paramètres de l'application
define('LMIN_PSEUDO', 4);
define('LMAX_PSEUDO', 30); //longueur du champ dans la base de données
define('LMAX_EMAIL', 80); //longueur du champ dans la base de données
define('LMAX_NOMPRENOM', 60); //longueur du champ dans la base de données
define('LMAX_WEB', 120); //longueur du champ dans la base de données
define('LMAX_VILLE', 50); //longueur du champ dans la base de données
define('LMAX_BIO', 255); //longueur du champ dans la base de données

define('LMIN_PASSWORD', 4);
define('LMAX_PASSWORD', 20);

define('AGE_MIN', 18);
define('AGE_MAX', 120);

define('BLABLAS_AFFICHE', 4);

// suggestions.php
define('SUGGESTIONS_AFFICHE', 5);
define('SUGGESTIONS_COMPLEMENT', 10);

// aside
define('NB_TENDANCES', 4);
define('NB_SUGGESTIONS_ASIDE', 2);

//_______________________________________________________________
/**
 * Génération et affichage de l'entete des pages
 *
 * @param ?string    $titre  Titre de l'entete (si null, affichage de l'entete de cuiteur.php avec le formulaire)
 * @param string     $id "deconnecter" pour changer l'arriere plan ou rien si on veut l'entete de "connecter"
 */
function em_aff_entete(?string $titre = null, string $id = null):void{
    echo '<div id="bcContenu">';
    if ($id === null) {
        echo  '<header>';
    }else {
        echo '<header id="',$id,'">';
    }
    if(em_est_authentifie()) {
                    echo '<a href="./deconnexion.php" title="Se déconnecter de cuiteur"></a>',
                        '<a href="./cuiteur.php" title="Ma page d\'accueil"></a>',
                        '<a href="./recherche.php" title="Rechercher des personnes à suivre"></a>',
                        '<a href="./compte.php" title="Modifier mes informations personnelles"></a>';
                }
    if ($titre === null){
        echo    '<form action="./cuiteur.php" method="POST">',
                    '<textarea name="txtMessage">', (isset($_GET['pseudoDestinataire']) ? $_GET['pseudoDestinataire'] : ''), '</textarea>',
                    '<input type="submit" name="btnPublier" value="" title="Publier mon message">',
                '</form>';
    }
    else{
        echo    '<h1>', $titre, '</h1>';
    }
    echo    '</header>';    
}

//_______________________________________________________________
/**
 * Génération et affichage du bloc d'informations utilisateur
 *
 * @param bool $connecte          true si l'utilisateur courant s'est authentifié, false sinon
 * @param array  $tab             tableau avec les infos de l'utlisateur courant
 */
function em_aff_infos(bool $connecte = true, ?array $tab = null):void{
    echo '<aside>';
    if ($connecte && $tab!=null){
        $id = $_SESSION['usID']; 
        echo
            '<h3>Utilisateur</h3>',
            '<ul>',
                '<li>',
                    '<img src="../', ($tab[0] == 1 && file_exists("../upload/$id.jpg") ? "upload/$id.jpg" : 'images/anonyme.jpg'), 
                    '" alt="photo de l\'auteur">',
                    em_html_a('./utilisateur.php', $tab[1], 'id', $id, 'Voir mes infos'), ' ', $tab[2], '</li>',
                '<li>', em_html_a('./blablas.php', $tab[3].($tab[3]>1 ? ' blablas' : ' blabla'), 'id', $id, 'Voir la liste de mes messages'), '</li>',
                '<li>', em_html_a('./abonnements.php', $tab[4].($tab[4]>1 ? ' abonnements' : ' abonnement'), 'id', $id, 'Voir les personnes que je suis'), '</li>',
                '<li>', em_html_a('./abonnes.php', $tab[5].($tab[5]>1 ? ' abonnés   ' : ' abonné'), 'id', $id, 'Voir les personnes qui me suivent'), '</li>',                 
            '</ul>',
            '<h3>Tendances</h3>',
            '<ul>';
                $tendances = explode(',', $tab[7]);
                foreach ($tendances as $key => $value) {
                    echo '<li>#', em_html_a('./tendances.php', $value, 'taID', $value, 'Voir les blablas contenant ce tag'), '</li>';
                }
            echo '<li><a href="./tendances.php">Toutes les tendances</a><li>',
            '</ul>',
            '<h3>Suggestions</h3>',             
            '<ul>';
                foreach ($tab[8] as $key => $value) {
                    $suggestion = explode(',', $value);
                    echo '<li>',
                        '<img src="../', ($suggestion[3] == 1 && file_exists("../upload/{$suggestion[0]}.jpg") ? "upload/{$suggestion[0]}.jpg" : 'images/anonyme.jpg'), 
                        '" alt="photo de l\'auteur">',
                        em_html_a('./utilisateur.php', $suggestion[1], 'id', $suggestion[0], 'Voir mes infos'), ' ', $suggestion[2],
                    '</li>';     
                }
                
            echo  '<li><a href="./suggestions.php">Plus de suggestions</a></li>',
            '</ul>';
    }
    echo '</aside>',
         '<main>';   
}

//_______________________________________________________________
/**
 * Génération et affichage du pied de page
 *
 */
function em_aff_pied(): void{
    echo    '</main>',
            '<footer>',
                '<a href="../index.html">A propos</a>',
                '<a href="../index.html">Publicité</a>',
                '<a href="../index.html">Patati</a>',
                '<a href="../index.html">Aide</a>',
                '<a href="../index.html">Patata</a>',
                '<a href="../index.html">Stages</a>',
                '<a href="../index.html">Emplois</a>',
                '<a href="../index.html">Confidentialité</a>',
            '</footer>',
    '</div>';
}

//_______________________________________________________________
/**
* Affichages des résultats des SELECT des blablas.
*
* La fonction gére la boucle de lecture des résultats et les
* encapsule dans du code HTML envoyé au navigateur 
* @param mysqli         $bd               Objet permettant l'accès à la bdd
* @param mysqli_result  $r                Objet permettant l'accès aux résultats de la requête SELECT
* @param string         $page             page de redirection de plus de blablas
* @param ?array         $argsUrl          arguments eventuelle de l'url
*/
function em_aff_blablas(mysqli $bd, mysqli_result $r, string $page, ?array $argsUrl = null): void {
    
    // traitement du nombre de blablas à affichés
    $nb_blablas_affiche = (isset($_GET['blablas_affiche'])) ? $_GET['blablas_affiche'] : BLABLAS_AFFICHE; 
    
    $i=0;
    while (($t = mysqli_fetch_assoc($r)) && $i < $nb_blablas_affiche ) {
        if ($t['oriID'] === null){
            $id_orig = $t['autID'];
            $pseudo_orig = $t['autPseudo'];
            $photo = $t['autPhoto'];
            $nom_orig = $t['autNom'];
        }
        else{
            $id_orig = $t['oriID'];
            $pseudo_orig = $t['oriPseudo'];
            $photo = $t['oriPhoto'];
            $nom_orig = $t['oriNom'];
        }

        $t['blTexte'] = em_html_proteger_sortie($t['blTexte']);
       
        if(isset($t['TAGS'])) {
            $t['TAGS'] = em_html_proteger_sortie($t['TAGS']);
            $TAGS = explode(',', $t['TAGS']);

            foreach ($TAGS as $key => $tag) {
                $t['blTexte'] = mb_ereg_replace("#{$tag}", '#'.em_html_a('./tendances.php', $tag, 'taID', $tag), $t['blTexte']);
            }
        }

        if(isset($t['IDMENTIONS'])) {
            $t['IDMENTIONS'] = em_html_proteger_sortie($t['IDMENTIONS']);
        
            $idMentions = explode(',', $t['IDMENTIONS']);

            foreach ($idMentions as $key => $idMention) {
                $sql = "SELECT usPseudo
                        FROM users
                        WHERE usID=$idMention";
                
                $res = em_bd_send_request($bd, $sql);
                $tabRes = em_html_proteger_sortie(mysqli_fetch_assoc($res));

                mysqli_free_result($res);

                $t['blTexte'] = mb_ereg_replace("@{$tabRes['usPseudo']}", '@'.em_html_a('./utilisateur.php', $tabRes['usPseudo'], 'id', $idMention), $t['blTexte']);
            }    
        }
       
        echo    '<li>', 
                    '<img src="../', ($photo == 1 ? "upload/$id_orig.jpg" : 'images/anonyme.jpg'), 
                    '" class="imgAuteur" alt="photo de l\'auteur">',
                    em_html_a('utilisateur.php', '<strong>'.em_html_proteger_sortie($pseudo_orig).'</strong>','id', $id_orig), 
                    ' ', em_html_proteger_sortie($nom_orig),
                    ($t['oriID'] !== null ? ', recuité par '
                                            .em_html_a( 'utilisateur.php','<strong>'.em_html_proteger_sortie($t['autPseudo']).'</strong>',
                                                        'id', $t['autID'], 'Voir mes infos') : ''),
                    '<br>',
                    $t['blTexte'],
                    '<p class="finMessage">',
                    em_amj_clair($t['blDate']), ' à ', em_heure_clair($t['blHeure']);
        if ($t['autID'] != $_SESSION['usID']) {       
                    echo em_html_a('./cuiteur.php', 'Répondre', 'pseudoDestinataire', '@'.$pseudo_orig), 
                    em_html_a('./cuiteur.php', 'Recuiter', 'blIDARecuiter', em_html_proteger_sortie($t['blID'])), '</p>';
        }else {
                    echo em_html_a('./cuiteur.php', 'Supprimer', 'id_blabla_supp', em_html_proteger_sortie($t['blID'])),'</p>';
        }
        echo    '</li>';
                
       $i++;
    }
    if($t!=null) {
        //arguments obligatoire pour le bouton plusDeblablas
        $argsUrlBase = array('blablas_affiche' => $nb_blablas_affiche+BLABLAS_AFFICHE); 

        // si arguments url en option on les fusionnents
        $argumentsUrl = ($argsUrl ? array_merge($argsUrlBase, $argsUrl) : $argsUrlBase);

        echo    '<li class="plusBlablas">',
                    em_html_a_array("$page", 'Plus de blablas', $argumentsUrl), 
                    '<img src="../images/speaker.png" alt="plus de blablas">',
                '</li>';
    }
}

//_______________________________________________________________
/**
* Détermine si l'utilisateur est authentifié
*
* @global array    $_SESSION 
* @return bool     true si l'utilisateur est authentifié, false sinon
*/
function em_est_authentifie(): bool {
    return  isset($_SESSION['usID']);
}

//_______________________________________________________________
/**
 * Termine une session et effectue une redirection vers la page transmise en paramètre
 *
 * Elle utilise :
 *   -   la fonction session_destroy() qui détruit la session existante
 *   -   la fonction session_unset() qui efface toutes les variables de session
 * Elle supprime également le cookie de session
 *
 * Cette fonction est appelée quand l'utilisateur se déconnecte "normalement" et quand une 
 * tentative de piratage est détectée. On pourrait améliorer l'application en différenciant ces
 * 2 situations. Et en cas de tentative de piratage, on pourrait faire des traitements pour 
 * stocker par exemple l'adresse IP, etc.
 * 
 * @param string    URL de la page vers laquelle l'utilisateur est redirigé
 */
function em_session_exit(string $page = '../index.php'):void {
    session_destroy();
    session_unset();
    $cookieParams = session_get_cookie_params();
    setcookie(session_name(), 
            '', 
            time() - 86400,
            $cookieParams['path'], 
            $cookieParams['domain'],
            $cookieParams['secure'],
            $cookieParams['httponly']
        );
    header("Location: $page");
    exit();
}

/**
 *  Obtenir des infos de l'utilisateur pour notamment le aside 
 *
 * @param mysqli $bd                 Objet permettant l'accès à la base de données
 * @global array $_SESSION
 *
 * @return array                     Un tableau contenant les données de la requête
 */
function agg_get_donnee_reduite_utilisateur(mysqli $bd, int $ID): array {
   
    $sql = "SELECT usAvecPhoto AS NB, 1 AS TYPE
        FROM users
        WHERE usID = $ID
        UNION
        SELECT usPseudo AS NB, 2 AS TYPE
        FROM users
        WHERE usID = $ID
        UNION
        SELECT usNom AS NB, 3 AS TYPE
        FROM users
        WHERE usID = $ID
        UNION
        SELECT COUNT(*) AS NB, 4 AS TYPE
        FROM blablas
        WHERE blIDAuteur = $ID
        UNION
        SELECT COUNT(*) AS NB, 5 AS TYPE
        FROM estabonne
        WHERE eaIDUser = $ID
        UNION
        SELECT COUNT(*) AS NB, 6 AS TYPE
        FROM estabonne
        WHERE eaIDAbonne = $ID
        UNION
        SELECT COUNT(*) AS NB, 7 AS TYPE
        FROM mentions
        WHERE meIDUser = $ID";
    
    $r = em_bd_send_request($bd, $sql);
    
    while ($t = mysqli_fetch_assoc($r)) {
        $tab[] = em_html_proteger_sortie($t['NB']);
    }
    mysqli_free_result($r);

    // tendances
    $sql = 'SELECT taID 
            FROM tags
            GROUP BY taID
            ORDER BY COUNT(taID) DESC, taID DESC
            LIMIT '.NB_TENDANCES;

    $r = em_bd_send_request($bd, $sql);
    
    $tendances = '';
    while ($t = mysqli_fetch_assoc($r)) {
        $t  = em_html_proteger_sortie($t);
        $tendances = $tendances.$t['taID'].',';
    }

    $tendances = substr($tendances, 0, -1);
    $tab[7] = $tendances;
    mysqli_free_result($r);

    // suggestions 
    // sous-requête qui prend les abonné de l'utilisateur courant puis la requête principale choisit au hasard des utlisateur qui
    // ne sont pas dans mes abonnés {$_SESSION['usID']}
    $r = agg_get_suggestions($bd, NB_SUGGESTIONS_ASIDE);
    $nbLignes = mysqli_num_rows($r);
    
    $suggestions = '';
    $usIDAlreadyPresent='';

    while ($t = mysqli_fetch_assoc($r)) {
        //$t  = em_html_proteger_sortie($t);
        $suggestions = $suggestions.$t['usID'].','.$t['usPseudo'].','.$t['usNom'].','.$t['usAvecPhoto'].';';
        $usIDAlreadyPresent = $usIDAlreadyPresent.$t['usID'].',';
    }
    
    $usIDAlreadyPresent = substr($usIDAlreadyPresent, 0, -1);
    mysqli_free_result($r);
    
    if ($nbLignes < NB_SUGGESTIONS_ASIDE) {
        
        $res = agg_get_suggestions_complement($bd, SUGGESTIONS_COMPLEMENT, $usIDAlreadyPresent);
    
        while($t = mysqli_fetch_assoc($res)) {
            //$t  = em_html_proteger_sortie($t);
            
            if ($nbLignes==NB_SUGGESTIONS_ASIDE) break;
            
            $suggestions = $suggestions.$t['usID'].','.$t['usPseudo'].','.$t['usNom'].','.$t['usAvecPhoto'].';';
            $nbLignes++;
        }
        mysqli_free_result($res);
    }

    if($nbLignes==0) {
        $tab[8] = array();
    }else {
        $suggestions = substr($suggestions, 0, -1);
        $tab[8] = explode(';', $suggestions);
    }
    
    /* tab[0] = usAvecPhoto, tab[1] = pseudo, tab[2] = nom, tab[3] = nombre de blablas de l'utlisateur,
     * tab[4] = nombre d'abonnement de l'utilisateur, tab[5] = nombre d'abonné de l'utilisateur
     * tab[7] = tendances, tab[8]  array de suggestions(utilisateur) tab[8][0] = 1 utilisateur = usID, usPseudo, usNom, usAvecPhoto
     *                                                               tab[8][1] = 1 utilisateur = usID, usPseudo, usNom, usAvecPhoto 
     *                                                               ...
     */
    return $tab;
}

/**
 *  Obtenir les infos de l'utilisateur contenu dans la table user
 *
 * @param mysqli $bd                 Objet permettant l'accès à la base de données
 * @global array $_SESSION
 *
 * @return array                     Un tableau contenant toute les infos de la table user d'un utlisateur
 */
function agg_get_infos_user(mysqli $bd, int $ID): array {
    $sql = "SELECT *
            FROM users
            WHERE usID = '$ID'";

    $res = em_bd_send_request($bd, $sql);

    $infos = mysqli_fetch_assoc($res);
    $infos = em_html_proteger_sortie($infos);
    mysqli_free_result($res);
    
    return $infos;
}

//___________________________________________________________________
/**
 * Affichage une ligne d'un tableau permettant la saisie d'un bouton (colspan="2")
 *
 * La ligne est constituée de 1 cellule :
 * - la 1ème cellule contient l'input
 *
 * @param array     $attributs      Un tableau associatif donnant les attributs de l'input sous la forme nom => valeur
 * @param ?int      $nbCols         nombre de colonnes
 */
function agg_aff_ligne_input_bouton(array $attributs = array(), ?int $nbCols=2): void{
    echo    '<tr>', 
                '<td colspan="', $nbCols, '">',
                '<input'; 
                
    foreach ($attributs as $cle => $value){
        echo ' ', $cle, ($value !== null ? "='{$value}'" : '');
    }
    echo '></td></tr>';
}

//___________________________________________________________________
/**
 * Vérifie le conformité du nom et prénom
 *
 * @param string     $key            Une chaine contnant la cle du champ a anlayser dans le tableau $_POST 
 * @param array      $erreurs        Un tableau assosiatif contenant les erreurs
 * @global array $_POST
 *  
 */
function agg_verif_nomprenom(string $key, array &$erreurs): void {
    if (empty($_POST[$key])) {
        $erreurs[] = 'Le nom et le prénom doivent être renseignés.'; 
    }
    else {
        if (mb_strlen($_POST[$key], 'UTF-8') > LMAX_NOMPRENOM){
            $erreurs[] = 'Le nom et le prénom ne peuvent pas dépasser ' . LMAX_NOMPRENOM . ' caractères.';
        }
        $noTags = strip_tags($_POST[$key]);
        if ($noTags != $_POST[$key]){
            $erreurs[] = 'Le nom et le prénom ne peuvent pas contenir de code HTML.';
        }
        else {
            if( !mb_ereg_match('^[[:alpha:]]([\' -]?[[:alpha:]]+)*$', $_POST[$key])){
                $erreurs[] = 'Le nom et le prénom contiennent des caractères non autorisés.';
            }
        }
    }
}

//___________________________________________________________________
/**
 * Vérifie le conformité de l'addresse mail
 *
 * @param string     $key            Une chaine contnant la cle du champ a anlayser dans le tableau $_POST 
 * @param array      $erreurs        Un tableau assosiatif contenant les erreurs
 * @global array $_POST
 *  
 */
function agg_verif_mail(string $key, array &$erreurs): void {
    if (empty($_POST[$key])){
        $erreurs[] = 'L\'adresse mail ne doit pas être vide.'; 
    }
    else {
        if (mb_strlen($_POST[$key], 'UTF-8') > LMAX_EMAIL){
            $erreurs[] = 'L\'adresse mail ne peut pas dépasser '.LMAX_EMAIL.' caractères.';
        }
        // la validation faite par le navigateur en utilisant le type email pour l'élément HTML input
        // est moins forte que celle faite ci-dessous avec la fonction filter_var()
        // Exemple : 'l@i' passe la validation faite par le navigateur et ne passe pas
        // celle faite ci-dessous
        if(! filter_var($_POST[$key], FILTER_VALIDATE_EMAIL)) {
            $erreurs[] = 'L\'adresse mail n\'est pas valide.';
        }
    }
}

//___________________________________________________________________
/**
 * Vérifie le conformité de la date de naissance
 *
 * @param string     $key            Une chaine contnant la cle du champ a anlayser dans le tableau $_POST 
 * @param array      $erreurs        Un tableau assosiatif contenant les erreurs
 * @global array $_POST
 *  
 */
function agg_verif_naissance(string $key, array &$erreurs): void {
    if (empty($_POST[$key])){
        $erreurs[] = 'La date de naissance doit être renseignée.'; 
    }
    else{
        if( !mb_ereg_match('^\d{4}(-\d{2}){2}$', $_POST[$key])){ //vieux navigateur qui ne supporte pas le type date ?
            $erreurs[] = 'la date de naissance doit être au format "AAAA-MM-JJ".'; 
        }
        else{
            list($annee, $mois, $jour) = explode('-', $_POST[$key]);
            if (!checkdate($mois, $jour, $annee)) {
                $erreurs[] = 'La date de naissance n\'est pas valide.'; 
            }
            else if (mktime(0,0,0,$mois,$jour,$annee + AGE_MIN) > time()) {
                $erreurs[] = 'Vous devez avoir au moins '.AGE_MIN.' ans pour vous inscrire.'; 
            }
            else if (mktime(0,0,0,$mois,$jour,$annee + AGE_MAX + 1) < time()) {
                $erreurs[] = 'Vous devez avoir au plus '.AGE_MAX.' ans pour vous inscrire.'; 
            }
        }
    }
}

//___________________________________________________________________
/**
 * Vérifie le conformité des mots de passe
 * @param string     $key            Une chaine contnant la cle du champ a anlayser dans le tableau $_POST 
 * @param string     $key            Une chaine contnant la cle du champ a anlayser dans le tableau $_POST 
 * @param array      $erreurs        Un tableau assosiatif contenant les erreurs
 * @global array $_POST
 *  
 */
function agg_verif_motDePasse(string $key1, string $key2, array &$erreurs): void {
    if ($_POST[$key1] !== $_POST[$key2]) {
        $erreurs[] = 'Les mots de passe doivent être identiques.';
    }
    $nb = mb_strlen($_POST[$key1], 'UTF-8');
    if ($nb < LMIN_PASSWORD || $nb > LMAX_PASSWORD){
        $erreurs[] = 'Le mot de passe doit être constitué de '. LMIN_PASSWORD . ' à ' . LMAX_PASSWORD . ' caractères.';
    }
}

//___________________________________________________________________
/**
 * Affichage du message de validation d'un formulaire ou non
 * soit un message en rouge spécifiant les erreurs
 * soit un message en vert spécifiant qu'aucune erreurs à été detectéé
 *
 * @param array      $erreurs        Un tableau assosiatif contenant les erreurs
 */
function agg_aff_message_validation_formulaire(array $err): void {
    if (count($err) > 0) {
        echo '<p class="error">Les erreurs suivantes ont été détectées :';
        foreach ($err as $v) {
            echo '<br> - ', $v;
        }
        echo '</p>';    
    }else {
        echo '<p class="noError">La mise à jour des informations sur votre compte a bien été effectuée', '</p>';    
    }
}

//___________________________________________________________________
/**
 * Fonction qui permet d'effectuer les abonnements et les désabonnements
 *
 * Pour s'abboner ou se desabonner à un utlisateur son usID 
 * doit être le name d'un bouton/ checkBox
 * 
 * @param array      $bd                 objet permettant d'acceder à la bdd
 * @param string     $nomBtn             nom du bouton dans $_POST 
 * @global array     $_POST
 */
function agg_set_abonnement_desabonnement(mysqli $bd, ?string $nomBtn = null): void {
    $date_aujourdhui = date('Ymd');
    $ID = $_SESSION['usID'];

    $listID = '';
    foreach ($_POST as $key => $value) {
        if ($nomBtn && $key == $nomBtn) continue;
        //if(!em_est_entier($key)) return; // une des $key n'est pas un entier donc on s'arrete
        $listID = "$listID,$key";
    }                                                               

    $listID = substr($listID, 1);
    $sql = "SELECT GROUP_CONCAT(usID ORDER BY usID ASC SEPARATOR ',') AS usids
            FROM users
            WHERE usID IN ($listID);";
                
    $res = em_bd_send_request($bd, $sql);
    $idsExistant = mysqli_fetch_assoc($res);
    $idsExistant = em_html_proteger_sortie($idsExistant);
    mysqli_free_result($res);

    $idsExistant = explode(',', $idsExistant['usids']);

    $sqlInsert = 'INSERT INTO estabonne(eaIDUser, eaIDAbonne, eaDate) VALUES';
    
    foreach ($idsExistant as $key => $idExistant) {
    
        if ($_POST[$idExistant] === 'S\'abonner') { // l'utilisateur veut s'abonner
            $sqlInsert = "$sqlInsert ('$ID','$idExistant','$date_aujourdhui'),"; 
        }else {
            $idsDelete = "$idsDelete,$idExistant";
        }   
    }
    
    if (array_search('Se désabonner', $_POST)) {
        $idsDelete = substr($idsDelete, 1);
        $sqlDelete = "DELETE FROM estabonne WHERE eaIDUser = $ID AND eaIDAbonne IN ($idsDelete)";
        em_bd_send_request($bd, $sqlDelete);
    }

    if (array_search('S\'abonner', $_POST)) {
        $sqlInsert = substr($sqlInsert, 0, -1);
        em_bd_send_request($bd, $sqlInsert);
    }
}

//____________________________________________________________________________
/**
 * verifier les arguments de la page php
 *
 * @global $_GET
 * @return  bool     
 */
function agg_verifArgs(): bool {

    foreach ($_GET as $key => $value) {
        if(!em_est_entier($value)){
            return false; 
        }
        if(!array_key_exists('id', $_GET)) {    
            return false;
        }
    }
    return true;
}

//___________________________________________________________________
/**
 * Fonction qui permet d'obtenir les blablas d'un utlisateur
 * 
 * @param mysqli     $bd                              objet permettant d'acceder à la bdd
 * @param int        $id                              id de l'utlisateur voulue
 * @param ?bool      $abonne                          oui si on veux les blablas des personnes abonnés non sinon
 * @param ?bool      $mentions                        oui si on veux les blablasoù l'utilisateur est mentionné non sinon
 * @param ?bool      $auteur                          oui si on veut l'auteur des blablas non sinon
 *                                                    
 * @return   mysqli_result                             le resultat de la requête
 */
function agg_get_donnees_blablas(mysqli $bd, int $id, ?bool $abonne=true, ?bool $mentions=true, ?bool $auteur = true): mysqli_result {

    $sql = 'SELECT  DISTINCT auteur.usID AS autID, auteur.usPseudo AS autPseudo, auteur.usNom AS autNom, auteur.usAvecPhoto AS autPhoto, 
            blTexte, blDate, blHeure,
            origin.usID AS oriID, origin.usPseudo AS oriPseudo, origin.usNom AS oriNom, origin.usAvecPhoto AS oriPhoto,
            GROUP_CONCAT(DISTINCT taID) AS TAGS, GROUP_CONCAT(DISTINCT meIDUser) AS IDMENTIONS, blID
            FROM ((((((users AS auteur
            INNER JOIN blablas ON blIDAuteur = usID)
            LEFT OUTER JOIN users AS origin ON origin.usID = blIDAutOrig)
            LEFT OUTER JOIN estabonne ON auteur.usID = eaIDAbonne)
            LEFT OUTER JOIN mentions ON blID = meIDBlabla)
            LEFT OUTER JOIN tags ON blID = taIDBlabla)
            LEFT OUTER JOIN users AS users2 ON meIDUser = users2.usID)

            WHERE '.
            (($auteur) ? "auteur.usID = $id " : ' ').
            (($abonne) ? (($auteur) ? ' OR ' : ' ')." eaIDUser = $id " : ' ').
            (($mentions) ? (($auteur) ? ' OR ' : ' ')." meIDUser = $id " : ' ').
            'GROUP BY blID
            ORDER BY blID DESC';
    
    return em_bd_send_request($bd, $sql);
}

//___________________________________________________________________
/**
 * Affichage des données d'un utilisateur (nombre de blablas, mentions, abonnés, abonnements)
 *
 * @param string    $id                  id de l'utilisateur à affiché
 * @param array     $infos               infos de l'utiliateur
 * @param ?bool      ?$abonne            oui si l'utisateur courant est abonne a l'utlisateur à affiché non sinon
 * @param ?bool     $afficheCheckBox     booleen affiche une check box
 */
function agg_aff_donnees_reduite_utilisateur(string $id, array $infos, ?bool $abonne=false, ?bool $afficheCheckBox=false): void {

    echo 
    '<img src="../', ($infos[0] == 1 ? "upload/$id.jpg" : 'images/anonyme.jpg'), 
    '" class="imgAuteur" alt="photo de l\'auteur">',
    em_html_a('utilisateur.php', '<strong>'.$infos[1].'</strong>','id', $id), ' ', $infos[2],'<br>',
    em_html_a('./blablas.php', $infos[3].($infos[3]>1 ? ' blablas' : ' blabla'), 'id', $id), ' - ',
    em_html_a('./mentions.php', $infos[6].($infos[6]>1 ? ' mentions' : ' mention'), 'id', $id), ' - ',
    em_html_a('./abonnes.php', $infos[5].($infos[5]>1 ? ' abonnés' : ' abonné'), 'id', $id), ' - ',
    em_html_a('./abonnements.php', $infos[4].($infos[4]>1 ? ' abonnements' : ' abonnement'), 'id', $id);
    // si l'utlisateur recherché n'est pas l'utlisateur courant
    if ($_SESSION['usID'] != $id && $afficheCheckBox) {
        $estAbonne = (!$abonne ? 'S\'abonner' : 'Se désabonner');
            echo '<p class="finMessage, alignDroite">',
                    '<input id="', $id, '" type="checkBox" name="', $id, '" value="', $estAbonne, '">',
                    '<label for="', $id, '">', $estAbonne, '</label>',
                '<p>';
    }
}

//___________________________________________________________________
/**
 * Affichage du formulaire des données réduite de plusieures utlisateur
 * 
 * @param mysqli               $bd                              objet permettant d'acceder à la bdd
 * @param mysqli_result        $res
 *                                                    
 */
function agg_aff_donnees_reduite_utlisateurs(mysqli $bd, mysqli_result $res): void {

    while($t = mysqli_fetch_assoc($res)) {
        $t  = em_html_proteger_sortie($t);

        // recupération des données de l'utilisateur
        $infos = agg_get_donnee_reduite_utilisateur($bd, $t['usID']);
        
        echo '<li>',   
        agg_aff_donnees_reduite_utilisateur($t['usID'], $infos, $t['eaIDUser']!=NULL, true),
            '</li>';   
    }
}

/**
 *  Traitement de l'abonnement ou le désabonnement à un utlisateur
 *
 *      Etape 1. Vérification des données
 *      Etape 2. Mettre à jour la bdd et redirection vers la page cuiteur.php
 *
 * @param mysqli    $bd        Objet permettant l'accès à la base de données
 * @global array    $_POST
 * @global array    $_SESSION
 *
 */
function agg_traitement_abonnement_desabonnement(mysqli $bd): void {
    /*------------------------- Etape 1 --------------------------------------------
    - Vérification des données
    ------------------------------------------------------------------------------*/
    
    if( !isset($_POST['btnValider'])) {
        em_session_exit();
    }

    foreach($_POST as &$val){
        $val = trim($val);
    }
    
    if (count($_POST)==1) { // il n'y a  
        header('Location: cuiteur.php');
        exit();
    }

    /*------------------------- Etape 2 --------------------------------------------
    - Mettre à jour la bdd et redirection vers la page cuiteur.php
    ------------------------------------------------------------------------------*/
    
    agg_set_abonnement_desabonnement($bd, 'btnValider');
   
    // redirection vers la page cuiteur.php
    header('Location: cuiteur.php'); 
    exit();
}

/**
 *  Affichage des tendances selon la date
 *
 *
 * @param mysqli_result    $res            résulat d'une requête
 * @param string           $intituleDate        
 *  
 */
function agg_aff_tendances(mysqli_result $res, string $intituleDate): void {
    $today=date("Y-m-d-w"); // annee , mois, jour , numero jour dans la semaine en cours 0 ->dimanche à 6 samedi
    $today = explode('-', $today);
	
    $dateSemaine = date_create($today[0].'-'.$today[1].'-'.$today[2]);
    date_sub($dateSemaine, date_interval_create_from_date_string(($today[3]==0 ? '6' : $today[3]-1).' days'));

    echo '<br><h1>Top 10 '.$intituleDate.'</h1><ol>';
    $i=0;
    
	while(($t=mysqli_fetch_assoc($res)) && $i < 10)
	{
		$t = em_html_proteger_sortie($t);
		$annee = substr(($t['blDate']), 0, 4);
        $mois = substr(($t['blDate']), 4, 2);
        $jour = substr(($t['blDate']), 6, 2);
        
        if($intituleDate=='du jour' && $today[0]==$annee && $today[1]==$mois && $today[2]==$jour) {			
			echo '<li>', em_html_a('./tendances.php', $t['taID'].' ('.$t['nbOccurences'].')', 'taID', $t['taID']), '</li>';
            $i++;
		}

        if($intituleDate=='du mois' && $today[0]==$annee && $today[1]==$mois) {			
			echo '<li>', em_html_a('./tendances.php', $t['taID'].' ('.$t['nbOccurences'].')', 'taID', $t['taID']), '</li>';
            $i++;
		}

        if($intituleDate=='de l\'ann&eacute;e' && $today[0]==$annee) {			
			echo '<li>', em_html_a('./tendances.php', $t['taID'].' ('.$t['nbOccurences'].')', 'taID', $t['taID']), '</li>';
            $i++;
		}

        $dateBlablas = date_create($annee.'-'.$mois.'-'.$jour);
        $interval = date_diff($dateSemaine, $dateBlablas);
        
        if($intituleDate=='de la semaine' && intval($interval->format('%R%a'))>=0) {			
			echo '<li>', em_html_a('./tendances.php', $t['taID'].' ('.$t['nbOccurences'].')', 'taID', $t['taID']), '</li>';
            $i++;
		}

    }
    
    echo '</ol>';
	if($i==0) {
		echo '<p>Aucune tendance ...</p>';
	}	
}

/**
 *  Obtenir sugestions (abonnements des mes abonnes)
 *
 *
 * @param mysqli    $bd                        Objet permettant l'accès à la base de données      
 * @param int       $NB_SUGGESTIONS            Nombre de suggestions voulues
 * @global array    $_SESSION  
 * 
 * @return mysqli_result    $res               résulat de la requête
 */
function agg_get_suggestions(mysqli $bd , int $NB_SUGGESTIONS): mysqli_result {
    
    // requête pour selectionner les abonnements de mes abonnée auquelle je ne suis pas encore abonné
    $sql = "SELECT DISTINCT usID, usPseudo, usNom, usAvecPhoto, dejaAbonne.eaIDUser, dejaAbonne.eaIDAbonne
            FROM users INNER JOIN estabonne ON usID=eaIDAbonne LEFT OUTER JOIN estabonne AS dejaAbonne ON usID=dejaAbonne.eaIDAbonne AND dejaAbonne.eaIDUser=24
            WHERE estabonne.eaIDUser IN (SELECT eaIDAbonne FROM estabonne WHERE eaIDuser={$_SESSION['usID']})
            AND usID!={$_SESSION['usID']}
            AND usID NOT IN (SELECT eaIDAbonne FROM estabonne WHERE eaIDuser={$_SESSION['usID']})
            ORDER BY RAND()
            LIMIT $NB_SUGGESTIONS";
        
    return em_bd_send_request($bd, $sql);
}

/**
 * Obtenir suggestions (les utilisateurs ayant le plus d'abonné)
 *
 *
 * @param mysqli    $bd                                   Objet permettant l'accès à la base de données      
 * @param int       $NB_SUGGESTIONS_COMPLEMENT            Nombre de suggestions voulues
 * @param string    $usIDAlreadyPresent                   les utlisateurs déja obtenu avec la reqûete agg_get_suggestions 
 * @global array    $_SESSION  
 * 
 * @return mysqli_result    $res               résulat de la requête
 */
function agg_get_suggestions_complement(mysqli $bd , int $NB_SUGGESTIONS_COMPLEMENT, string $usIDAlreadyPresent): mysqli_result {
    $sql = "SELECT usID, usPseudo, COUNT(usID), usNom, usAvecPhoto, dejaAbonne.eaIDUser, dejaAbonne.eaIDAbonne
                    FROM (users INNER JOIN estabonne ON usID=eaIDUser) LEFT OUTER JOIN estabonne AS dejaAbonne ON usID=dejaAbonne.eaIDAbonne AND dejaAbonne.eaIDUser={$_SESSION['usID']}
                    WHERE usID NOT IN ({$_SESSION['usID']}".(mb_strlen($usIDAlreadyPresent)!= 0 ? ','.$usIDAlreadyPresent : '').
                    ') AND dejaAbonne.eaIDUser IS NULL
                    GROUP BY usID
                    ORDER BY RAND()
                    LIMIT '.SUGGESTIONS_COMPLEMENT;
    
    return em_bd_send_request($bd, $sql);
}

/**
 * Verfier les paramètres de $_GET
 */
function agg_verif_parametre(): void {
    foreach ($_GET as $key => $value) {
        if ($value === FALSE){
            em_session_exit();
        }
    }
}
?>
