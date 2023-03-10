<h1 class="container-lg text-center">Liste des utilisateurs</h1>

<section class="container-lg col-lg-9 mb-5">
<div class="offset-3 offset-md-3">
            <a href="ajouter"><button type="submit" class="btn btn-primary">Valider</button></a>
        </div>
    <table class="table">
        <tr class="table-primary">
            <th>Nom</th>
            <th>Prénom</th>
            <th>Date d'embauche</th>
            <th>Date de départ</th>
            <th>Region</th>
            <th>Profil</th>
            <th>Editer</th>
            <th>Supprimer</th>
        </tr>
        <?php foreach ($lesUtilisateurs as $util) { ?>
            <tr>
                <td><?= $util->nom ?></td>
                <td><?= $util->prenom ?></td>
                <td><?= $util->date_embauche ?></td>
                <td><?= $util->date_depart ?></td>
                <td><?= $util->nom_profil ?></td>
                <td><?= $util->nom_region ?></td>
                <td class="text-center"><a href="modifierUtilisateur&<?= $util->id ?>" ><i class="far fa-edit"></i></a></td>
                <td class="text-center"><a href="supprimerUtilisateur&<?= $util->id ?>" ><i class="far fa-trash-alt"></i></a></td>
            </tr>
        <?php } ?>

    </table>
</section>