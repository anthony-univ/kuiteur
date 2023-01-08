<?php
ob_start(); //démarre la bufferisation

require_once 'bibli_generale.php';
require_once 'bibli_cuiteur.php';

em_aff_debut('Cuiteur | Inscription');

echo '<h1>Réception du formulaire<br>Inscription utilisateur</h1>';

if( !em_parametres_controle('post', array('pseudo', 'email', 'nomprenom', 'naissance', 
                                              'passe1', 'passe2', 'btnSInscrire'))) {
    header('Location: ../index.php'); 
    exit();
}

foreach($_POST as &$val){
    $val = trim($val);
}

$erreurs = array();

// vérification du pseudo
$l = mb_strlen($_POST['pseudo'], 'UTF-8');
if ($l == 0){
    $erreurs[] = 'Le pseudo doit être renseigné.';
}
else if ($l < LMIN_PSEUDO || $l > LMAX_PSEUDO){
    $erreurs[] = 'Le pseudo doit être constitué de '. LMIN_PSEUDO . ' à ' . LMAX_PSEUDO . ' caractères.';
}
else if( !mb_ereg_match('^[[:alnum:]]{'.LMIN_PSEUDO.','.LMAX_PSEUDO.'}$', $_POST['pseudo'])){
    $erreurs[] = 'Le pseudo ne doit contenir que des caractères alphanumériques.' ;
}

// vérification des mots de passe
if ($_POST['passe1'] !== $_POST['passe2']) {
    $erreurs[] = 'Les mots de passe doivent être identiques.';
}
$nb = mb_strlen($_POST['passe1'], 'UTF-8');
if ($nb < LMIN_PASSWORD || $nb > LMAX_PASSWORD){
    $erreurs[] = 'Le mot de passe doit être constitué de '. LMIN_PASSWORD . ' à ' . LMAX_PASSWORD . ' caractères.';
}

// vérification des noms et prenoms
if (empty($_POST['nomprenom'])) {
    $erreurs[] = 'Le nom et le prénom doivent être renseignés.'; 
}
else {
    if (mb_strlen($_POST['nomprenom'], 'UTF-8') > LMAX_NOMPRENOM){
        $erreurs[] = 'Le nom et le prénom ne peuvent pas dépasser ' . LMAX_NOMPRENOM . ' caractères.';
    }
    $noTags = strip_tags($_POST['nomprenom']);
    if ($noTags != $_POST['nomprenom']){
        $erreurs[] = 'Le nom et le prénom ne peuvent pas contenir de code HTML.';
    }
    else {
        if( !mb_ereg_match('^[[:alpha:]]([\' -]?[[:alpha:]]+)*$', $_POST['nomprenom'])){
            $erreurs[] = 'Le nom et le prénom contiennent des caractères non autorisés.';
        }
    }
}

// vérification du format de l'adresse email
if (empty($_POST['email'])){
    $erreurs[] = 'L\'adresse mail ne doit pas être vide.'; 
}
else {
    if (mb_strlen($_POST['email'], 'UTF-8') > LMAX_EMAIL){
        $erreurs[] = 'L\'adresse mail ne peut pas dépasser '.LMAX_EMAIL.' caractères.';
    }
    // la validation faite par le navigateur en utilisant le type email pour l'élément HTML input
    // est moins forte que celle faite ci-dessous avec la fonction filter_var()
    // Exemple : 'l@i' passe la validation faite par le navigateur et ne passe pas
    // celle faite ci-dessous
    if(! filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $erreurs[] = 'L\'adresse mail n\'est pas valide.';
    }
}

// vérification de la date de naissance
if (empty($_POST['naissance'])){
    $erreurs[] = 'La date de naissance doit être renseignée.'; 
}
else{
    if( !mb_ereg_match('^\d{4}(-\d{2}){2}$', $_POST['naissance'])){ //vieux navigateur qui ne supporte pas le type date ?
        $erreurs[] = 'la date de naissance doit être au format "AAAA-MM-JJ".'; 
    }
    else{
        list($annee, $mois, $jour) = explode('-', $_POST['naissance']);
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

if (count($erreurs) == 0) {
    // vérification de l'unicité du pseudo 
    // (uniquement si pas d'autres erreurs, parce que la connection à la base de données est consommatrice de ressources)
    $bd = em_bd_connect();

    // pas utile, car le pseudo a déjà été vérifié, mais tellement plus sécurisant...
    $pseudo = em_bd_proteger_entree($bd, $_POST['pseudo']);
    $sql = "SELECT usID FROM users WHERE usPseudo = '$pseudo'"; 

    $res = em_bd_send_request($bd, $sql);
    
    if (mysqli_num_rows($res) != 0) {
        $erreurs[] = 'Le pseudo spécifié est déjà utilisé.';
        
        // libération des ressources 
        mysqli_free_result($res);
        mysqli_close($bd);
    }
    else{
        // libération des ressources 
        mysqli_free_result($res);
    }
}


// s'il y a des erreurs ==> on retourne le tableau d'erreurs    
if (count($erreurs) > 0) {  
    echo '<p>Votre inscription n\'a pas pu être réalisée à cause des erreurs suivantes : ';
    foreach ($erreurs as $v) {
        echo '<br> - ', $v;
    }
    echo '</p>';
    em_aff_fin();
    exit(); //==> FIN DU SCRIPT
}

// pas d'erreurs ==> enregistrement de l'utilisateur
$nomprenom = em_bd_proteger_entree($bd, $_POST['nomprenom']);
$email = em_bd_proteger_entree($bd, $_POST['email']);

$passe1 = password_hash($_POST['passe1'], PASSWORD_DEFAULT);
$passe1 = em_bd_proteger_entree($bd, $passe1);

$aaaammjj = $annee*10000  + $mois*100 + $jour;

$date_inscription = date('Ymd');

$sql = "INSERT INTO users(usNom, usVille, usWeb, usMail, usPseudo, usPasse, usBio, usDateNaissance, usDateInscription) 
        VALUES ('$nomprenom', '', '', '$email', '$pseudo', '$passe1',  '', $aaaammjj, $date_inscription)";
        
em_bd_send_request($bd, $sql);

// libération des ressources
mysqli_close($bd);

echo '<p>Un nouvel utilisateur a bien été enregistré</p>';

em_aff_fin();

?>
