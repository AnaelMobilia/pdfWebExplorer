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

/**
 * Génère une miniature d'un fichier PDF
 * @param $source PATH du fichier source
 * @param $destination PATH de l'image destination
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
 * @param $path PATH à analyser (sans récursivité
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

// if cron...
//     genPdfThumbnail(PATH_DATAS . $unFichier, PATH_THUMBS . $unFichier . ".png"); // generates /uploads/my.jpg

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
        $monHtml .= "<a href=\"" . URL_DATAS . $unFichier . "\" target=\"blank\">";
        $monHtml .= "<img src=\"" . $maMiniature . "\" width=\"100\" height=\"100\" />";
        $monHtml .= $unFichier . "</a>\r\n";

        $monRetour->append($monHtml);
    }

    return $monRetour;
}

// Si on demande une mise à jour des miniatures
if (isset($_GET['updateCache']) || isset($argv[1])) {
    foreach (getPdfFiles(PATH_DATAS) as $unFichier) {
        $miniatureFichier = PATH_THUMBS . $unFichier . ".png";
        // Génération des miniatures manquantes///
        if (!file_exists($miniatureFichier)) {
            genPdfThumbnail(PATH_DATAS . $unFichier, $miniatureFichier);
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
    <link rel="icon" type="image/png" href="<?= DEFAULT_THUMBS?>" sizes="16x16">

    <!-- Bootstrap core CSS -->
    <link href="https://getbootstrap.com/docs/4.5/dist/css/bootstrap.min.css" rel="stylesheet"
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
        <button type="button" class="btn btn-success">Envoyer...</button>
        <form class="form-inline my-2 my-lg-0" action="#">
            <input class="form-control mr-sm-2" type="search" placeholder="Rechercher" aria-label="Rechercher"
                   id="rechercher" onkeyup="maRecherche()">
        </form>
    </nav>
</header>

<!-- Begin page content -->
<main role="main" class="flex-shrink-0">
    <div class="container" id="monContainer">
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
    function maRecherche() {
        // https://www.w3schools.com/howto/howto_js_filter_table.asp
        const valeurCherchee = document.getElementById("rechercher").value.toUpperCase();
        const mesChamps = document.getElementsByClassName("col");
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
</script>
</body>
</html>
