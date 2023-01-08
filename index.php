<?php
/* ------------------------------------------------------------------------------
    Architecture de la page
    - étape 1 : vérifications diverses et traitement des soumissions
    - étape 2 : génération du code HTML de la page
------------------------------------------------------------------------------*/

ob_start(); //démarre la bufferisation
session_start();

require_once './php/bibli_generale.php';
require_once './php/bibli_cuiteur.php';

/*------------------------- Etape 1 --------------------------------------------
- vérifications diverses et traitement des soumissions
------------------------------------------------------------------------------*/

// si utilisateur déjà authentifié, on le redirige vers la page cuiteur_1.php
if (em_est_authentifie()){
    header('Location: cuiteur.php');
    exit();
}

// traitement si soumission du formulaire d'inscription
$er = isset($_POST['btnSConnecter']) ? eml_traitement_inscription() : array();

/*------------------------- Etape 2 --------------------------------------------
- génération du code HTML de la page
------------------------------------------------------------------------------*/

em_aff_debut('Cuiteur | Se connecter', './styles/cuiteur.css');

em_aff_entete('Se connecter', "deconnecter");
em_aff_infos(false);

eml_aff_formulaire($er);

em_aff_pied();
em_aff_fin();

// facultatif car fait automatiquement par PHP
ob_end_flush();

// ----------  Fonctions locales du script ----------- //

/**
 * Affichage du contenu de la page (formulaire d'inscription)
 *
 * @param   array   $err    tableau d'erreurs à afficher
 * @global  array   $_POST
 */
function eml_aff_formulaire(array $err): void {

    // réaffichage des données soumises en cas d'erreur, sauf les mots de passe 
    if (isset($_POST['btnSConnecter'])){
        $values = em_html_proteger_sortie($_POST);
    }
    else{
        $values['pseudo'] = '';
    }
        
    if (count($err) > 0) {
        echo '<p class="error">Les erreurs suivantes ont été détectées :';
        foreach ($err as $v) {
            echo '<br> - ', $v;
        }
        echo '</p>';    
    }


    echo    
            '<p>Pour vous connecter, il faut vous authentifier. </p>',
            '<form method="post" action="index.php">',
                '<table>';

    em_aff_ligne_input( 'Votre pseudo :', array('type' => 'text', 'name' => 'pseudo', 'value' => $values['pseudo'], 
                        'placeholder' => 'Minimum 4 caractères alphanumériques', 'required' => null));
    em_aff_ligne_input('Votre mot de passe :', array('type' => 'password', 'name' => 'passe', 'value' => '', 'required' => null));

    echo 
                    '<tr>',
                        '<td colspan="2">',
                            '<input type="submit" name="btnSConnecter" value="Connexion">', 
                        '</td>',
                    '</tr>',
                '</table>',
            '</form>';

    echo    
            '<p>Pas encore de compte ? <a href="./php/inscription.php"><strong>Inscrivez-vous</strong></a> sans tarder !<br> 
            Vous hésitez à vous inscire ? laissez-vous séduire par une <a href="./html/presentation.html"><strong>présentation</strong></a> des possibilitées de Cuiteur</p>';
}


/**
 *  Traitement de la connexion 
 *
 *      Etape 1. vérification de la validité des données
 *                  -> return des erreurs si on en trouve
 *      Etape 2. ouverture de la session et redirection vers la page cuiteur.php 
 *
 * Toutes les erreurs détectées qui nécessitent une modification du code HTML sont considérées comme des tentatives de piratage 
 * et donc entraînent l'appel de la fonction em_session_exit() sauf :
 * - les éventuelles suppressions des attributs required car l'attribut required est une nouveauté apparue dans la version HTML5 et 
 *   nous souhaitons que l'application fonctionne également correctement sur les vieux navigateurs qui ne supportent pas encore HTML5
 * - une éventuelle modification de l'input de type date en input de type text car c'est ce que font les navigateurs qui ne supportent 
 *   pas les input de type date
 *
 * @global array    $_POST
 *
 * @return array    tableau assosiatif contenant les erreurs
 */
function eml_traitement_inscription(): array {
    
    if( !em_parametres_controle('post', array('pseudo', 
                                              'passe', 'btnSConnecter'))) {
        em_session_exit();   
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
    $nb = mb_strlen($_POST['passe'], 'UTF-8');
    if ($nb < LMIN_PASSWORD || $nb > LMAX_PASSWORD){
        $erreurs[] = 'Le mot de passe doit être constitué de '. LMIN_PASSWORD . ' à ' . LMAX_PASSWORD . ' caractères.';
    }

    if (count($erreurs) == 0) {
        // vérification de l'unicité du pseudo 
        // (uniquement si pas d'autres erreurs, parce que la connection à la base de données est consommatrice de ressources)
        $bd = em_bd_connect();

        // pas utile, car le pseudo a déjà été vérifié, mais tellement plus sécurisant...
        $pseudo = em_bd_proteger_entree($bd, $_POST['pseudo']);
        $sql = "SELECT usID,usPasse FROM users WHERE usPseudo = '$pseudo'"; 
    
        $res = em_bd_send_request($bd, $sql);
        
        if (mysqli_num_rows($res) === 0) {
            $erreurs[] = 'Le pseudo n\'existe pas.';
            // libération des ressources 
            mysqli_free_result($res);
            mysqli_close($bd);
            return $erreurs;
        }

        $t = mysqli_fetch_assoc($res);
        $usid = $t['usID']; 
        if (!password_verify($_POST['passe'], $t['usPasse'])) {
            $erreurs[] = 'Le mot de passe est incorrect.';
        }
        // libération des ressources 
        mysqli_free_result($res);
        mysqli_close($bd);
    }
    
    // s'il y a des erreurs ==> on retourne le tableau d'erreurs    
    if (count($erreurs) > 0) {  
        return $erreurs;    
    }

    // redirection vers la page cuiteur.php
    $_SESSION['usID'] = $usid;
    header('Location: ./php/cuiteur.php'); //TODO : à modifier dans le projet
    exit();
}
?>