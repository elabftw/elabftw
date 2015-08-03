<?php
if ( ! @include __DIR__ . '/../vendor/autoload.php' )
{
    die(<<<'EOT'
You must set up the project dependencies, run the following commands:
wget http://getcomposer.org/composer.phar
php composer.phar install --dev

You can then run tests by calling:

phpunit
EOT
       );
}

function ezc_autoload( $className )
{
    if ( strpos( $className, '_' ) === false )
    {
        ezcBase::autoload( $className );
    }
}

spl_autoload_register( 'ezc_autoload' );

ezcBase::setWorkingDirectory(__DIR__);
