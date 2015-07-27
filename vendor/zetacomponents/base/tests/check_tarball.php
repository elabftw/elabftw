<?php
// modify when needed
require '/tmp/ezcomponents-2009.1alpha1/Base/src/ezc_bootstrap.php';

$md = new ezcBaseMetaData();
echo $md->getComponentVersion( 'Base' ), "\n";
echo $md->getComponentVersion( 'Archive' ), "\n";
echo $md->getComponentVersion( 'Blah' ), "\n";
echo $md->getComponentVersion( 'Configuration' ), "\n";
echo $md->isComponentInstalled( 'Base' ) ? "true" : "false", "\n";
echo $md->isComponentInstalled( 'Archive' ) ? "true" : "false", "\n";
echo $md->isComponentInstalled( 'Blah' ) ? "true" : "false", "\n";
echo $md->isComponentInstalled( 'Configuration' ) ? "true" : "false", "\n";
?>
