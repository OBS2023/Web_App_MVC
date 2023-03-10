<?php
use Gsbfrais\Autoloader;
use Gsbfrais\Controllers\{AccueilController, FicheFraisController, FraisForfaitController, FraisHorsForfaitController, UtilisateurController};

session_start();
require_once('config/config.php');
require_once('includes/fonctions.php');
require_once "./autoloader.php";

Autoloader::register();

$page = "accueil";
if (isset($_GET['action'])) {
    $page = $_GET['action'];
}
if (estVisiteurConnecte() == false) {
    $page = 'login';
}
try {
    switch ($page) {
        case 'accueil':
            $controller = new AccueilController();
            $controller->accueil();
            break;
        case 'gereUtilisateurs':
            $controller = new UtilisateurController();
            $controller->gereUtilisateurs();
            break;
        case 'login':
            $controller = new UtilisateurController();
            $controller->login();
            break;
        case 'ajouterUtilisateur':
            $controleur = new UtilisateurController();
            $controleur->ajouterUtilisateur();
            break;
        case 'modifierUtilisateur':
            $controleur = new UtilisateurController();
            $controleur->modifierUtilisateur();
            break;
        case 'supprimerUtilisateur':
            $controleur = new UtilisateurController();
            $controleur->supprimerUtilisateur();
            break;
        case 'saisirFraisForfait':
            $controller = new FraisForfaitController();
            $controller->saisirFraisForfait();
            break;
        case 'saisirFraisHorsForfait':
            $controller = new FraisHorsForfaitController();
            $controller->saisirFraisHorsForfait();
            break;
        case 'supprimerFraisHorsForfait':
            $numFrais = filter_input(INPUT_GET, 'numFrais', FILTER_VALIDATE_INT);
            if ($numFrais === false or $numFrais === null) {
                http_response_code(400);
                throw new Exception('Bad request');
            }
            $controller = new FraisHorsForfaitController();
            $controller->supprimerFraisHorsForfait($numFrais);
            break;
        case 'voirFicheFrais':
            $controller = new FicheFraisController();
            $controller->voirFicheFrais();
            break;
        case 'cloturerFichesFrais':
            $controller = new FicheFraisController();
            $controller->cloturerFichesFrais();
            break;
        case 'validerVoirLesFichesFrais':
            $controller = new FicheFraisController();
            $controller->validerVoirLesFichesFrais();
            break;   
        case 'validerFicheFrais':
            $idVisiteur = filter_input(INPUT_GET, 'idVisiteur', FILTER_VALIDATE_INT);
            $mois = filter_input(INPUT_GET, 'mois', FILTER_VALIDATE_INT);
            if ($idVisiteur === false or $idVisiteur === null or 
                $mois === false or $mois === null ) {
                http_response_code(400);
                throw new Exception('Bad request');
            }
            $controller = new FicheFraisController();
            $controller->validerFicheFrais($idVisiteur, $mois);
            break;
        case 'logout':
            $controller = new UtilisateurController();
            $controller->logout();
            break;
        default:
            http_response_code(404);
            throw new Exception('Not Found');
    }
} catch(Exception $e) {
    
    $codeHttp = http_response_code();

    if ($codeHttp != 200) {
        $erreurHttp = $lesErreursHttp[$codeHttp][0];
        $errorMsg = $lesErreursHttp[$codeHttp][1];
    } else {
        $erreurHttp = '';
        $errorMsg = $e->getMessage();
    }
    require('views/vue-erreurs.php');
}