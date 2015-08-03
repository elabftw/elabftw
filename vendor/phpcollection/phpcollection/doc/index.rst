PHP Collection
==============
This library adds basic collections for PHP.

Collections can be seen as more specialized arrays for which certain contracts are guaranteed.

Supported Collections:

- Sequences

  - Keys: numerical, consequentially increasing, no gaps
  - Values: anything, duplicates allowed
  - Classes: ``Sequence``, ``SortedSequence``


- Maps

  - Keys: strings or objects, duplicate keys not allowed
  - Values: anything, duplicates allowed
  - Classes: ``Map``, ``ObjectMap`` (not yet implemented)


- Sets (not yet implemented)

  - Keys: not meaningful
  - Values: anything, each value must be unique (===)
  - Classes: ``Set``

General Characteristics:

- Collections are mutable (new elements may be added, existing elements may be modified or removed). Specialized
  immutable versions may be added in the future though.
- Equality comparison between elements are always performed using the shallow comparison operator (===).
- Sorting algorithms are unstable, that means the order for equal elements is undefined (the default, and only PHP behavior).


Installation
------------
PHP Collection can easily be installed via composer

.. code-block :: bash

    composer require phpcollection/phpcollection

or add it to your ``composer.json`` file.

Usage
-----
Collection classes provide a rich API.

Sequences
~~~~~~~~~

.. code-block :: php

    // Read Operations
    $seq = new Sequence([0, 2, 3, 2]);
    $seq->get(2); // int(3)
    $seq->all(); // [0, 2, 3, 2]

    $seq->first(); // Some(0)
    $seq->last(); // Some(2)

    // Write Operations
    $seq = new Sequence([1, 5]);
    $seq->get(0); // int(1)
    $seq->update(0, 4);
    $seq->get(0); // int(4)
    $seq->remove(0);
    $seq->get(0); // int(5)

    $seq = new Sequence([1, 4]);
    $seq->add(2);
    $seq->all(); // [1, 4, 2]
    $seq->addAll(array(4, 5, 2));
    $seq->all(); // [1, 4, 2, 4, 5, 2]

    // Sort
    $seq = new Sequence([0, 5, 4, 2]);
    $seq->sortWith(function($a, $b) { return $a - $b; });
    $seq->all(); // [0, 2, 4, 5]

Maps
~~~~

.. code-block :: php

    // Read Operations
    $map = new Map(['foo' => 'bar', 'baz' => 'boo']);
    $map->get('foo'); // Some('bar')
    $map->get('foo')->get(); // string('bar')
    $map->keys(); // ['foo', 'baz']
    $map->values(); // ['bar', 'boo']
    iterator_to_array($map); // ['foo' => 'bar', 'baz' => 'boo']

    $map->first()->get(); // ['foo', 'bar']
    $map->last()->get(); // ['baz', 'boo']

    // Write Operations
    $map = new Map();
    $map->set('foo', 'bar');
    $map->setAll(array('bar' => 'baz', 'baz' => 'boo'));
    $map->remove('foo');

    // Sort
    $map->sortWith('strcmp');

License
-------

The code is released under the business-friendly `Apache2 license`_.

Documentation is subject to the `Attribution-NonCommercial-NoDerivs 3.0 Unported
license`_.

.. _Apache2 license: http://www.apache.org/licenses/LICENSE-2.0.html
.. _Attribution-NonCommercial-NoDerivs 3.0 Unported license: http://creativecommons.org/licenses/by-nc-nd/3.0/

