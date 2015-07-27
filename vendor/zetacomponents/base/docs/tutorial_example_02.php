<?php
require 'tutorial_autoload.php';

$data = ezcBaseFile::findRecursive(
	"/dat/dev/ezcomponents",
	array( '@src/.*_autoload.php$@' ),
	array( '@/autoload/@' )
);
var_dump( $data );

?>
