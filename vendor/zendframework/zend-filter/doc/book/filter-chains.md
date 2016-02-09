# Filter Chains

Often, multiple filters should be applied to some value in a particular order.
For example, a login form accepts a username that should be lowercase and
contain only alphabetic characters.

`Zend\Filter\FilterChain` provides a simple method by which filters may be
chained together. The following code illustrates how to chain together two
filters for the submitted username and fulfill the above requirements:

```php
// Create a filter chain and add filters to the chain
$filterChain = new Zend\Filter\FilterChain();
$filterChain
    ->attach(new Zend\I18n\Filter\Alpha())
    ->attach(new Zend\Filter\StringToLower());

// Filter the username
$username = $filterChain->filter($_POST['username']);
```

Filters are run in the order they are added to the filter chain. In the above
example, the username is first removed of any non-alphabetic characters, and
then any uppercase characters are converted to lowercase.

Any object that implements `Zend\Filter\FilterInterface` may be used in a
filter chain.

## Setting Filter Chain Order

For each filter added to the `FilterChain`, you can set a priority to define
the chain order. Higher values indicate higher priority (execute first), while
lower and/or negative values indicate lower priority (execute last). The default value is `1000`.

In the following example, any uppercase characters are converted to lowercase
before any non-alphabetic characters are removed.

```php
// Create a filter chain and add filters to the chain
$filterChain = new Zend\Filter\FilterChain();
$filterChain
    ->attach(new Zend\I18n\Filter\Alpha())
    ->attach(new Zend\Filter\StringToLower(), 500);
```

## Using the Plugin Manager

A `FilterPluginManager` is attached to every `FilterChain` instance. Every filter
that is used in a `FilterChain` must be known to the `FilterPluginManager`.

To add a filter to the `FilterChain`, use the `attachByName()` method. The
first parameter is the name of the filter within the `FilterPluginManager`. The
second parameter takes any options for creating the filter instance. The third
parameter is the priority.

```php
// Create a filter chain and add filters to the chain
$filterChain = new Zend\Filter\FilterChain();
$filterChain
    ->attachByName('alpha')
    ->attachByName('stringtolower', ['encoding' => 'utf-8'], 500);
```

The following example shows how to add a custom filter to the `FilterPluginManager` and the
`FilterChain`:

```php
$filterChain = new Zend\Filter\FilterChain();
$filterChain
    ->getPluginManager()
    ->setInvokableClass('myNewFilter', 'MyCustom\Filter\MyNewFilter');
$filterChain
    ->attachByName('alpha')
    ->attachByName('myNewFilter');
```

You can also add your own `FilterPluginManager` implementation:

```php
$filterChain = new Zend\Filter\FilterChain();
$filterChain->setPluginManager(new MyFilterPluginManager());
$filterChain
    ->attach(new Zend\I18n\Filter\Alpha())
    ->attach(new MyCustom\Filter\MyNewFilter());
```
