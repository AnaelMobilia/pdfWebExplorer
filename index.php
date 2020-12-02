<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
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
// Répertoire & URL pour les les fichiers PDF
define('PATH_DATAS', './fichiers/');
define('URL_DATAS', (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . substr(PATH_DATAS, 1));
// Répertoire & URL pour les les miniatures des fichiers PDF
define('PATH_THUMBS', './miniatures/');
define('URL_THUMBS', (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . substr(PATH_THUMBS, 1));
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
 * @return ArrayObject code HTML
 */
function getHtmlForFiles()
{
    $monRetour = new ArrayObject();
    foreach (getPdfFiles(PATH_DATAS) as $unFichier) {
        $monHtml = "";
        $nomMiniature = $unFichier . ".png";
        if (file_exists(PATH_THUMBS . $nomMiniature)) {
            $maMiniature = URL_THUMBS . $nomMiniature;
        } else {
            // Miniature absente -> image par défaut
            $maMiniature = DEFAULT_THUMBS;
        }
        $monHtml .= "<a href=\"" . URL_DATAS . $unFichier . "\" target=\"blank\" class=\"text-break\">";
        $monHtml .= "<img src=\"" . $maMiniature . "\" width=\"100\" height=\"100\" alt=\"" . $unFichier . "\" loading=\"lazy\"/><br />";
        $monHtml .= $unFichier . "</a>\r\n";

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
if (isset($_GET['updateCache']) || isset($argv[1])) {
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
    <link href="css/bootstrap-4.5.3.min.css" rel="stylesheet"
          integrity="sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2" crossorigin="anonymous">
</head>
<body class="d-flex flex-column h-100">
<header>
    <!-- navbar -->
    <nav class="navbar navbar-light bg-light">
        <a class="navbar-brand" href="#">
            <img src="<?= DEFAULT_THUMBS ?>" width="30" height="30" alt="pdfWebExplorer">
            pdfWebExplorer
        </a>
        <!-- Envoi de fichiers PDF -->
        <form method="POST" enctype="multipart/form-data" class="form-inline border border-info">
            <input name="<?= FIELD_UPLOAD ?>[]" id="<?= FIELD_UPLOAD ?>" accept="<?= MIME_TYPE ?>" type="file"
                   class="file" multiple onchange="verifierNombreFichiers()"/>
            <input type="submit" class="btn btn-info" value="Envoyer des fichiers"/>
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
            <? foreach (getHtmlForFiles() as $unFichier): ?>
                <div class="col">
                    <?= $unFichier ?>
                </div>
            <? endforeach; ?>
        </div>
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