# Advanced Usage

## JSON Objects

When encoding PHP objects as JSON, all public properties of that object will be
encoded in a JSON object.

JSON does not allow object references, so care should be taken not to encode
objects with recursive references. If you have issues with recursion,
`Zend\Json\Json::encode()` and `Zend\Json\Encoder::encode()` each allow an
optional second parameter to check for recursion; if an object is serialized
twice, an exception will be thrown.

Decoding JSON objects poses additional difficulty, however, since JavaScript
objects correspond most closely to PHP's associative array. Some suggest that a
class identifier should be passed, and an object instance of that class should
be created and populated with the key/value pairs of the JSON object; others
feel this could pose a substantial security risk.

By default, `Zend\Json\Json` will decode JSON objects as `stdClass` objects.
However, if you desire an associative array returned, you can request it using
the second argument to `decode()`:

```php
// Decode JSON objects as PHP array
$phpNative = Zend\Json\Json::decode($encodedValue, Zend\Json\Json::TYPE_ARRAY);
```

Any objects thus decoded are returned as associative arrays with keys and values
corresponding to the key/value pairs in the JSON notation.

The recommendation of Zend Framework is that the individual developer should
decide how to decode JSON objects. If an object of a specified type should be
created, it can be created in the developer code and populated with the values
decoded using zend-json.

## Encoding PHP objects

If you are encoding PHP objects, the default encoding mechanism can only
access public properties of these objects. When a method `toJson()` is
implemented on an object to encode, `Zend\Json\Json` calls this method and
expects the object to return a JSON representation of its internal state.

`Zend\Json\Json` can encode PHP objects recursively but does not do so by
default. This can be enabled by passing `true` as the second argument to
`Zend\Json\Json::encode()`.

```php
// Encode PHP object recursively
$jsonObject = Zend\Json\Json::encode($data, true);
```

When doing recursive encoding of objects, as JSON does not support cycles, a
`Zend\Json\Exception\RecursionException` will be thrown. If you wish, you can
silence these exceptions by passing the `silenceCyclicalExceptions` option:

```php
$jsonObject = Zend\Json\Json::encode(
    $data,
    true,
    ['silenceCyclicalExceptions' => true]
);
```

## Internal Encoder/Decoder

`Zend\Json` has two different modes depending if ext/json is enabled in your PHP
installation or not. If `ext/json` is installed, zend-json will use the
`json_encode()` and `json_decode()` functions for encoding and decoding JSON. If
`ext/json` is not installed, a Zend Framework implementation in PHP code is used
for en/decoding. This is considerably slower than using the PHP extension, but
behaves exactly the same.

Sometimes you might want to use the zend-json encoder/decoder even if you have
`ext/json` installed. You can achieve this by calling:

```php
Zend\Json\Json::$useBuiltinEncoderDecoder = true;
```

## JSON Expressions

JavaScript makes heavy use of anonymous function callbacks, which can be saved
within JSON object variables. They only work if not returned inside double
quotes, which zend-json implements by default. With the Expression support for
zend-json, you can encode JSON objects with valid JavaScript callbacks.
This works when either `json_encode()` or the internal encoder is used.

A JavaScript callback is represented using the `Zend\Json\Expr` object. It
implements the value object pattern and is immutable. You can set the JavaScript
expression as the first constructor argument. By default
`Zend\Json\Json::encode()` does not encode JavaScript callbacks; you have to
pass the option `enableJsonExprFinder` and set it to `TRUE` when calling the
`encode()` method. If enabled, the expression support works for all nested
expressions in large object structures.

As an example:

```php
$data = [
    'onClick' => new Zend\Json\Expr(
        'function() {'
        . 'alert("I am a valid JavaScript callback created by Zend\\Json");
        . '}'
    ),
    'other' => 'no expression',
];
$jsonObjectWithExpression = Zend\Json\Json::encode(
    $data,
    false,
    ['enableJsonExprFinder' => true]
);
```
