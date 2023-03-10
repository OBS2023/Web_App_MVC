<?php
namespace Gsbfrais\Controllers;

class Controller 
{
    protected $idUtilAuthentifie;       // identifiant de l'utilisateur connecté
    protected $nomUtilAuthentifie;      // nom de l'utilisateur connecté
    protected $prenomUtilAuthentifie;   // prénom de l'utilisateur connecté
    protected $profilUtilAuthentifie;   // profil de l'utilisateur connecté
    protected $mois;                    // mois en cours au format aaaamm

    public function __construct()
    {
        if (isset($_SESSION['idUtil'])) {
            $this->idUtilAuthentifie = $_SESSION['idUtil'];  
            $this->nomUtilAuthentifie = $_SESSION['nomUtil'];  
            $this->prenomUtilAuthentifie = $_SESSION['prenomUtil'];  
            $this->profilUtilAuthentifie = $_SESSION['profilUtil'];  
        }
        $this->mois = date("Ym"); 
    }

    public function render(string $view, array $data = [])
	{
        $file = ROOT_PATH . "views/$view" . ".php";
        $template = ROOT_PATH . 'views/template.php';

		if (file_exists($file) == true) {
			extract($data);
			ob_start();
			require_once($file);
			$content = ob_get_clean();
			require_once($template);
		} else {
			http_response_code(500);
			throw new \Exception('Erreur interne');
		}
	}

}