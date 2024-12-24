<?php

/**
 * Tutorial file 2.
 *
 * Description : Adding a single image to the document
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

$odf = new Odf("tutorial2.odt");

$odf->setVars('titre','Anaska formation');

$message = "Anaska, leader français de la formation informatique sur les technologies 
Open Source, propose un catalogue de plus de 50 formations, dont certaines préparent 
aux certifications Linux, MySQL, PHP et PostgreSQL.";

$odf->setVars('message', $message);

$odf->setImage('image', './images/anaska.jpg');

// We export the file. Note that the output will appear on stdout.
// If you want to capture it, use something as " > output.odt".
$odf->exportAsAttachedFile();
