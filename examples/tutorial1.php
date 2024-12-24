<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Tutorial file
 * Description : Simple substitutions of variables
 *
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

$odf = new Odf("tutorial1.odt");

$odf->setVars('titre', 'PHP: Hypertext Preprocessor');

// The original version was French, and we keep it in French to see the accents
// be outputted correctly.
$message = "PHP (sigle de PHP: Hypertext Preprocessor), est un langage de scripts libre 
principalement utilisé pour produire des pages Web dynamiques via un serveur HTTP, mais 
pouvant également fonctionner comme n'importe quel langage interprété de façon locale, 
en exécutant les programmes en ligne de commande.";

$odf->setVars('message', $message);

// We export the file. Note that the output will appear on stdout.
// If you want to capture it, use something as " > output.odt".
$odf->exportAsAttachedFile();
