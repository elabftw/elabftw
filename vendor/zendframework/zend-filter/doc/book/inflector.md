# String Inflection

`Zend\Filter\Inflector` is a general purpose tool for rules-based inflection of
strings to a given target.

As an example, you may find you need to transform MixedCase or camelCasedWords
into a path; for readability, OS policies, or other reasons, you also need to
lower case this; and finally, you want to separate the words using a dash
(`-`). An inflector can do this for you.

`Zend\Filter\Inflector` implements `Zend\Filter\FilterInterface`; you perform
inflection by calling `filter()` on the object instance.

## Transforming MixedCase and camelCaseText to another format

```php
$inflector = new Zend\Filter\Inflector('pages/:page.:suffix');
$inflector->setRules([
    ':page'  => ['Word\CamelCaseToDash', 'StringToLower'],
    'suffix' => 'html',
]);

$string   = 'camelCasedWords';
$filtered = $inflector->filter(['page' => $string]);
// pages/camel-cased-words.html

$string   = 'this_is_not_camel_cased';
$filtered = $inflector->filter(['page' => $string]);
// pages/this_is_not_camel_cased.html
```

## Operation

An inflector requires a **target** and one or more **rules**. A target is
basically a string that defines placeholders for variables you wish to
substitute. These are specified by prefixing with a `:`: `:script`.

When calling `filter()`, you then pass in an array of key and value pairs
corresponding to the variables in the target.

Each variable in the target can have zero or more rules associated with them.
Rules may be either **static** or refer to a zend-filter class. Static rules
will replace with the text provided.  Otherwise, a class matching the rule
provided will be used to inflect the text. Classes are typically specified
using a short name indicating the filter name stripped of any common prefix.

As an example, you can use any zend-filter concrete implementations; however,
instead of referring to them as `Zend\I18n\Filter\Alpha` or
`Zend\Filter\StringToLower`, you'd specify only `Alpha` or `StringToLower`.

### Using Custom Filters

`Zend\Filter\Inflector` uses `Zend\Filter\FilterPluginManager` to manage
loading filters to use with inflection. By default, filters registered with
`Zend\Filter\FilterPluginManager` are available. To access filters with that
prefix but which occur deeper in the hierarchy, such as the various `Word`
filters, simply strip off the `Zend\Filter` prefix:

```php
// use Zend\Filter\Word\CamelCaseToDash as a rule
$inflector->addRules(['script' => 'Word\CamelCaseToDash']);
```

To use custom filters, you have two choices: reference them by fully qualified
class name (e.g., `My\Custom\Filter\Mungify`), or manipulate the composed
`FilterPluginManager` instance.

```php
$filters = $inflector->getPluginManager();
$filters->addInvokableClass('mungify', 'My\Custom\Filter\Mungify');
```

### Setting the Inflector Target

The inflector target is a string with some placeholders for variables.
Placeholders take the form of an identifier, a colon (`:`) by default, followed
by a variable name: `:script`, `:path`, etc. The `filter()` method looks for
the identifier followed by the variable name being replaced.

You can change the identifier using the `setTargetReplacementIdentifier()`
method, or passing it as the fourth argument to the constructor:

```php
// Via constructor:
$inflector = new Zend\Filter\Inflector('#foo/#bar.#sfx', array(), null, '#');

// Via accessor:
$inflector->setTargetReplacementIdentifier('#');
```

Typically, you will set the target via the constructor. However, you may want
to re-set the target later. `setTarget()` can be used for this purpose:

```php
$inflector->setTarget('layouts/:script.phtml');
```

Additionally, you may wish to have a class member for your class that you can
use to keep the inflector target updated &mdash; without needing to directly update
the target each time (thus saving on method calls). `setTargetReference()`
allows you to do this:

```php
class Foo
{
    /**
     * @var string Inflector target
     */
    protected $target = 'foo/:bar/:baz.:suffix';

    /**
     * Constructor
     * @return void
     */
    public function __construct()
    {
        $this->inflector = new Zend\Filter\Inflector();
        $this->inflector->setTargetReference($this->target);
    }

    /**
     * Set target; updates target in inflector
     *
     * @param  string $target
     * @return Foo
     */
    public function setTarget($target)
    {
        $this->target = $target;
        return $this;
    }
}
```

## Inflection Rules

As mentioned in the introduction, there are two types of rules: static and filter-based.

> ### Order is important
>
> It is important to note that regardless of the method in which you add rules
> to the inflector, either one-by-one, or all-at-once; the order is very
> important. More specific names, or names that might contain other rule names,
> must be added before least specific names. For example, assuming the two rule
> names `moduleDir` and `module`, the `moduleDir` rule should appear before
> module since `module` is contained within `moduleDir`. If `module` were added
> before `moduleDir`, `module` will match part of `moduleDir` and process it
> leaving `Dir` inside of the target uninflected.

### Static Rules

Static rules do simple string substitution; use them when you have a segment in
the target that will typically be static, but which you want to allow the
developer to modify. Use the `setStaticRule()` method to set or modify the
rule:

```php
$inflector = new Zend\Filter\Inflector(':script.:suffix');
$inflector->setStaticRule('suffix', 'phtml');

// change it later:
$inflector->setStaticRule('suffix', 'php');
```

Much like the target itself, you can also bind a static rule to a reference,
allowing you to update a single variable instead of require a method call; this
is often useful when your class uses an inflector internally, and you don't
want your users to need to fetch the inflector in order to update it. The
`setStaticRuleReference()` method is used to accomplish this:

```php
class Foo
{
    /**
     * @var string Suffix
     */
    private $suffix = 'phtml';

    /**
     * Constructor
     * @return void
     */
    public function construct()
    {
        $this->inflector = new Zend\Filter\Inflector(':script.:suffix');
        $this->inflector->setStaticRuleReference('suffix', $this->suffix);
    }

    /**
     * Set suffix; updates suffix static rule in inflector
     *
     * @param  string $suffix
     * @return Foo
     */
    public function setSuffix($suffix)
    {
        $this->suffix = $suffix;
        return $this;
    }
}
```

### Filter-Based Inflector Rules

`Zend\Filter` filters may be used as inflector rules as well. Just like static
rules, these are bound to a target variable; unlike static rules, you may
define multiple filters to use when inflecting. These filters are processed in
order, so be careful to register them in an order that makes sense for the data
you receive.

Rules may be added using `setFilterRule()` (which overwrites any previous rules
for that variable) or `addFilterRule()` (which appends the new rule to any
existing rule for that variable). Filters are specified in one of the following
ways:

- **String**. The string may be a filter class name, or a class name segment
  minus any prefix set in the inflector's plugin loader (by default, minus the
  '`Zend\Filter`' prefix).
- **Filter object**. Any object instance implementing
  `Zend\Filter\FilterInterface` may be passed as a filter.
- **Array**. An array of one or more strings or filter objects as defined above.

```php
$inflector = new Zend\Filter\Inflector(':script.:suffix');

// Set rule to use Zend\Filter\Word\CamelCaseToDash filter
$inflector->setFilterRule('script', 'Word\CamelCaseToDash');

// Add rule to lowercase string
$inflector->addFilterRule('script', new Zend\Filter\StringToLower());

// Set rules en-masse
$inflector->setFilterRule('script', [
    'Word\CamelCaseToDash',
    new Zend\Filter\StringToLower()
]);
```

## Setting Many Rules At Once

Typically, it's easier to set many rules at once than to configure a single
variable and its inflection rules one at a time. `Zend\Filter\Inflector`'s
`addRules()` and `setRules()` methods allow this.

Each method takes an array of variable and rule pairs, where the rule may be
whatever the type of rule accepts (string, filter object, or array). Variable
names accept a special notation to allow setting static rules and filter rules,
according to the following notation:

- **`:` prefix**: filter rules.
- **No prefix**: static rule.

As an example:

```php
// Could also use setRules() with this notation:
$inflector->addRules([
    // Filter rules:
    ':controller' => ['Word\CamelCaseToUnderscore','StringToLower'],
    ':action'     => ['Word\CamelCaseToUnderscore','StringToLower'],

    // Static rule:
    'suffix'      => 'phtml',
]);
```

## Utility Methods

`Zend\Filter\Inflector` has a number of utility methods for retrieving and
setting the plugin loader, manipulating and retrieving rules, and controlling
if and when exceptions are thrown.

- `setPluginManager()` can be used when you have configured your own
  `Zend\Filter\FilterPluginManager` instance and wish to use it with
  `Zend\Filter\Inflector`; `getPluginManager()` retrieves the currently set
  one.
- `setThrowTargetExceptionsOn()` can be used to control whether or not
  `filter()` throws an exception when a given replacement identifier passed to
  it is not found in the target. By default, no exceptions are thrown.
  `isThrowTargetExceptionsOn()` will tell you what the current value is.
- `getRules($spec = null)` can be used to retrieve all registered rules for all
  variables, or just the rules for a single variable.
- `getRule($spec, $index)` fetches a single rule for a given variable; this can
  be useful for fetching a specific filter rule for a variable that has a
  filter chain. `$index` must be passed.
- `clearRules()` will clear all currently registered rules.

## Using a Traversable or an array

You can use a `Traversable` or an array to set rules and other object state in
your inflectors, by passing either type to either the constructor or the
`setOptions()` method. The following settings may be specified:

- `target` specifies the inflection target.
- `pluginManager` specifies the `Zend\Filter\FilterPluginManager` instance or
  extension to use for obtaining plugins; alternately, you may specify a class
  name of a class that extends the `FilterPluginManager`.
- `throwTargetExceptionsOn` should be a boolean indicating whether or not to
  throw exceptions when a replacement identifier is still present after
  inflection.
- `targetReplacementIdentifier` specifies the character to use when identifying
  replacement variables in the target string.
- `rules` specifies an array of inflection rules; it should consist of keys
  that specify either values or arrays of values, consistent with `addRules()`.

As examples:

```php
// $options implements Traversable:

// With the constructor:
$inflector = new Zend\Filter\Inflector($options);

// Or with setOptions():
$inflector = new Zend\Filter\Inflector();
$inflector->setOptions($options);
```
