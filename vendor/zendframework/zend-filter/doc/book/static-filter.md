# Using the StaticFilter

If it is inconvenient to load a given filter class and create an instance of
the filter, you can use `StaticFilter` with it's method `execute()` as an
alternative invocation style. The first argument of this method is a data input
value that you would pass to the `filter()` method. The second argument is a
string, which corresponds to the basename of the filter class, relative to the
`Zend\Filter` namespace. The `execute()` method automatically loads the class,
creates an instance, and applies the `filter()` method to the data input.

```php
echo StaticFilter::execute('&', 'HtmlEntities');
```

You can also pass an array of constructor arguments, if they are needed for the filter class:

```php
echo StaticFilter::execute(
    '"',
    'HtmlEntities',
    ['quotestyle' => ENT_QUOTES]
);
```

The static usage can be convenient for invoking a filter ad hoc, but if you
have the need to run a filter for multiple inputs, it's more efficient to
create an instance of the filter and invoke it.  instance of the filter object
and calling its `filter()` method.

Additionally, [filter chains](filter-chains.md) allow you to instantiate and run multiple filters
on demand to process sets of input data.

## Using custom filters

You can set and receive the `FilterPluginManager` for the `StaticFilter` to
amend the standard filter classes:

```php
$pluginManager = StaticFilter::getPluginManager()
    ->setInvokableClass('myNewFilter', 'MyCustom\Filter\MyNewFilter');

StaticFilter::setPluginManager(new MyFilterPluginManager());
```

This is useful when adding custom filters to be used by the `StaticFilter`.
