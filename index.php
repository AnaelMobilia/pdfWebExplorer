<?php
/*
 * Copyright 2020-2020 Anael MOBILIA
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
// Connexion limitée
define('SLOW_CONNEXION', isset($_REQUEST['slow']));

// Forcer le HTTPS (sauf pour tâche cron)
if ($_SERVER["HTTPS"] != "on" && !IS_CRON) {
    header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
    die();
}

// Répertoire & URL pour les les fichiers PDF
define('FOLDER_DATAS', '/fichiers/');
define('PATH_DATAS', __DIR__ . FOLDER_DATAS);
define('URL_DATAS', "https://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . substr(FOLDER_DATAS, 1));
// Répertoire & URL pour les les miniatures des fichiers PDF
define('FOLDER_THUMBS', '/miniatures/');
define('PATH_THUMBS', __DIR__ . FOLDER_THUMBS);
define('URL_THUMBS', "https://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . substr(FOLDER_THUMBS, 1));
// Miniature par défaut
define('DEFAULT_THUMBS', URL_THUMBS . "default_image.png");
// Type MIME des fichiers acceptés
define('MIME_TYPE', 'application/pdf');

// Nom de champs utilisés en JS
define('FIELD_SEARCH', 'recherche');
define('FIELD_UPLOAD', 'upload');

// Variables de retour utilisateur
$logSuccess = "";
$logError = "";

/**
 * Génère une miniature d'un fichier PDF
 * @param $source string PATH du fichier source
 * @param $destination string PATH de l'image destination
 * @throws ImagickException
 */
function genPdfThumbnail($source, $destination)
{
    echo $source . " -> " . $destination . "\r\n";
    $im = new Imagick($source . "[0]"); // 0-first page, 1-second page
    $im->setImageColorspace(255); // prevent image colors from inverting
    $im->setimageformat("png");
    $im->thumbnailimage(150, 150); // width and height
    $im->writeimage($destination);
    $im->clear();
    $im->destroy();
}

/**
 * Liste des fichiers PDF contenus dans un répertoire
 * @param $path string PATH à analyser (sans récursivité
 * @return ArrayObject des fichiers
 */
function getPdfFiles($path)
{
    $monRetour = new ArrayObject();

    $listeBrute = scandir($path);
    foreach ($listeBrute as $unItem) {
        // Si ce n'est pas un dossier...
        if (!is_dir($path . $unItem) && substr($unItem, -4) == ".pdf") {
            // On l'ajoute au retour
            $monRetour->append($unItem);
        }
    }
    return $monRetour;
}

/**
 * Génère le code HTML pour afficher les fichiers, miniatures, liens...
 * @param boolean Faut-il cacher les miniatures
 * @return ArrayObject code HTML
 */
function getHtmlForFiles($hideThumbs)
{
    $monRetour = new ArrayObject();
    foreach (getPdfFiles(PATH_DATAS) as $unFichier) {
        $monHtml = "";
        $nomMiniature = $unFichier . ".png";
        $nomAffiche = str_replace(".pdf", "", $unFichier);
        if (file_exists(PATH_THUMBS . $nomMiniature)) {
            $maMiniature = URL_THUMBS . $nomMiniature;
        } else {
            // Miniature absente -> image par défaut
            $maMiniature = DEFAULT_THUMBS;
        }
        $monHtml .= "<a href=\"" . URL_DATAS . $unFichier . "\" target=\"blank\" class=\"text-break\">";
        if (!$hideThumbs) {
            $monHtml .= "<img src=\"" . $maMiniature . "\" width=\"100\" height=\"100\" alt=\"" . $nomAffiche . "\" loading=\"lazy\"/><br />";
        }
        $monHtml .= $nomAffiche . "</a>\r\n";

        $monRetour->append($monHtml);
    }

    return $monRetour;
}

// Envoi de fichiers sur la plateforme
if (isset($_FILES[FIELD_UPLOAD])) {
    $mesFichiers = [];
    // Cas envoi simple => on convertit en array comme si envoi multiple
    if (!is_array($_FILES[FIELD_UPLOAD]["name"])) {
        $mesFichiers["name"] = [$_FILES[FIELD_UPLOAD]["name"]];
        $mesFichiers["tmp_name"] = [$_FILES[FIELD_UPLOAD]["tmp_name"]];
    } else {
        // Envoi multiple, déjà bien formaté
        $mesFichiers = $_FILES[FIELD_UPLOAD];
    }
    $nbFichiers = sizeof($mesFichiers["name"]);
    $nbUploadOk = 0;

    // Pour chaque fichier
    for ($i = 0; $i < $nbFichiers; $i++) {
        // Nettoyage du nom du fichier
        $nom = str_replace(["..", "/", "\\", "<", ">"], "", $mesFichiers["name"][$i]);

        // Vérification du type du fichier
        if (mime_content_type($mesFichiers["tmp_name"][$i]) == MIME_TYPE) {
            // Déplacement du fichier
            if (move_uploaded_file($mesFichiers["tmp_name"][$i], PATH_DATAS . $nom)) {
                $nbUploadOk++;
            } else {
                $logError .= "Erreur au déplacement du fichier " . $mesFichiers["tmp_name"][$i] . " vers " . PATH_DATAS . $nom . " !<br />";
            }
        } else {
            $logError .= "Le fichier " . $nom . " n'est pas de type " . MIME_TYPE . " !<br />";
        }
    }
    if ($nbUploadOk > 0) {
        $logSuccess .= "Envoi réussi de " . $nbUploadOk . " fichier" . ($nbUploadOk > 1 ? "s" : "") . "<br />";
    }
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
        <a href="<?= (SLOW_CONNEXION ? '?' : '?slow') ?>" class="btn btn-info">Changer d'affichage</a>
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
            <? foreach (getHtmlForFiles(SLOW_CONNEXION) as $unFichier): ?>
                <<?= (SLOW_CONNEXION ? "li" : "div") ?> class="col">
                <?= $unFichier ?>
                <<?= (SLOW_CONNEXION ? "/li" : "/div") ?>>
            <? endforeach; ?>
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