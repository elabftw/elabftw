# Writing Filters

`Zend\Filter` supplies a set of commonly needed filters, but developers will
often need to write custom filters for their particular use cases. You can do
so by writing classes that implement `Zend\Filter\FilterInterface`, which
defines a single method, `filter()`.

## Example

```php
namespace Application\Filter;

use Zend\Filter\FilterInterface;

class MyFilter implements FilterInterface
{
    public function filter($value)
    {
        // perform some transformation upon $value to arrive on $valueFiltered

        return $valueFiltered;
    }
}
```

To attach an instance of the filter defined above to a filter chain:

```php
$filterChain = new Zend\Filter\FilterChain();
$filterChain->attach(new Application\Filter\MyFilter());
```

Alternately, add it to the `FilterPluginManager`:

```php
$filterChain = new Zend\Filter\FilterChain();
$filterChain
    ->getPluginManager()
    ->setInvokableClass('myfilter', Application\Filter\MyFilter::class)
$filterChain->attachByName('myfilter');
```
