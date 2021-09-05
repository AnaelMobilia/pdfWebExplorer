<?php
/*
 * Copyright 2020-2021 Anael MOBILIA
 *
 * This file is part of pdfWebExplorer
 *
 * pdfWebExplorer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * pdfWebExplorer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with pdfWebExplorer If not, see <http://www.gnu.org/licenses/>
 */
// Est-ce une tâche cron
define('IS_CRON', !isset($_SERVER['REMOTE_ADDR']));

// Forcer le HTTPS (sauf pour tâche cron)
if ($_SERVER["HTTPS"] != "on" && !IS_CRON) {
    header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
    die();
}

require "config.php";
require "fonctions.php";

// Variables de retour utilisateur
$logSuccess = "";
$logError = "";

// Envoi de fichiers sur la plateforme
if (isset($_FILES[FIELD_UPLOAD])) {
    // Traitement & enregistrement
    saveUploadedFiles($logError, $logSuccess);
}

// Si on demande une mise à jour des miniatures
if (isset($_GET['updateCache']) || IS_CRON) {
    foreach (getPdfFiles(PATH_DATAS) as $unFichier) {
        $miniatureFichier = PATH_THUMBS . $unFichier . ".png";
        // Génération des miniatures manquantes///
        if (!file_exists($miniatureFichier)) {
            try {
                genPdfThumbnail(PATH_DATAS . $unFichier, $miniatureFichier);
            } catch (ImagickException $e) {
                $logError .= "Erreur à la génération de la miniature pour " . $unFichier . " (" . $e->getTraceAsString() . ")";
            }
        }
    }
    die();
}

?>
<!doctype html>
<html lang="en" class="h-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <title>phpWebExplorer</title>
    <link rel="icon" type="image/png" href="<?= DEFAULT_THUMBS ?>" sizes="16x16">

    <!-- Bootstrap core CSS -->
    <link href="css/bootstrap-5.1.0.min.css" rel="stylesheet"
          integrity="sha384-KyZXEAg3QhqLMpG8r+8fhAXLRk2vvoC2f3B09zVXn8CA5QIVfZOJ3BCsw2P0p/We" crossorigin="anonymous">
    <!-- Sticky navbar -->
    <style>
        /* Show it is fixed to the top */
        body {
            min-height: 75rem;
            padding-top: 4.5rem;
        }
    </style>
</head>
<body class="d-flex flex-column h-100">
<header>
    <!-- navbar -->
    <nav class="navbar navbar-light bg-light fixed-top">
        <a class="navbar-brand" href="#">
            <img src="<?= DEFAULT_THUMBS ?>" width="30" height="30" alt="pdfWebExplorer">
            pdfWebExplorer
        </a>
        <!-- Type d'affichage -->
        <label for="affichage" class="form-label">Affichage :</label>
        <select class="form-select d-inline" id="affichage" style="width: auto !important;"
                onchange="self.location.href='<?= BASE_URL ?>'+this.value;">
            <option value="?" <?= (SLOW_CONNEXION ? '' : 'selected') ?>>Standard</option>
            <option value="?slow" <?= (SLOW_CONNEXION ? 'selected' : '') ?>>Simplifié</option>
        </select>
        <!-- Catégorie de documents -->
        <label for="categorie" class="form-label">Type :</label>
        <select class="form-select d-inline" id="categorie" style="width: auto !important;"
                onchange="self.location.href='<?= BASE_URL_AFFICHAGE ?>&cat='+this.value;">
            <option value="<?= CATEGORIES_TOUTES ?>">Tous</option>
            <?php foreach (CATEGORIES as $id => $uneCategorie) : ?>
                <option value="<?= $id ?>" <?= ((isset($_REQUEST["cat"]) && $_REQUEST["cat"] == $id) ? "selected" : "") ?>><?= $uneCategorie ?></option>
            <?php endforeach; ?>
        </select>
        <!-- Envoi de fichiers PDF -->
        <form method="POST" enctype="multipart/form-data" class="form-inline border border-info rounded">
            &nbsp;
            <input name="<?= FIELD_UPLOAD ?>[]" id="<?= FIELD_UPLOAD ?>" accept="<?= MIME_TYPE ?>" type="file"
                   class="file" multiple onchange="verifierNombreFichiers()"/>
            &nbsp;
            <input type="submit" class="btn btn-info" value="Envoyer des fichiers"/>
            &nbsp;
        </form>
        <form class="form-inline my-2 my-lg-0" action="#">
            <input class="form-control mr-sm-2" type="search" placeholder="Rechercher" aria-label="Rechercher"
                   id="<?= FIELD_SEARCH ?>" onkeyup="maRecherche()">
        </form>
    </nav>
</header>

<!-- Begin page content -->
<main role="main" class="flex-shrink-0">
    <div class="container" id="monContainer">
        <?php if (!empty($logSuccess)) : ?>
            <div class="alert alert-success" role="alert">
                <?= $logSuccess ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($logError)) : ?>
            <div class="alert alert-danger" role="alert">
                <?= $logError ?>
            </div>
        <?php endif; ?>
        <div class="row">
            <?= (SLOW_CONNEXION ? "<ul>" : "") ?>
            <?php foreach (getHtmlForFiles(SLOW_CONNEXION) as $unFichier): ?>
                <<?= (SLOW_CONNEXION ? "li" : "div") ?> class="col">
                <?= $unFichier ?>
                <<?= (SLOW_CONNEXION ? "/li" : "/div") ?>>
            <?php endforeach; ?>
            <?= (SLOW_CONNEXION ? "</ul>" : "") ?>
        </div>
</main>

<script>
    /**
     * Filtre les éléments affichés en fonction de la saisie de l'utilisateur
     */
    function maRecherche() {
        // https://www.w3schools.com/howto/howto_js_filter_table.asp
        const valeurCherchee = document.querySelector("#<?= FIELD_SEARCH ?>").value.toUpperCase();
        const mesChamps = document.querySelectorAll(".col");
        for (let i = 0; i < mesChamps.length; i++) {
            let monChamp = mesChamps[i].getElementsByTagName("a")[0];
            if (monChamp) {
                let txtValue = monChamp.textContent || monChamp.innerText;
                if (txtValue.toUpperCase().indexOf(valeurCherchee) > -1) {
                    mesChamps[i].style.display = "";
                } else {
                    mesChamps[i].style.display = "none";
                }
            }
        }
    }

    /**
     * Vérifie le nombre de fichiers à envoyer
     */
    function verifierNombreFichiers() {
        const input = document.querySelector("#<?= FIELD_UPLOAD ?>");
        // Trop de fichiers
        if (input.files.length > <?= ini_get('max_file_uploads') ?>) {
            alert("Trop de fichiers ont été selectionnés (maximum <?= ini_get('max_file_uploads') ?>)");
        }
    }
</script>
</body>
</html>