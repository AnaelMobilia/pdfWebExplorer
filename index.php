<?php
/*
 * Copyright 2020-2025 Anael MOBILIA
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

// Forcer le HTTPS
if ($_SERVER['HTTPS'] !== 'on') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    die();
}

require 'config.php';

// Variables de retour utilisateur
$logSuccess = '';
$logError = '';

// Envoi de fichiers sur la plateforme
if (isset($_FILES[FIELD_UPLOAD])) {
    // Traitement et enregistrement
    saveUploadedFiles($logError, $logSuccess);
}

?>
<!doctype html>
<html lang="en" class="h-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <title>pdfWebExplorer</title>
    <link rel="icon" type="image/png" href="<?= DEFAULT_THUMBS ?>" sizes="16x16">

    <!-- Bootstrap core CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
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
        <a class="navbar-brand" href="<?= BASE_URL ?>">
            <img src="<?= DEFAULT_THUMBS ?>" width="30" height="30" alt="pdfWebExplorer">
            pdfWebExplorer <small class="text-secondary">(<?= count(scandir(PATH_DATAS)) ?> fichiers)</small>
        </a>
        <!-- Catégorie de documents -->
        <div class="nav-item">
            <label for="categorie" class="form-label">Catégories</label>
            <select class="form-select d-inline" id="categorie" style="width: auto !important;"
                    onchange="self.location.href='<?= BASE_URL ?>?cat='+this.value;">
                <option value="">Toutes</option>
                <?php foreach (CATEGORIES as $id => $uneCategorie) : ?>
                    <option value="<?= $id ?>" <?= (isset($_REQUEST['cat'])&& is_numeric($_REQUEST['cat']) && (int) $_REQUEST['cat'] === $id ? 'selected' : '') ?>><?= $uneCategorie ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <form class="form-inline" action="#">
            <input class="form-control" type="search" placeholder="Rechercher" aria-label="Rechercher"
                   id="<?= FIELD_SEARCH ?>" onkeyup="maRecherche()">
        </form>
        <!-- Envoi de fichiers PDF -->
        <button type="button" class="btn btn-info me-3" data-bs-toggle="modal" data-bs-target="#modalUpload">
            Envoyer des fichiers
        </button>
    </nav>
</header>
<!-- Modal d'envoi des fichiers -->
<div class="modal fade" id="modalUpload" tabindex="-1" aria-labelledby="modalUploadLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalUploadLabel">Envoyer des fichiers</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="<?= FIELD_UPLOAD ?>" class="form-label">Document(s)</label>
                        <input name="<?= FIELD_UPLOAD ?>[]" id="<?= FIELD_UPLOAD ?>" accept="<?= MIME_TYPE ?>" type="file" class="form-control" multiple onchange="verifierNombreFichiers()"/>
                    </div>
                    <!-- Catégorie du fichier -->
                    <div class="mb-3">
                        <label for="categorie" class="form-label">Catégorie</label>
                        <select name="cat" class="form-select" id="categorie">
                            <?php foreach (CATEGORIES as $id => $uneCategorie) : ?>
                                <option value="<?= $id ?>"><?= $uneCategorie ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="submit" class="btn btn-info" value="Envoyer"/>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Modal de renommage d'un fichier -->
<div class="modal fade" id="modalRename" tabindex="-1" aria-labelledby="modalRenameLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalRenameLabel">Renommer un fichier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label for="name" class="form-label">Nom du fichier</label>
                        <input name="name" id="name" type="text" class="form-control"/>
                    </div>
                    <!-- Catégorie du fichier -->
                    <div class="mb-3">
                        <label for="categorie" class="form-label">Catégorie</label>
                        <select name="cat" class="form-select" id="categorie">
                            <?php
                            foreach (CATEGORIES as $id => $uneCategorie) : ?>
                                <option value="<?= $id ?>"><?= $uneCategorie ?></option>
                            <?php
                            endforeach; ?>
                        </select>
                    </div>
                    <input type="submit" class="btn btn-info" value="Envoyer"/>
                </form>
            </div>
        </div>
    </div>
</div>
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
            <?php foreach (getHtmlForFiles() as $htmlFichier): ?>
                <?= $htmlFichier ?>
            <?php endforeach; ?>
        </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
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
            alert("Trop de fichiers ont été sélectionnés (maximum <?= ini_get('max_file_uploads') ?>)");
        }
    }

    /**
     * Effectuer une action sur un fichier en ajax
     * @param action Action à effectuer
     * @param filename Nom du fichier sur lequel effectuer l'action
     * @param e élément HTML pour injecter la modification
     */
    function ajaxCall(action, filename, e) {
        let url = "ajax.php?action=" + action + "&filename=" + encodeURIComponent(filename)

        if (action === '<?=ACTION_RENOMMER ?>') {
            const newname = prompt("Nouveau nom du fichier ?", filename);
            url += '&newName=' + encodeURIComponent(newname);
        }
        let req = new XMLHttpRequest();
        req.open("GET", url);
        req.addEventListener("load", function () {
            if (req.status === 200) {
                // Fermer toutes les tooltips
                tooltipTriggerList.forEach((tooltip) => {
                    const instance = bootstrap.Tooltip.getInstance(tooltip);
                    instance.hide();
                })
                // Mettre à jour l'interface
                e.innerHTML = req.response;
                if (action === '<?=ACTION_ARCHIVER ?>') {
                    e.remove();
                }
            } else {
                console.error(req.status + " " + req.statusText + " " + url);
            }
        });
        req.addEventListener("error", function () {
            console.error("Erreur réseau avec l'URL " + url);
        });
        req.send(null);
    }

    // Activer les tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))
</script>
</body>
</html>