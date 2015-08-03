eZ Components - Base
~~~~~~~~~~~~~~~~~~~~

.. contents:: Table of Contents

Introduction
============

The Base component provides the basic functionality, such as autoloading, that
all eZ Components need to function properly. The Base component needs to be
loaded specifically. Base can also autoload external class repositories from
outside the eZ Components.

Aside from the autoload functionality, the Base component also contains a number of
generic Exception classes that all inherit from the ezcBaseException class.


Installation
============

The installation and configuration of the eZ Components environment is
described in a separate article. Please refer to the `Components Introduction`_
for instructions on installation and configuration of the eZ Components library
and the Base component.

.. _Components Introduction: ../../../../zetacomponents/documentation/install.html


Usage
=====

Debugging
---------

By default the ezcBase component's autoload mechanism will not throw an
exception when an autoload class can not be found. In some cases (during
development) it is useful to have an exception with detailed information
about which autoload files were searched for, and in which directories.
ezcBase supports an option that enables this behavior::

    <?php
    $options = new ezcBaseAutoloadOptions;
    $options->debug = true;
    ezcBase::setOptions( $options );
    ?>

**Warning**: Exceptions are ignored when they are thrown from an autoload()
handler in PHP. In order to see the exception message that is thrown when a
class can not be found, you need to catch the exception *in* the autoload()
handler. Your autoload() function could then look like::

    function __autoload( $className )
    {
        try
        {
            ezcBase::autoload( $className );
        }
        catch ( Exception $e )
        {
            echo $e->getMessage();
        }
    }

Preloading
----------

The default autoload policy of the eZ Components is to load every class
file on demand only. It is also possible to load all classes of one
component at the same time, when one of the component's classes is 
requested for the first time. You can change this behavior with the
"preload" option that is available through the ezcBaseAutoloadOptions option
class. You can turn preloading on with::

    <?php
    $options = new ezcBaseAutoloadOptions;
    $options->preload = true;
    ezcBase::setOptions( $options );
    ?>

Please note that preloading will *not* be done for Exception classes.

Adding class repositories located outside eZ Components to autoload system
--------------------------------------------------------------------------

It can be useful to add repositories of user-defined classes to the eZ
Components autoload system.  The ezcBase::addClassRepository() method can be
used to perform this task.  You need to arrange the desired external classes
in a class repository. That is, make sure that classes and corresponding
\*_autoload.php files are named and placed according to the explanations below.
After they are in the proper structure, you can call addClassRepository() with
the proper parameters before you use the external classes.
External classes will then be loaded by autoload system.

ezcBase::addClassRepository() takes two arguments:

- $basePath is the base path for the whole class repository.
- $autoloadDirPath is the path where autoload files for this repository are found. 

The paths in the autoload files are *not* relative to the package directory
as specified by the $basePath argument. In other words, class definition files will
only be searched for in the location $autoloadDirPath.

Consider the following example:

- There is a class repository stored in the directory "./repos".
- Autoload files for this repository are stored in "./repos/autoloads".
- There are two components in this repository: "Me" and "You".
- The "Me" component has the classes "erMyClass1" and "erMyClass2".
- The "You" component has the classes "erYourClass1" and "erYourClass2".

In this case, you need to create the following files in "./repos/autoloads".
Note that the prefix to _autoload.php ("my" and "your") in the filename is the
first part of the classname (excluding the lowercase classname prefix - "er").

Content of my_autoload.php:

.. include:: repos/autoloads/my_autoload.php
   :literal:

Content of your_autoload.php:

.. include:: repos/autoloads/your_autoload.php
   :literal:
 
The directory structure for the external repository is then: ::

    ./repos/autoloads/my_autoload.php
    ./repos/autoloads/your_autoload.php
    ./repos/Me/myclass1.php
    ./repos/Me/myclass2.php
    ./repos/You/yourclass1.php
    ./repos/You/yourclass2.php

To use this repository with the autoload mechanism, use the
following code:

.. include:: tutorial_example_01.php
    :literal:

The above code will output: ::

    Class 'erMyClass2'
    Class 'erYourClass1'

Lazy initialization
-------------------

Lazy initialization is a mechanism to load and configure a component, only 
when it is really used in your application. This mechanism saves time for 
parsing the classes and configuration, when the component is not used at all
during one request. The implementation in ezcBaseInit may be reused by other
applications and components, like the following example will show.

.. include:: tutorial_lazy_initialization.php
   :literal:

The example shows a random class implementing the singleton pattern, which may
be some database connection handler, or anything similar in your case. The
getInstance() method shows a typical PHP 5 implementation except the
additional line 14, which checks, if a configuration callback was provided 
earlier and configures the newly created instance. If no configuration
callback was provided, nothing will happen. The customKey is used to receive 
the right callback from ezcBaseInit and needs to be known by the user, who
wants to define a configuration callback for your class.

In line 32 the class used to configure your instance on creation is defined. 
The first parameter is the key used earlier in the getInstance method, to 
reference the right class, and the second parameter is the name of your 
configuration class.

The configuration class beginning in line 22 just needs to implement the
ezcBaseConfigurationInitializer interface, which defines one
method: configureObject(). This method will be called with the object to
configure as a single parameter. In the example, a new public property on the
customSingleton instance is created, which will be echo'd later to show the 
success of the configuration.

The configuration itself will not happen before the actual instance is created
in line 35 performing the static call on customSingleton::getInstance(). The
var_dump() in the following line shows, that the property value is set and
contains the earlier set value (int) 42.

File Operations
---------------

Finding files recursively
`````````````````````````

This example shows how to use the ezcBaseFile::findRecursive() method:

.. include:: tutorial_example_02.php
   :literal:

The code in this example searches for files in the ``/dat/dev/ezcomponents``
directory. It will only include files that match *all* patterns in the
$includeFilters array (the second parameter). Files that match *any* of the
patterns in the $excludeFilters array (the third parameter) will not be returned.

In other words, the code above searches for files in the ``dat/dev/ezcomponents``
directory, which are in the ``src/`` directory and end with ``_autoload.php``,
except for files that are in the ``/autoload/`` directory.

Removing directories recursively
````````````````````````````````

This example shows how to use the ezcBaseFile::removeRecursive() method:

.. include:: tutorial_example_03.php
   :literal:

This code simply removes the directory ``/dat/dev/ezcomponents/trash`` and all
of its files and sub-directories.

**Warning: Use this function with care, as it has the potential to erase
everything that the current user has access to.**

Overloading the callback
````````````````````````

The ezcBaseFile::findRecursive() method internally uses the
ezcBaseFile::walkRecursive() method to do the actual recursing. The callback
method ezcBaseFile::findRecursiveCallback() is then responsible for collecting
the data. In case you want to do additional things, such as printing progress,
you can either call walkRecursive() yourself with a callback function of your
choice, or overload the ezcBaseFile class and provide a new
findRecursiveCallback() method. The code below uses
ezcBaseFile::walkRecursive() directly in order to display dots for when ever it
finds a new directory:

.. include:: tutorial_example_04.php
   :literal:



..
   Local Variables:
   mode: rst
   fill-column: 79
   End: 
   vim: et syn=rst tw=79
