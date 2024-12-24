//////////////////////////////////////////////////////////////
// ODT Letter Generation Based on a Form                    //
//                                                          // 
// Laurent Lefèvre - http://www.cybermonde.org              //
// 															//
//////////////////////////////////////////////////////////////

// This is a left over example from cybermonde/odtphp, I left it
// partially in French.

use Odtphp\Odf;

// Make sure you have Zip extension or PclZip library loaded.
// First : include the library.
require_once '../vendor/autoload.php';

// base model
$odf = new Odf("form_template.odt");
// if nothing has been posted, show the form
if (!$_POST) {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<title>Form to ODT</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<style type="text/css">
	</style>
	</head>
<body>
                <form id="myform" method="POST" action="formulaire.php">
                <p>
                    <input type="radio" id="invite" name="invite" value="Mrs" checked />Mrs
                    <input type="radio" id="invite" name="invite" value="Mr" />Mr.
                </p>
                <p>
                    <label for="firstname">First Name:</label> 
                    <input type="text" id="firstname" name="firstname" size="40" value=""/>
                </p>
                <p>
                    <label for="lastname">Last Name:</label> 
                    <input type="text" id="lastname" name="lastname" size="40" value=""/>
                </p>
                <p>
                    <label for="total">Total:</label> 
                    <input type="text" id="total" name="total" size="40" value=""/>
                </p>
                <p>
                <input type="submit" name="submit" />
            </p>
        </form>
</body>
</html>
<?php
// otherwise send the generated file
} else {
// date of the day
$odf->setVars('cyb_date', date("d/m/Y"));
// form data
$odf->setVars('cyb_invite', $_POST["invite"]);
// Handle accented characters using this HTML entity decode trick.
$odf->setVars('cyb_firstname', html_entity_decode(htmlentities($_POST["firstname"],ENT_NOQUOTES, "utf-8")));
$odf->setVars('cyb_lastname', html_entity_decode(htmlentities($_POST["lastname"],ENT_NOQUOTES, "utf-8")));
$odf->setVars('cyb_total', $_POST["total"]);

$odf->exportAsAttachedFile();
}
