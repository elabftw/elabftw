<?php
require_once 'tutorial_autoload.php';

// Create a custom class implementing the singleton pattern
class customSingleton
{
    protected static $instance;

    public static function getInstance()
    {
        if ( self::$instance === null )
        {
            self::$instance = new customSingleton();
            ezcBaseInit::fetchConfig( 'customKey', self::$instance );
        }

        return self::$instance;
    }
}

// Implement your configuration class
class customSingletonConfiguration implements ezcBaseConfigurationInitializer
{
    public static function configureObject( $object )
    {
        echo "Configure customSingleton.\n";
        $object->value = 42;
    }
}

// Register for lazy initilization
ezcBaseInit::setCallback( 'customKey', 'customSingletonConfiguration' );

// Configure on first initilization
$object = customSingleton::getInstance();
var_dump( $object->value );

?>
