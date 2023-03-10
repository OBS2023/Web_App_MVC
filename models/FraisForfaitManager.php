<?php
namespace Gsbfrais\models;

class FraisForfaitManager extends Model
{
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Retourne tous les frais forfaitisés d'un visiteur donné pour un mois donné
     * @param int $idVisiteur int identifiant du visiteur médical
     * @param int $mois int mois sous la forme aaaamm
     * 
     * @return array tableau d'objets contenant les frais forfaitisés répondant aux critères
     */
    public function getLesFraisForfait(int $idVisiteur, int $mois):array
    {
        $sql = "select code_typefrais, typefraisforfait.libelle, fraisforfait.quantite 
                from fraisforfait 
                join typefraisforfait on typefraisforfait.code = fraisforfait.code_typefrais
                where fraisforfait.id_visiteur =:id_visiteur and fraisforfait.mois=:mois
                order by fraisforfait.code_typefrais";
        $stmt = $this->db->prepare($sql);
        $ret = $stmt->execute(array(
            ':id_visiteur' => $idVisiteur,
            ':mois' => $mois
        ));
        if ($ret == false) {
            http_response_code(500);
            throw new \Exception('Problème requête getLesFraisForfait (FraisForfaitManager)', 99500);
        }
        return $stmt->fetchAll();
    }

    /**
     * Ajoute un frais forfaitisé
     * 
     * @param int $idVisiteur identifiant du visiteur médical concerné
     * @param int $mois int sous la forme aaaamm période concernée
     * @param int $codeTypeFrais code du type de frais
     * @param int $quantite quantite de frais forfaitisé
     * 
     * @return void
     */
    public function ajouteFraisForfait(int $idVisiteur, int $mois, string $idTypeFrais, int $quantite) {
        $sql = "insert into fraisforfait(id_visiteur, mois, code_typefrais, quantite) 
        values(:id_visiteur, :mois, :code_typefrais, :quantite)";
        $stmt = $this->db->prepare($sql);
        $ret = $stmt->execute(array(
            ':id_visiteur' => $idVisiteur,
            ':mois' => $mois,
            ':code_typefrais' => $idTypeFrais,
            ':quantite' => $quantite
        ));
        if ($ret == false) {
            http_response_code(500);
            throw new \Exception('Problème requête ajouteFraisForfait (FraisForfaitManager)', 99500);
        }
    }
    
    /**
     * Modifie un frais forfaitisé
     * 
     * @param int $idVisiteur identifiant du visiteur médical concerné
     * @param int $mois int sous la forme aaaamm période concernée
     * @param int $codeTypeFrais code du type de frais
     * @param int $quantite nouvelle quantite
     * 
     * @return void
     */
    public function modifieFraisForfait(int $idVisiteur, int $mois, string $codeTypeFrais, int $quantite):void
    {
        $sql = "update fraisforfait set fraisforfait.quantite = $quantite
        where fraisforfait.id_visiteur = :id_visiteur 
        and fraisforfait.mois = :mois
        and fraisforfait.code_typefrais = '$codeTypeFrais'";
        $stmt = $this->db->prepare($sql);
        $ret = $stmt->execute(array(
            ':id_visiteur' => $idVisiteur,
            ':mois' => $mois
        ));
        if ($ret == false) {
            http_response_code(500);
            throw new \Exception('Problème requête modifieFraisForfait (FraisForfaitManager)', 99500);
        }
    }
    
}
