<?php

/*
* @author    Auriau Maxime <maxime.auriau@dnd.fr>
* @copyright Copyright (c) 2017 Agence Dn'D
* @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
* @link      http://www.dnd.fr/
*
*/

/* Put your API user key here */
$hash = "";
$command = "php ../../app/console oro:wsse:generate-header $hash -e prod";
exec($command,$output,$return);
echo explode('X-WSSE: UsernameToken ',$output[2])[1];