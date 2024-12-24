<?php

/**
 * Tutorial file 7.
 *
 * Description : Odf object with configuration array.
 * You need PHP 8.1 at least
 * You need Zip Extension or PclZip library
 *
 * @copyright  GPL License 2008 - Julien Pauli - Cyril PIERRE de GEYER - Anaska (http://www.anaska.com)
 * @license    http://www.gnu.org/copyleft/gpl.html  GPL License
 * @version 1.3
 */

use Odtphp\Odf;

// Make sure you have Zip extension or PclZip library loaded.
// First : include the library.
require_once '../vendor/autoload.php';

$config = array(
    'ZIP_PROXY' => \Odtphp\Zip\PhpZipProxy::class, // Make sure you have Zip extension loaded.
    'DELIMITER_LEFT' => '#', // Yan can also change delimiters.
    'DELIMITER_RIGHT' => '#'
);

$odf = new Odf("tutorial7.odt", $config);

$odf->setVars('titre', 'PHP: Hypertext Preprocessor');

$message = "PHP (sigle de PHP: Hypertext Preprocessor), est un langage de scripts libre 
principalement utilisé pour produire des pages Web dynamiques via un serveur HTTP, mais 
pouvant également fonctionner comme n'importe quel langage interprété de façon locale, 
en exécutant les programmes en ligne de commande.";

$odf->setVars('message', $message);

// We export the file. Note that the output will appear on stdout.
// If you want to capture it, use something as " > output.odt".
$odf->exportAsAttachedFile();
