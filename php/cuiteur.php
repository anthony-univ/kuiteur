<?php
/* ------------------------------------------------------------------------------
    Architecture de la page
    - étape 1 : vérifications diverses et traitement des soumissions
    - étape 2 : recupération des données dans la base données
    - étape 3 : génération du code HTML de la page
------------------------------------------------------------------------------*/

ob_start(); //démarre la bufferisation
session_start();

require_once 'bibli_generale.php';
require_once 'bibli_cuiteur.php';

/*------------------------- Etape 1 --------------------------------------------
- vérifications diverses et traitement des soumissions
------------------------------------------------------------------------------*/
if(isset($_GET['blablas_affiche'])) $_GET['blablas_affiche'] = decrypteSigneURL($_GET['blablas_affiche']);
if(isset($_GET['id_blabla_supp'])) $_GET['id_blabla_supp'] = decrypteSigneURL($_GET['id_blabla_supp']);
if(isset($_GET['blIDARecuiter'])) $_GET['blIDARecuiter'] = decrypteSigneURL($_GET['blIDARecuiter']);
if(isset($_GET['pseudoDestinataire'])) $_GET['pseudoDestinataire'] = decrypteSigneURL($_GET['pseudoDestinataire']);

// si utilisateur déjà authentifié, on le redirige vers la page cuiteur_1.php
if (!em_est_authentifie()){
    header('Location: ../index.php');
    exit();
}

agg_verif_parametre();
$bd = em_bd_connect();

// traitement du blabla à supprimer
if (isset($_GET['id_blabla_supp'])) aggl_traitement_supprimer_blabla($bd); 

// traitement du blabla à recuiter
if (isset($_GET['blIDARecuiter'])) aggl_traitement_recuiter_blabla($bd); 

// traitement si soumission du formulaire de post d'un blabla
if (isset($_POST['btnPublier'])) aggl_traitement_publier_blabla($bd); 

/*------------------------- Etape 2 --------------------------------------------
- recupération des données dans la base données
------------------------------------------------------------------------------*/

$infos = agg_get_donnee_reduite_utilisateur($bd, $_SESSION['usID']);
$res = agg_get_donnees_blablas($bd, $_SESSION['usID']);

/*------------------------- Etape 3 --------------------------------------------
- génération du code HTML de la page
------------------------------------------------------------------------------*/

em_aff_debut('Cuiteur', '../styles/cuiteur.css');
em_aff_entete();

em_aff_infos(true, $infos);

echo '<ul>';

if (mysqli_num_rows($res) == 0){
    echo '<li>Votre fil de blablas est vide</li>';
}
else{
    em_aff_blablas($bd, $res, './cuiteur.php');
}

echo '</ul>';

// libération des ressources
mysqli_free_result($res);
mysqli_close($bd);

em_aff_pied();
em_aff_fin();

// facultatif car fait automatiquement par PHP
ob_end_flush();

// ----------  Fonctions locales du script ----------- //

/**
 *  Traitement du blabla à publier
 *
 *      Etape 1. vérification de la validité des données
 *      Etape 2. analyse des tags et des mentions du nouveau blabla
 *      Etape 3. enregistrement du nouveau blabla dans la base de données ansi que les tags et les mentions
 *
 * @param mysqli $bd                 $Objet permettant l'accès à la base de données
 * @global array    $_POST
 * @global array    $_SESSION
 *
 */
function aggl_traitement_publier_blabla(mysqli $bd): void {
    /*------------------------- Etape 1 --------------------------------------------
    - vérification de la validité des données
    ------------------------------------------------------------------------------*/

    if( !em_parametres_controle('post', array('btnPublier','txtMessage'))) {
        em_session_exit();   
    }

    // vérification de la non présence de code html
    $noTags = strip_tags($_POST['txtMessage']);
    if ($noTags!=$_POST['txtMessage']) {
        em_session_exit();
    }

    // vérification du blabla
    //$_POST = em_html_proteger_sortie($_POST);
    $txtMessage = $_POST['txtMessage'];
    if (mb_strlen($txtMessage, 'UTF-8') <= 0){
        return;
    }

    /*------------------------- Etape 2 --------------------------------------------
    - analyse des tags et des mentions du nouveau blabla
    ------------------------------------------------------------------------------*/
    
    // enlever les doublons car sinon on provoque deux lignes identique dans la table tags ou mentions
    $tags = array_unique(analyserBlalblas($txtMessage, '#[[:alnum:]]+')); 
    
    $mentions = array_unique(analyserBlalblas($txtMessage, '@[[:alnum:]]+'));
    
    /*------------------------- Etape 3 --------------------------------------------
    - enregistrement du nouveau blabla dans la base de données ansi que les tags et les mentions
    ------------------------------------------------------------------------------*/

    $sql = "INSERT INTO blablas (blIDAuteur, bldate, blHeure, blTexte) 
            VALUES ('".$_SESSION['usID']."', '".date("Ymd")."', '".date("H:i:s")."', '".em_bd_proteger_entree($bd, $txtMessage)."')";
    
    em_bd_send_request($bd, $sql);
    $id_blabla = mysqli_insert_id($bd); // blID du blabla qu'on vient d'enregistrer

    foreach ($tags as $key => $tag) {
        $sql = "INSERT INTO tags (taID, taIDBlabla)
                VALUES ('".em_bd_proteger_entree($bd, $tag)."', '".em_bd_proteger_entree($bd, $id_blabla)."')";
        em_bd_send_request($bd, $sql);
    }

    foreach ($mentions as $key => $mention) {
        // vérication que l'utlisateur mentionné existe
        $mention = em_bd_proteger_entree($bd, $mention);
        $sql = "SELECT usID
                FROM users
                WHERE usPseudo = '$mention'";
        
        $res = em_bd_send_request($bd, $sql);
        $t = mysqli_fetch_assoc($res);
        mysqli_free_result($res);

        // insertion de la mention si l'utilisateur mentionné existe
        if (isset($t['usID'])) {
            $t = em_html_proteger_sortie($t);
            $sql = "INSERT INTO mentions (meIDUser, meIDBlabla) 
                    VALUES ('".em_bd_proteger_entree($bd, $t['usID'])."', '".em_bd_proteger_entree($bd, $id_blabla)."')";
            em_bd_send_request($bd, $sql);
        }
    }
}

/**
 *  Fonction qui cherche une ou plusieurs correspondace avec une regex dans un blabla
 *  Fonction utlisé recursivement sur le blabla qu'on a pas encore analysé
 *
 * @param string $blabla          blabla à analyser
 * @param mysqli_result  $regex   expression regulière au mot(s) qu'on souhaite trouver dans le blabla
 */
function analyserBlalblas(string $blabla, string $regex){
    mb_ereg_search_init($blabla, $regex);
    $resultats=array();
    if ($test =  mb_ereg_search_pos())   {
        $pos = $test[0];
        $longueur = $test[1];
        $finDeBlabla= substr($blabla,$pos+$longueur+1);
        $resultats = analyserBlalblas($finDeBlabla, $regex);
        $tag = substr($blabla, $pos+1, $longueur-1);
        $resultats[count($resultats)] = $tag;
    }
    return $resultats;
}

/**
 *  Traitement du blabla à supprimer
 *
 *      Etape 1. vérification de la validité des données
 *      Etape 2. suppression du nouvel blablas dans la base de donéées ansi que les tags et les mentions qui lui sont associés et redirection vers la page cuiteur
 *
 * @param mysqli $bd                 $Objet permettant l'accès à la base de données
 * @global array    $_GET
 * @global array    $_SESSION
 *
 */
function aggl_traitement_supprimer_blabla(mysqli $bd): void {
    /*------------------------- Etape 1 --------------------------------------------
    - vérification de la validité des données
    ------------------------------------------------------------------------------*/

    // on verifie si l'utlisateur est le propriétaire du blablas à supprimer et que la blala existe
    $id_blabla_supp = $_GET['id_blabla_supp'];
    $sql = "SELECT blID 
            FROM blablas
            WHERE blIDAuteur = ".$_SESSION['usID']." AND blID = $id_blabla_supp";

    $res = em_bd_send_request($bd, $sql);
    $t = mysqli_fetch_assoc($res);
    mysqli_free_result($res);
   
    /*------------------------- Etape 2 --------------------------------------------
    - suppression du nouvel blablas dans la base de donéées ansi que les tags et les mentions qui lui sont associés et redirection vers la page cuiteur
    ------------------------------------------------------------------------------*/

    // suppresion du blabla si l'utlisateur en est le propriétaire
    if (isset($t['blID'])) {
        $sql = "DELETE FROM tags
                WHERE taIDBlabla = $id_blabla_supp";
        em_bd_send_request($bd, $sql);
        $sql = "DELETE FROM mentions
                WHERE meIDBlabla = $id_blabla_supp";
        em_bd_send_request($bd, $sql);
        $sql = "DELETE FROM blablas
                WHERE blID = $id_blabla_supp";
        em_bd_send_request($bd, $sql);
    }
}

/**
 *  Traitement du blabla à recuiter
 *
 *      Etape 1. vérification de la validité des données
 *      Etape 2. enregistrement du nouveau blabla(recuit) et les tags et les mentions 
 *               qui lui sont associé(même tags,mentions que le blabla recuité) dans la base et redirection vers la page cuiteur
 *
 * @param mysqli $bd                 $Objet permettant l'accès à la base de données
 * @global array    $_GET
 *
 */
function aggl_traitement_recuiter_blabla(mysqli $bd): void {
    /*------------------------- Etape 1 --------------------------------------------
    - vérification de la validité des données
    ------------------------------------------------------------------------------*/
    
    // on verifie si le blabla à recuiter existe
    $blIDARecuiter = $_GET['blIDARecuiter'];
    $sql = "SELECT *
            FROM blablas
            WHERE blID = $blIDARecuiter";
    $res = em_bd_send_request($bd, $sql);
    $t = mysqli_fetch_assoc($res);
    mysqli_free_result($res);
    
    /*------------------------- Etape 2 --------------------------------------------
    - enregistrement du nouveau blabla(recuit) et les tags et les mentions 
      qui lui sont associé(même tags,mentions que le blabla recuité) dans la base et redirection vers la page cuiteur
    ------------------------------------------------------------------------------*/

    // s'il le blabla à recuiter existe
    if (isset($t['blID'])) {
        $sql = "INSERT INTO blablas (blIDAuteur, blDate, blHeure, blTexte, blIDAutOrig) 
                VALUES ('".$_SESSION['usID']."', '".date("Ymd")."', 
                        '".em_bd_proteger_entree($bd, date("H:i:s"))."', '".em_bd_proteger_entree($bd, $t['blTexte'])."', 
                        '".(isset($t['blIDAutOrig']) ? em_bd_proteger_entree($bd, $t['blIDAutOrig']) : em_bd_proteger_entree($bd, $t['blIDAuteur']))."')"; // cas du double recuit 
       
        em_bd_send_request($bd, $sql);                                                             // (ne pas changer l'auteur original)
        $id_blabla = mysqli_insert_id($bd); // blID du blabla qu'on vient d'enregistrer

        // analyse et enregistrement des même tags que la blabla recuiter
        $sql = "SELECT taID, taIDBlabla
                FROM tags
                WHERE taIDBlabla = $blIDARecuiter";
        $res = em_bd_send_request($bd, $sql);

        while ($t = mysqli_fetch_assoc($res)) {
            $sql = "INSERT INTO tags (taID, taIDBlabla)
                    VALUES ('".$t['taID']."', '".$id_blabla."')";
            em_bd_send_request($bd, $sql);
        }
        mysqli_free_result($res);

        // analyse et enregistrement des même mentions que la blabla recuiter
        $sql = "SELECT meIDUser, meIDBlabla
                FROM mentions
                WHERE meIDBlabla = $blIDARecuiter";
        $res = em_bd_send_request($bd, $sql);

        while ($t = mysqli_fetch_assoc($res)) {
            $sql = "INSERT INTO mentions (meIDUser, meIDBlabla)
                    VALUES ('".em_bd_proteger_entree($bd, $t['meIDUser'])."', '".em_bd_proteger_entree($bd, $id_blabla)."')";
            em_bd_send_request($bd, $sql);
        }
        mysqli_free_result($res); 
    }
}
?>