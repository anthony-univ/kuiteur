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

// si utilisateur déjà authentifié, on le redirige vers la page cuiteur_1.php
if (!em_est_authentifie()){
    header('Location: ../index.php');
    exit();
}

$bd = em_bd_connect();

$er = array();
// traitement si soumission du formulaire de modification n°1
if (isset($_POST['btnValider1'])) $er = aggl_traitement_modification1($bd); 

// traitement si soumission du formulaire de modification n°1
if (isset($_POST['btnValider2'])) $er = aggl_traitement_modification2($bd);

// traitement si soumission du formulaire de modification n°1
if (isset($_POST['btnValider3'])) $er = aggl_traitement_modification3($bd);

/*------------------------- Etape 2 --------------------------------------------
- recupération des données dans la base données
------------------------------------------------------------------------------*/

$infos = agg_get_donnee_reduite_utilisateur($bd, $_SESSION['usID']);

/*------------------------- Etape 3 --------------------------------------------
- génération du code HTML de la page
------------------------------------------------------------------------------*/

em_aff_debut('Cuiteur | Compte', '../styles/cuiteur.css');
em_aff_entete('Paramètres de mon compte');

em_aff_infos(true, $infos);

aggl_aff_formulaires($er , $bd);

// libération des ressources
mysqli_close($bd);

em_aff_pied();
em_aff_fin();

// facultatif car fait automatiquement par PHP
ob_end_flush();

// ----------  Fonctions locales du script ----------- //

/**
 *  Affichage des 3 formulaires de modification des données personnelles de l'utilisateur
 *
 *      Etape 1. Récupération des données de l'utilisateur
 *      Etape 2. Affichage des 3 formulaires
 *
 * @param   array   $err    tableau d'erreurs à afficher
 * @global  array   $_POST
 *
 */
function aggl_aff_formulaires(array $err, mysqli $bd): void {
    /*------------------------- Etape 1 --------------------------------------------
    - Récupération des données de l'utilisateur
    ------------------------------------------------------------------------------*/
    $infos = agg_get_infos_user($bd, $_SESSION['usID']);
    
    // réaffichage des données soumises en cas d'erreur, sauf les mots de passe 
    if (isset($_POST['btnValider1'])){
        $tab1 = em_html_proteger_sortie($_POST);
        $infos['usNom'] = $tab1['nomprenom'];
        $dateNaissance = $tab1['naissance'];
        $infos['usVille'] = $tab1['ville'];
        $infos['usBio'] = $tab1['bio'];
    }else { // soumission d'au moins un des 3 formualaire
        // mise des tirets pour afficher la date dans l'input correctement
        $dateNaissance = substr($infos['usDateNaissance'], 0, 4).'-'.substr($infos['usDateNaissance'], 4, 2).'-'.substr($infos['usDateNaissance'], 6, 2);
    }
    
    if (isset($_POST['btnValider2'])){
        $tab2 = em_html_proteger_sortie($_POST);
        $infos['usMail'] = $tab2['email'];
        $infos['usWeb'] = $tab2['web'];
    }

    /*------------------------- Etape 2 --------------------------------------------
    - Affichage des 3 formulaires
    ------------------------------------------------------------------------------*/

    /* Formulaire n°1 */
    echo    '<p>Cette page vous permet de modifier les informations relatives à votre compte.</p><br>',
            '<p class="titreFormulaire">Informations personnelles</p>';
            
    if (isset($_POST['btnValider1'])) {
        agg_aff_message_validation_formulaire($err);
    }

    echo    '<form action="./compte.php" method="POST">',
                '<table>';
   
    em_aff_ligne_input('Nom', array('type' => 'text', 'name' => 'nomprenom', 'value' => $infos['usNom'], 'required' => null));
    em_aff_ligne_input('Date de naissance', array('type' => 'date', 'name' => 'naissance', 'value' => $dateNaissance, 'required' => null));
    em_aff_ligne_input('Ville', array('type' => 'text', 'name' => 'ville', 'value' => $infos['usVille']));
   
                echo '<tr>',
                        '<td><label for="miniBio">', 'Mini-bio', '</label></td>',
                        '<td>', '<textarea id="miniBio" class="miniBio" name="bio">', $infos['usBio'],'</textarea>', '</td>',
                    '</tr>',
                    agg_aff_ligne_input_bouton(array('type' => 'submit', 'name' => 'btnValider1', 'value' => 'Valider')),
                '</table>',
            '</form>';

    /* Formulaire n°2 */
    echo    '<p class="titreFormulaire">Informations sur votre compte Cuiteur</p>';
            
    if (isset($_POST['btnValider2'])) {
        agg_aff_message_validation_formulaire($err);
    }

    echo    '<form action="./compte.php#textemail" method="POST">',
                '<table>';

    em_aff_ligne_input('Adresse mail', array('type' => 'email', 'name' => 'email', 'value' => $infos['usMail'], 'required' => null));
    em_aff_ligne_input('Site web', array('type' => 'text', 'name' => 'web', 'value' => $infos['usWeb']));
    agg_aff_ligne_input_bouton(array('type' => 'submit', 'name' => 'btnValider2', 'value' => 'Valider'));
        
        echo    '</table>',
            '</form>';

    /* Formulaire n°3 */
    echo    '<p class="titreFormulaire">Paramètres de votre compte Cuiteur</p>';
            
    if (isset($_POST['btnValider3'])) {
        agg_aff_message_validation_formulaire($err);
    }

    echo    '<form action="./compte.php#textpasse1" method="POST" enctype="multipart/form-data">',
                '<table>';

    em_aff_ligne_input('Changer le mot de passe :', array('type' => 'password', 'name' => 'passe1'));
    em_aff_ligne_input('Répétez le mot de passe :', array('type' => 'password', 'name' => 'passe2'));

                echo '<tr>',
                        '<td><label for="photo">', 'Votre photo actuelle', '</label></td>',
                        '<td>','<img src="../', ($infos['usAvecPhoto'] == 1 && file_exists('../upload/'.$_SESSION['usID'].'.jpg') ? 'upload/'.$_SESSION['usID'].'.jpg' : 'images/anonyme.jpg'), '" alt="photo de l\'auteur"><br>', 
                            'Taille 20ko maximum<br>','Image JPG carrée (mini 50x50px)<br>',
                            '<input id="photo" type="file" name="photoAvatar" accept=".jpg,.jpeg">',
                        '</td>',
                    '</tr>',
                    '<tr>',
                        '<td><label>','Utiliser votre photo','</label></td>',    
                        '<td>','<input type="radio" name="btnRadioPhoto" value="0" ', ($infos['usAvecPhoto']==0 ? 'checked' : ''), '>non',
                                '<input type="radio" name="btnRadioPhoto" value="1" ', ($infos['usAvecPhoto']==1 ? 'checked' : ''), '>oui',
                        '</td>',    
                    '</tr>';
                    agg_aff_ligne_input_bouton(array('type' => 'submit', 'name' => 'btnValider3', 'value' => 'Valider'));
        echo    '</table>',
            '</form>';

}

/**
 *  Traitement de la modication des données de l'utlisateur du formulaire 1 
 *
 *      Etape 1. vérification de la validité des données
 *                  -> return des erreurs si on en trouve
 *      Etape 2. enregistrement des nouvelles modifications dans la base
 *
 * Toutes les erreurs détectées qui nécessitent une modification du code HTML sont considérées comme des tentatives de piratage 
 * et donc entraînent l'appel de la fonction em_session_exit() sauf :
 * - les éventuelles suppressions des attributs required car l'attribut required est une nouveauté apparue dans la version HTML5 et 
 *   nous souhaitons que l'application fonctionne également correctement sur les vieux navigateurs qui ne supportent pas encore HTML5
 * - une éventuelle modification de l'input de type date en input de type text car c'est ce que font les navigateurs qui ne supportent 
 *   pas les input de type date
 *
 * @param mysqli    $bd      Objet permettant l'accès à la base de données
 * @global array    $_POST
 *
 * @return array    tableau assosiatif contenant les erreurs
 */
function aggl_traitement_modification1(mysqli $bd): array {
    /*------------------------- Etape 1 --------------------------------------------
    - vérification de la validité des données
    ------------------------------------------------------------------------------*/

    if( !em_parametres_controle('post', array('nomprenom', 'naissance', 'ville', 'bio', 'btnValider1'))) {
        em_session_exit();
    }

    foreach($_POST as &$val){
    $val = trim($val);
    }

    $erreurs = array();

    // vérification des noms et prenoms
    agg_verif_nomprenom('nomprenom', $erreurs);

    // vérification de la date de naissance
    agg_verif_naissance('naissance', $erreurs);

    // vérification de la ville
    $noTags = strip_tags($_POST['ville']);
    if ($noTags != $_POST['ville']){
        $erreurs[] = 'La ville ne peuvent pas contenir de code HTML.';
    }
    if (mb_strlen($_POST['ville'], 'UTF-8') > LMAX_VILLE){
        $erreurs[] = 'La ville ne peuvent pas dépasser ' . LMAX_VILLE . ' caractères.';
    }

    // vérification de la mini-bio
    $noTags = strip_tags($_POST['bio']);
    if ($noTags != $_POST['bio']){
        $erreurs[] = 'La mini-bio ne peuvent pas contenir de code HTML.';
    }
    if (mb_strlen($_POST['bio'], 'UTF-8') > LMAX_BIO){
        $erreurs[] = 'La bio ne peuvent pas dépasser ' . LMAX_BIO . ' caractères.';
    }
    
    // s'il y a des erreurs ==> on retourne le tableau d'erreurs    
    if (count($erreurs) > 0) {  
        return $erreurs;    
    }
    
    /*------------------------- Etape 2 --------------------------------------------
    - enregistrement des nouvelles modifications dans la base
    ------------------------------------------------------------------------------*/

    // pas d'erreurs ==> enregistrement des modifications de l'utilisateur
    $nomprenom = em_bd_proteger_entree($bd, $_POST['nomprenom']);
    $dateNaissance = mb_ereg_replace('-', '',em_bd_proteger_entree($bd, $_POST['naissance']));
    $ville = em_bd_proteger_entree($bd, $_POST['ville']);
    $bio = em_bd_proteger_entree($bd, $_POST['bio']);

    $sql = "UPDATE users 
            SET usNom='".$nomprenom."', usVille='".$ville."', usBio='".$bio."', usDateNaissance='".$dateNaissance."'
            WHERE usID='".$_SESSION['usID']."'";
            
    em_bd_send_request($bd, $sql);
    return array();
}

/**
 *  Traitement de la modication des données de l'utlisateur du formulaire 2
 *
 *      Etape 1. vérification de la validité des données
 *                  -> return des erreurs si on en trouve
 *      Etape 2. enregistrement des nouvelles modifications dans la base
 *
 * Toutes les erreurs détectées qui nécessitent une modification du code HTML sont considérées comme des tentatives de piratage 
 * et donc entraînent l'appel de la fonction em_session_exit() sauf :
 * - les éventuelles suppressions des attributs required car l'attribut required est une nouveauté apparue dans la version HTML5 et 
 *   nous souhaitons que l'application fonctionne également correctement sur les vieux navigateurs qui ne supportent pas encore HTML5
 * - une éventuelle modification de l'input de type date en input de type text car c'est ce que font les navigateurs qui ne supportent 
 *   pas les input de type date
 *
 * @param mysqli    $bd      Objet permettant l'accès à la base de données
 * @global array    $_POST
 *
 * @return array    tableau assosiatif contenant les erreurs
 */
function aggl_traitement_modification2(mysqli $bd): array {
    /*------------------------- Etape 1 --------------------------------------------
    - vérification de la validité des données
    ------------------------------------------------------------------------------*/

    if( !em_parametres_controle('post', array('email', 'web', 'btnValider2'))) { 
        em_session_exit();
    }

    foreach($_POST as &$val){
    $val = trim($val);
    }

    $erreurs = array();

    // vérification du format de l'adresse email
    agg_verif_mail('email', $erreurs);

    // vérification du format du site web
    if(mb_strlen($_POST['web'], 'UTF-8') > 0) {
        if (mb_strlen($_POST['web'], 'UTF-8') > LMAX_WEB){
            $erreurs[] = 'Le site web ne peut pas dépasser '.LMAX_WEB.' caractères.';
        }
        if(! filter_var($_POST['web'], FILTER_VALIDATE_URL)) {
            $erreurs[] = 'Le site web n\'est pas valide.';
        }
    }

    // s'il y a des erreurs ==> on retourne le tableau d'erreurs    
    if (count($erreurs) > 0) {  
        return $erreurs;    
    }

    /*------------------------- Etape 2 --------------------------------------------
    - enregistrement des nouvelles modifications dans la base
    ------------------------------------------------------------------------------*/

    // pas d'erreurs ==> enregistrement des modifications de l'utilisateur
    $email = em_bd_proteger_entree($bd, $_POST['email']);
    $web = em_bd_proteger_entree($bd, $_POST['web']);

    $sql = "UPDATE users 
            SET usMail='".$email."', usWeb='".$web."'
            WHERE usID='".$_SESSION['usID']."'";
            
    em_bd_send_request($bd, $sql);
    return array();
}

/**
 *  Traitement de la modication des données de l'utlisateur du formulaire 3
 *
 *      Etape 1. vérification de la validité des données
 *                  -> return des erreurs si on en trouve
 *      Etape 2. enregistrement des nouvelles modifications dans la base
 *
 * Toutes les erreurs détectées qui nécessitent une modification du code HTML sont considérées comme des tentatives de piratage 
 * et donc entraînent l'appel de la fonction em_session_exit() sauf :
 * - les éventuelles suppressions des attributs required car l'attribut required est une nouveauté apparue dans la version HTML5 et 
 *   nous souhaitons que l'application fonctionne également correctement sur les vieux navigateurs qui ne supportent pas encore HTML5
 * - une éventuelle modification de l'input de type date en input de type text car c'est ce que font les navigateurs qui ne supportent 
 *   pas les input de type date
 *
 * @param mysqli    $bd      Objet permettant l'accès à la base de données
 * @global array    $_POST
 * @global array    $_SESSION
 * @global array    $_FILES
 *
 * @return array    tableau assosiatif contenant les erreurs
 */
function aggl_traitement_modification3(mysqli $bd): array {    
    /*------------------------- Etape 1 --------------------------------------------
    - vérification de la validité des données
    ------------------------------------------------------------------------------*/

    if( !em_parametres_controle('post', array('passe1', 'passe2', 'btnRadioPhoto', 'btnValider3')) || !isset($_FILES['photoAvatar'])) { 
        em_session_exit();
    }

    foreach($_POST as &$val){
    $val = trim($val);
    }

    $erreurs = array();

    // vérification des mots de passe si présents
    if(mb_strlen($_POST['passe1'], 'UTF-8') > 0 || mb_strlen($_POST['passe2'], 'UTF-8') > 0) {
        agg_verif_motDePasse('passe1' , 'passe2', $erreurs);
    }

    //var_dump($_POST);
    //var_dump($_FILES);
    
    // vérification de l'image de l'avatar si présente
    if ($_FILES['photoAvatar']['error']==0) { 

        //$currentDirectory = getcwd();
        $uploadDirectory = "../upload/";

        $fileExtensionsAllowed = ['jpeg','jpg']; // Un tableau contenant les extensions autorisées
        $fileName = $_FILES['photoAvatar']['name'];
        $fileSize = $_FILES['photoAvatar']['size'];
        $fileTmpName  = $_FILES['photoAvatar']['tmp_name'];
        $fileType = $_FILES['photoAvatar']['type'];
        
        $tab = explode('.',$fileName); //un tableau contenant comme dernier élément l'extension du fichier
        $fileExtension = strtolower(end($tab));

        // chemin où on vas uploder l'image
        $uploadPath = $uploadDirectory.$_SESSION['usID'].'.jpg';
        
        // vérification de l'extension de l'image
        if (! in_array($fileExtension,$fileExtensionsAllowed)) {
            $erreurs[] = "Le fichier doit être une image de type JPEG";
        }

        // vérification du poids de l'image (max 20ko)
        if ($fileSize > 20000) {
            $erreurs[] = "La taille maximum du fichier autorisée est de 20ko";
        }

        // vérification de la taille de l'image (min 50x50)
        list($width, $height) = getimagesize($fileTmpName);
            
        if ($width<50 || $height<50) {
            $erreurs[] = "La taille minimum de l'image doit être de 50x50";
        }
    
        // si pas d'erreur on upload l'image sur le serveur
        if (count($erreurs) == 0) {
            // si besoin, on redimensionne l'image
            if ($width>50 || $height>50) {
                $newImage = imagecreatetruecolor(50, 50);
                $source = imagecreatefromjpeg($fileTmpName);
                imagecopyresized($newImage, $source, 0, 0, 0, 0, 50, 50, $width, $height); // resize l'image originale (50x50)
                //imagecopymergegray($newImage, $newImage, 0, 0, 0, 0, 50, 50, 0); // passe l'image en niveau de gris
                imagejpeg($newImage, $fileTmpName);
            }

            $didUpload = move_uploaded_file($fileTmpName, $uploadPath);

            if (!$didUpload) {
                $erreurs[] = "Erreur lors de l'upload du fichier sur le serveur";
            }   
        }
    }

    /*------------------------- Etape 2 --------------------------------------------
    - enregistrement des nouvelles modifications dans la base
    ------------------------------------------------------------------------------*/

    // s'il y a des erreurs ==> on retourne le tableau d'erreurs    
    if (count($erreurs) > 0) {  
        return $erreurs;    
    }
    
    // pas d'erreurs ==> enregistrement des modifications de l'utilisateur
    $passe1 = password_hash($_POST['passe1'], PASSWORD_DEFAULT);
    $passe1 = em_bd_proteger_entree($bd, $passe1);
    $photo = em_bd_proteger_entree($bd, $_POST['btnRadioPhoto']);

    $sql = "UPDATE users 
            SET usAvecPhoto='".$photo.
                        (mb_strlen($_POST['passe1'], 'UTF-8') > 0 ? "', usPasse='".$passe1 : '')."'
            WHERE usID='".$_SESSION['usID']."'";
            
    em_bd_send_request($bd, $sql);
    
    return array();
}
?>