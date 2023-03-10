<?php
namespace Gsbfrais\Controllers;

use Gsbfrais\models\UtilisateurManager;

class UtilisateurController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function login():void
    {
        $errorMessage = '';

        if (count($_POST) > 0) {
            $errorMessage = $this->validerConnexion();

            if (empty($errorMessage) == true) {
                header("Location: " . ROOT_URL . "accueil");
            }
        }

        $this->render('utilisateur/connexion', [
            'title' => 'Connexion',
            'errorMessage' => $errorMessage
        ]);
    }

    private function validerConnexion():string
    {
        $errors = '';
        $utilisateurManager = new UtilisateurManager();

        $login = $_POST['login'];
        $mdp = $_POST['mdp'];
        $utilisateur = $utilisateurManager->getUtilisateur($login);

        if ($utilisateur === false || password_verify($mdp, $utilisateur->mot_passe) == false) {
            $errors .= "Login et/ou mot de passe incorrects";
        } else {
            $_SESSION['idUtil'] = $utilisateur->id;
            $_SESSION['nomUtil'] =  $utilisateur->nom;
            $_SESSION['prenomUtil'] =  $utilisateur->prenom;
            $_SESSION['profilUtil'] =  $utilisateur->nom_profil;
        }
        return $errors;
    }

    public function logout():void
    {
        // Détruit toutes les variables de session
        $_SESSION = [];

        // Supprime le cookie de session.
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // Détruit la session.
        session_destroy();

        header("Location: " . ROOT_URL . "login");
    }
    //----------------------------------------------------------------//
    
    public function gereUtilisateurs(): void
    {
        $utilisateurManager = new UtilisateurManager();
        $utilisateurs = $utilisateurManager->getLesUtilisateurs();

        $this->render('utilisateur/gerer',[
            'title'=>'Gestion utilisateurs',
            'lesUtilisateurs'=> $utilisateurs
        ]);
        
    }
    public function ajouterUtilisateur(): void
    {
        $nom = '';
        $prenom = '';
        $mot_pass = '';
        $login = '';
        $date_embauche = '';
        $date_depart = '';
        $id_profil = '';
        $id_region = '';
        $utilisateurManager = new UtilisateurManager();
        $saisirUtilisateur = $utilisateurManager->ajouter($nom,$prenom,$mot_pass,$login,$date_embauche,$date_depart,$id_profil,$id_region);

        $this->render('gererUtilisateurs/ajouterUtilisateur',[
            'title'=>'ajouterUtilisateur'
        ]);
    }

    public function modifierUtilisateur():void
    {
        $this->render('gererUtilisateurs/modifierUtilisateur',[
            'title'=>'modifierUtilisateur'
        ]);
    }
    public function supprimerUtilisateur():void
    {
        $this->render('gererUtilisateurs/supprimerUtilisateur',[
            'title'=>'supprimerUtilisateur'
        ]);
    }
    //--------------------------------------------------------------//
}
