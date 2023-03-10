<?php
namespace Gsbfrais\models;

class TypeFraisForfaitManager extends Model
{
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Retourne la liste des types de frais forfaitisés
     * 
     * @return array tableau d'objets contenant les types de frais forfait
     */
    public function getLesTypesFrais():array
    {
        $sql = "select typefraisforfait.code, libelle 
                from typefraisforfait 
                order by typefraisforfait.code";
        $stmt = $this->db->prepare($sql);
        $ret = $stmt->execute();
        if ($ret == false) {
            http_response_code(500);
            throw new \Exception('Problème requête getLesTypesFrais (TypeFraisForfaitManager)', 99500);
        }
        return $stmt->fetchAll();
    }
}
