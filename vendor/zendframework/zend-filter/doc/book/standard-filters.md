# Standard Filter Classes

zend-filter comes with a standard set of filters, available for immediate use.

## Alnum

The `Alnum` filter can be used to return only alphabetic characters and digits
in the unicode "letter" and "number" categories, respectively. All other
characters are suppressed.

This filter is part of the zend-i18n package; you will need to include that
package in your application to use it.

### Supported Options

The following options are supported for `Alnum`:

```php
Alnum([ boolean $allowWhiteSpace [, string $locale ]])
```

- `$allowWhiteSpace`: If set to true, then whitespace characters are allowed.
  Otherwise they are suppressed. Default is `false` (whitespace is not allowed).  
  Methods for getting/setting the `allowWhiteSpace` option are also available:
  `getAllowWhiteSpace()` and `setAllowWhiteSpace()`.

- `$locale`: The locale string used in identifying the characters to filter
  (locale name, e.g. `en_US`). If unset, it will use the default locale
  (`Locale::getDefault()`). Methods for getting/setting the locale are also
  available: `getLocale()` and `setLocale()`.

### Basic Usage

```php
// Default settings, deny whitespace
$filter = new \Zend\I18n\Filter\Alnum();
echo $filter->filter('This is (my) content: 123');
// Returns 'Thisismycontent123'

// First param in constructor is $allowWhiteSpace
$filter = new \Zend\I18n\Filter\Alnum(true);
echo $filter->filter('This is (my) content: 123');
// Returns 'This is my content 123'
```

> #### Supported languages
>
> `Alnum` works on almost all languages, except: Chinese, Japanese and Korean.
> Within these languages, the english alphabet is used instead of the characters
> from these languages. The language itself is detected using the `Locale`
> class.

## Alpha

The `Alpha` filter can be used to return only alphabetic characters in the unicode "letter"
category. All other characters are suppressed.

This filter is part of the zend-i18n package; you will need to include that
package in your application to use it.

### Supported Options

The following options are supported for `Alpha`:

```php
Alpha([ boolean $allowWhiteSpace [, string $locale ]])
```

- `$allowWhiteSpace`: If set to true then whitespace characters are allowed.
  Otherwise they are suppressed. Default is `false` (whitespace is not allowed).
  Methods for getting/setting the allowWhiteSpace option are also available:
  `getAllowWhiteSpace()` and `setAllowWhiteSpace()`.

- `$locale`: The locale string used in identifying the characters to filter
  (locale name, e.g. `en_US`). If unset, it will use the default locale
  (`Locale::getDefault()`).  Methods for getting/setting the locale are also
  available: `getLocale()` and `setLocale()`.

### Basic Usage

```php
// Default settings, deny whitespace
$filter = new \Zend\I18n\Filter\Alpha();
echo $filter->filter('This is (my) content: 123');
// Returns 'Thisismycontent'

// Allow whitespace
$filter = new \Zend\I18n\Filter\Alpha(true);
echo $filter->filter('This is (my) content: 123');
// Returns 'This is my content '
```

> #### Supported languages
>
> `Alpha` works on almost all languages, except: Chinese, Japanese and Korean.
> Within these languages, the english alphabet is used instead of the characters
> from these languages. The language itself is detected using the `Locale`
> class.

## BaseName

`Zend\Filter\BaseName` allows you to filter a string which contains the path to
a file, and it will return the base name of this file.

### Supported Options

There are no additional options for `Zend\Filter\BaseName`.

### Basic Usage

```php
$filter = new Zend\Filter\BaseName();

print $filter->filter('/vol/tmp/filename');
```

This will return 'filename'.

```php
$filter = new Zend\Filter\BaseName();

print $filter->filter('/vol/tmp/filename.txt');
```

This will return '`filename.txt`'.

## Blacklist

This filter will return `null` if the value being filtered is present in the filter's list of
values. If the value is not present, it will return that value.

For the opposite functionality, see the [`Whitelist` filter](#whitelist).

### Supported Options

The following options are supported for `Zend\Filter\Blacklist`:

- `strict`: Uses strict mode when comparing; passed to `in_array()`'s third argument.
- `list`: An array of forbidden values.

### Basic Usage

```php
$blacklist = new \Zend\Filter\Blacklist([
    'list' => ['forbidden-1', 'forbidden-2']
]);
echo $blacklist->filter('forbidden-1'); // => null
echo $blacklist->filter('allowed');     // => 'allowed'
```

## Boolean

This filter changes a given input to be a `BOOLEAN` value. This is often useful when working with
databases or when processing form values.

### Supported Options

The following options are supported for `Zend\Filter\Boolean`:

- `casting`: When this option is set to `TRUE`, then any given input will be
  cast to boolean.  This option defaults to `TRUE`.
- `translations`: This option sets the translations which will be used to detect localized input.
- `type`: The `type` option sets the boolean type which should be used. Read
  the following for details.

### Default Behavior

By default, this filter works by casting the input to a `BOOLEAN` value; in other words, it operates
in a similar fashion to calling `(boolean) $value`.

```php
$filter = new Zend\Filter\Boolean();
$value  = '';
$result = $filter->filter($value);
// returns false
```

This means that without providing any configuration, `Zend\Filter\Boolean` accepts all input types
and returns a `BOOLEAN` just as you would get by type casting to `BOOLEAN`.

### Changing the Default Behavior

Sometimes, casting with `(boolean)` will not suffice. `Zend\Filter\Boolean`
allows you to configure specific types to convert, as well as which to omit.

The following types can be handled:

- `boolean`: Returns a boolean value as is.
- `integer`: Converts an integer `0` value to `FALSE`.
- `float`: Converts a float `0.0` value to `FALSE`.
- `string`: Converts an empty string `''` to `FALSE`.
- `zero`: Converts a string containing the single character zero (`'0'`) to `FALSE`.
- `empty_array`: Converts an empty `array` to `FALSE`.
- `null`: Converts a `NULL` value to `FALSE`.
- `php`: Converts values according to PHP when casting them to `BOOLEAN`.
- `false_string`: Converts a string containing the word "false" to a boolean `FALSE`.
- `yes`: Converts a localized string which contains the word "no" to `FALSE`.
- `all`: Converts all above types to `BOOLEAN`.

All other given values will return `TRUE` by default.

There are several ways to select which of the above types are filtered. You can
give one or multiple types and add them, you can give an array, you can use
constants, or you can give a textual string.  See the following examples:

```php
// converts 0 to false
$filter = new Zend\Filter\Boolean(Zend\Filter\Boolean::TYPE_INTEGER);

// converts 0 and '0' to false
$filter = new Zend\Filter\Boolean(
    Zend\Filter\Boolean::TYPE_INTEGER + Zend\Filter\Boolean::TYPE_ZERO_STRING
);

// converts 0 and '0' to false
$filter = new Zend\Filter\Boolean([
    'type' => [
        Zend\Filter\Boolean::TYPE_INTEGER,
        Zend\Filter\Boolean::TYPE_ZERO_STRING,
    ],
]);

// converts 0 and '0' to false
$filter = new Zend\Filter\Boolean([
    'type' => [
        'integer',
        'zero',
    ],
]);
```

You can also give an instance of `Zend\Config\Config` to set the desired types.
To set types after instantiation, use the `setType()` method.

### Localized Booleans

As mentioned previously, `Zend\Filter\Boolean` can also recognise localized "yes" and "no" strings.
This means that you can ask your customer in a form for "yes" or "no" within his native language and
`Zend\Filter\Boolean` will convert the response to the appropriate boolean value.

To set the translation and the corresponding value, you can use the `translations` option or the
method `setTranslations`.

```php
$filter = new Zend\Filter\Boolean([
    'type'         => Zend\Filter\Boolean::TYPE_LOCALIZED,
    'translations' => [
        'ja'   => true,
        'nein' => false,
        'yes'  => true,
        'no'   => false,
    ],
]);

// returns false
$result = $filter->filter('nein');

// returns true
$result = $filter->filter('yes');
```

### Disable Casting

Sometimes it is necessary to recognise only `TRUE` or `FALSE` and return all
other values without changes. `Zend\Filter\Boolean` allows you to do this by
setting the `casting` option to `FALSE`.

In this case `Zend\Filter\Boolean` will work as described in the following
table, which shows which values return `TRUE` or `FALSE`. All other given values
are returned without change when `casting` is set to `FALSE`

Type | True | False
---- | ---- | -----
`Zend\Filter\Boolean::TYPE_BOOLEAN` | `TRUE` | `FALSE`
`Zend\Filter\Boolean::TYPE_EMPTY_ARRAY` | `array()` | 
`Zend\Filter\Boolean::TYPE_FALSE_STRING` | `"false"` (case insensitive) | `"true"` (case insensitive)
`Zend\Filter\Boolean::TYPE_FLOAT` | `0.0` | `1.0`
`Zend\Filter\Boolean::TYPE_INTEGER` | `0` | `1`
`Zend\Filter\Boolean::TYPE_LOCALIZED` | localized `"yes"` (case insensitive) | localized `"no"` (case insensitive)
`Zend\Filter\Boolean::TYPE_NULL` | `NULL` | 
`Zend\Filter\Boolean::TYPE_STRING` | `""` | 
`Zend\Filter\Boolean::TYPE_ZERO_STRING` | `"0"` | `"1"`

The following example shows the behaviour when changing the `casting` option:

```php
$filter = new Zend\Filter\Boolean([
    'type'    => Zend\Filter\Boolean::TYPE_ALL,
    'casting' => false,
]);

// returns false
$result = $filter->filter(0);

// returns true
$result = $filter->filter(1);

// returns the value
$result = $filter->filter(2);
```

## Callback

This filter allows you to use own methods in conjunction with `Zend\Filter`. You
don't have to create a new filter when you already have a method which does the
job.

### Supported Options

The following options are supported for `Zend\Filter\Callback`:

- `callback`: This sets the callback which should be used.
- `callback_params`: This property sets the options which are used when the
  callback is processed.

### Basic Usage

The usage of this filter is quite simple. In this example, we want to create a
filter which reverses a string:

```php
$filter = new Zend\Filter\Callback('strrev');

print $filter->filter('Hello!');
// returns "!olleH"
```

As you can see it's really simple to use a callback to define custom filters. It
is also possible to use a method, which is defined within a class, by giving an
array as the callback:

```php
class MyClass
{
    public static function reverse($param);
}

// The filter definition
$filter = new Zend\Filter\Callback(array('MyClass', 'reverse'));
print $filter->filter('Hello!');
```

To get the actual set callback use `getCallback()` and to set another callback
use `setCallback()`.

> #### Possible exceptions
>
> You should note that defining a callback method which can not be called will
> raise an exception.

### Default Parameters Within a Callback

It is also possible to define default parameters, which are given to the called
method as an array when the filter is executed. This array will be concatenated
with the value which will be filtered.

```php
$filter = new Zend\Filter\Callback([
    'callback' => 'MyMethod',
    'options'  => ['key' => 'param1', 'key2' => 'param2']
]);
$filter->filter(['value' => 'Hello']);
```

Calling the above method definition manually would look like this:

```php
$value = MyMethod('Hello', 'param1', 'param2');
```

## Compress and Decompress

These two filters are capable of compressing and decompressing strings, files, and directories.

### Supported Options

The following options are supported for `Zend\Filter\Compress` and `Zend\Filter\Decompress`:

- `adapter`: The compression adapter which should be used. It defaults to `Gz`.
- `options`: Additional options which are given to the adapter at initiation.
  Each adapter supports its own options.

### Supported Compression Adapters

The following compression formats are supported by their own adapter:

- **Bz2**
- **Gz**
- **Lzf**
- **Rar**
- **Tar**
- **Zip**

Each compression format has different capabilities as described below. All
compression filters may be used in approximately the same ways, and differ
primarily in the options available and the type of compression they offer (both
algorithmically as well as string vs. file vs. directory)

### Generic Handling

To create a compression filter, you need to select the compression format you want to use. The
following example takes the **Bz2** adapter. Details for all other adapters are described after
this section.

The two filters are basically identical, in that they utilize the same backends.
`Zend\Filter\Compress` should be used when you wish to compress items, and `Zend\Filter\Decompress`
should be used when you wish to decompress items.

For instance, if we want to compress a string, we have to initialize `Zend\Filter\Compress` and
indicate the desired adapter:

```php
$filter = new Zend\Filter\Compress('Bz2');
```

To use a different adapter, you simply specify it to the constructor.

You may also provide an array of options or a `Traversable` object. If you do,
provide minimally the key "adapter", and then either the key "options" or
"adapterOptions", both of which should be an array of options to provide to the
adapter on instantiation.

```php
$filter = new Zend\Filter\Compress([
    'adapter' => 'Bz2',
    'options' => [
        'blocksize' => 8,
    ],
]);
```

> #### Default compression Adapter
>
> When no compression adapter is given, then the **Gz** adapter will be used.

Decompression is essentially the same usage; we simply use the `Decompress`
filter instead:

```php
$filter = new Zend\Filter\Decompress('Bz2');
```

To get the compressed string, we have to give the original string. The filtered value is the
compressed version of the original string.

```php
$filter     = new Zend\Filter\Compress('Bz2');
$compressed = $filter->filter('Uncompressed string');
// Returns the compressed string
```

Decompression works in reverse, accepting the compressed string, and returning
the original:

```php
$filter     = new Zend\Filter\Decompress('Bz2');
$compressed = $filter->filter('Compressed string');
// Returns the original, uncompressed string
```

> #### Note on string compression
>
> Not all adapters support string compression. Compression formats like **Rar**
> can only handle files and directories. For details, consult the section for
> the adapter you wish to use.

### Creating an Archive

Creating an archive file works almost the same as compressing a string. However, in this case we
need an additional parameter which holds the name of the archive we want to create.

```php
$filter = new Zend\Filter\Compress([
    'adapter' => 'Bz2',
    'options' => [
        'archive' => 'filename.bz2',
    ],
]);
$compressed = $filter->filter('Uncompressed string');
// Returns true on success, and creates the archive file
```

In the above example, the uncompressed string is compressed, and is then written
into the given archive file.

> #### Existing archives will be overwritten
>
> The content of any existing file will be overwritten when the given filename
> of the archive already exists.

When you want to compress a file, then you must give the name of the file with its path:

```php
$filter = new Zend\Filter\Compress([
    'adapter' => 'Bz2',
    'options' => [
        'archive' => 'filename.bz2'
    ],
]);
$compressed = $filter->filter('C:\temp\compressme.txt');
// Returns true on success and creates the archive file
```

You may also specify a directory instead of a filename. In this case the whole
directory with all its files and subdirectories will be compressed into the
archive:

```php
$filter = new Zend\Filter\Compress([
    'adapter' => 'Bz2',
    'options' => [
        'archive' => 'filename.bz2'
    ],
]);
$compressed = $filter->filter('C:\temp\somedir');
// Returns true on success and creates the archive file
```

> #### Do not compress large or base directories
>
> You should never compress large or base directories like a complete partition.
> Compressing a complete partition is a very time consuming task which can lead
> to massive problems on your server when there is not enough space or your
> script takes too much time.

### Decompressing an Archive

Decompressing an archive file works almost like compressing it. You must specify either the
`archive` parameter, or give the filename of the archive when you decompress the file.

```php
$filter = new Zend\Filter\Decompress('Bz2');
$decompressed = $filter->filter('filename.bz2');
// Returns true on success and decompresses the archive file
```

Some adapters support decompressing the archive into another subdirectory. In
this case you can set the `target` parameter:

```php
$filter = new Zend\Filter\Decompress([
    'adapter' => 'Zip',
    'options' => [
        'target' => 'C:\temp',
    ]
]);
$decompressed = $filter->filter('filename.zip');
// Returns true on success, and decompresses the archive file
// into the given target directory
```

> #### Directories to extract to must exist
>
> When you want to decompress an archive into a directory, then the target
> directory must exist.

### Bz2 Adapter

The Bz2 Adapter can compress and decompress:

- Strings
- Files
- Directories

This adapter makes use of PHP's Bz2 extension.

To customize compression, this adapter supports the following options:

- `archive`: This parameter sets the archive file which should be used or created.
- `blocksize`: This parameter sets the blocksize to use. It can be from '0' to
  '9'. The default value is '4'.

All options can be set at instantiation or by using a related method; for example, the related
methods for 'blocksize' are `getBlocksize()` and `setBlocksize()`. You can also use the
`setOptions()` method, which accepts an array of all options.

### Gz Adapter

The Gz Adapter can compress and decompress:

- Strings
- Files
- Directories

This adapter makes use of PHP's Zlib extension.

To customize the compression this adapter supports the following options:

- `archive`: This parameter sets the archive file which should be used or created.
- `level`: This compression level to use. It can be from '0' to '9'. The default
  value is '9'.
- `mode`: There are two supported modes. `compress` and `deflate`. The default
  value is `compress`.

All options can be set at initiation or by using a related method. For example, the related methods
for `level` are `getLevel()` and `setLevel()`. You can also use the `setOptions()` method which
accepts an array of all options.

### Lzf Adapter

The Lzf Adapter can compress and decompress:

- Strings

> #### Lzf supports only strings
>
> The Lzf adapter can not handle files and directories.

This adapter makes use of PHP's Lzf extension.

There are no options available to customize this adapter.

### Rar Adapter

The Rar Adapter can compress and decompress:

- Files
- Directories

> #### Rar does not support strings
>
> The Rar Adapter can not handle strings.

This adapter makes use of PHP's Rar extension.

> #### Rar compression not supported
>
> Due to restrictions with the Rar compression format, there is no compression
> available for free. When you want to compress files into a new Rar archive,
> you must provide a callback to the adapter that can invoke a Rar compression
> program.

To customize compression, this adapter supports the following options:

- `archive`: This parameter sets the archive file which should be used or created.
- `callback`: A callback which provides compression support to this adapter.
- `password`: The password which has to be used for decompression.
- `target`: The target where the decompressed files will be written to.

All options can be set at instantiation or by using a related method. For example, the related
methods for `target` are `getTarget()` and `setTarget()`. You can also use the `setOptions()` method
which accepts an array of all options.

### Tar Adapter

The Tar Adapter can compress and decompress:

- Files
- Directories

> #### Tar does not support strings
>
> The Tar Adapter can not handle strings.

This adapter makes use of PEAR's `Archive_Tar` component.

To customize compression, this adapter supports the following options:

- `archive`: This parameter sets the archive file which should be used or created.
- `mode`: A mode to use for compression. Supported are either `NULL`, which
  means no compression at all; `Gz`, which makes use of PHP's Zlib extension;
  and `Bz2`, which makes use of PHP's Bz2 extension. The default value is `NULL`.
- `target`: The target where the decompressed files will be written to.

All options can be set at instantiation or by using a related method. For
example, the related methods for `target` are `getTarget()` and `setTarget()`.
You can also use the `setOptions()` method which accepts an array of all
options.

> #### Directory usage
>
> When compressing directories with Tar, the complete file path is used. This
> means that created Tar files will not only have the subdirectory, but the
> complete path for the compressed file.

### Zip Adapter

The Zip Adapter can compress and decompress:

- Strings
- Files
- Directories

> #### Zip does not support string decompression
>
> The Zip Adapter can not handle decompression to a string; decompression will
> always be written to a file.

This adapter makes use of PHP's `Zip` extension.

To customize compression, this adapter supports the following options:

- `archive`: This parameter sets the archive file which should be used or created.
- `target`: The target where the decompressed files will be written to.

All options can be set at instantiation or by using a related method. For example, the related
methods for `target` are `getTarget()` and `setTarget()`. You can also use the `setOptions()` method
which accepts an array of all options.

## Digits

Returns the string `$value`, removing all but digits.

### Supported Options

There are no additional options for `Zend\Filter\Digits`.

### Basic Usage

```php
$filter = new Zend\Filter\Digits();

print $filter->filter('October 2012');
```

This returns "2012".

```php
$filter = new Zend\Filter\Digits();

print $filter->filter('HTML 5 for Dummies');
```

This returns "5".

## Dir

Given a string containing a path to a file, this function will return the name of the directory.

### Supported Options

There are no additional options for `Zend\Filter\Dir`.

### Basic Usage

```php
$filter = new Zend\Filter\Dir();

print $filter->filter('/etc/passwd');
```

This returns `/etc`.

```php
$filter = new Zend\Filter\Dir();

print $filter->filter('C:/Temp/x');
```

This returns `C:/Temp`.

## Encrypt and Decrypt

These filters allow encrypting and decrypting any given string; they do so via
the use of adapters. Included adapters support `Zend\Crypt\BlockCipher` and
PHP's OpenSSL extension.

### Supported Options

The following options are supported for `Zend\Filter\Encrypt` and
`Zend\Filter\Decrypt`, and segregated by adapter.

#### General options

- `adapter`: This sets the encryption adapter to use.
- `compression`: If the encrypted value should be compressed. Default is no
  compression.

#### BlockCipher options

- `algorithm`: The algorithm to use with `Zend\Crypt\Symmetric\Mcrypt` (use the
  the `getSupportedAlgorithms()` method of that class to determine what is
  supported). If not set, it defaults to `aes`, the Advanced Encryption Standard
  (see the [zend-crypt BlockCipher documentation](http://zendframework.github.io/zend-crypt/block-cipher/)
  for details).
- `key`: The encryption key with which the input will be encrypted. You need the
  same key for decryption.
- `mode`: The encryption mode to use. It should be a
  [valid PHP mcrypt modes](http://php.net/manual/en/mcrypt.constants.php).
  If not set, it defaults to 'cbc'.
- `mode_directory`: The directory where the mode can be found. If not set, it
  defaults to the path set within the `Mcrypt` extension.
- `vector`: The initialization vector which shall be used. If not set, it will
  be a random vector.

#### OpenSSL options

- `envelope`: The encrypted envelope key from the user who encrypted the
  content. You can either provide the path and filename of the key file, or just
  the content of the key file itself. When the `package` option has been set,
  then you can omit this parameter.
- `package`: If the envelope key should be packed with the encrypted value.
  Default is `FALSE`.
- `private`: The private key to use for encrypting the content. You can either
  provide the path and filename of the key file, or just the content of the key
  file itself.
- `public`: The public key of the user for whom you want to provide the
  encrypted content. You can either provide the path and filename of the key
  file, or just the content of the key file itself.

### Adapter Usage

As these two encryption methodologies work completely different, the usage
of the adapters differ. You have to select the adapter you want to use when
initiating the filter.

```php
// Use the BlockCipher adapter
$filter1 = new Zend\Filter\Encrypt(['adapter' => 'BlockCipher']);

// Use the OpenSSL adapter
$filter2 = new Zend\Filter\Encrypt(['adapter' => 'openssl']);
```

To set another adapter, you can use `setAdapter()`; `getAdapter()` will return
the currently composed adapter.

```php
// Use the OpenSSL adapter
$filter = new Zend\Filter\Encrypt();
$filter->setAdapter('openssl');
```

> #### Default adapter
>
> When you do not supply the `adapter` option or do not call `setAdapter()`, the
> `BlockCipher` adapter will be used per default.

### Encryption with BlockCipher

To encrypt a string using the `BlockCipher` adapter, you have to specify the
encryption key by either calling the `setKey()` method or passing it to the
constructor.

```php
// Use the default AES encryption algorithm
$filter = new Zend\Filter\Encrypt(['adapter' => 'BlockCipher']);
$filter->setKey('encryption key');

// or
// $filter = new Zend\Filter\Encrypt([
//     'adapter' => 'BlockCipher',
//     'key'     => 'encryption key'
// ]);

$encrypted = $filter->filter('text to be encrypted');
printf ("Encrypted text: %s\n", $encrypted);
```

You can get and set the encryption values after construction using the
`getEncryption()` and `setEncryption()` methods:

```php
// Use the default AES encryption algorithm
$filter = new Zend\Filter\Encrypt(['adapter' => 'BlockCipher']);
$filter->setKey('encryption key');
var_dump($filter->getEncryption());

// Will print:
//array(4) {
//  ["key_iteration"]=>
//  int(5000)
//  ["algorithm"]=>
//  string(3) "aes"
//  ["hash"]=>
//  string(6) "sha256"
//  ["key"]=>
//  string(14) "encryption key"
//}
```

> #### Default BlockCipher algorithm
>
> The `BlockCipher` adapter uses the [Mcrypt](http://php.net/mcrypt) extension
> by default. That means you will need to install the Mcrypt module in your PHP
> environment.

If you don't specify an initialization Vector (salt or iv), the `BlockCipher` will
generate a random value during each encryption. If you try to execute the
following code, the output will always be different (note that even if the output
is always different, you can still decrypt it using the same key).

```php
$key  = 'encryption key';
$text = 'message to encrypt';

// use the default adapter that is BlockCipher
$filter = new \Zend\Filter\Encrypt();
$filter->setKey('encryption key');
for ($i = 0; $i < 10; $i++) {
   printf("%d) %s\n", $i, $filter->filter($text));
}
```

If you want to obtain the same output, you need to specify a fixed vector, using
the `setVector()` method. This following example always produces the same
encryption output:

```php
// use the default adapter that is BlockCipher
$filter = new \Zend\Filter\Encrypt();
$filter->setKey('encryption key');
$filter->setVector('12345678901234567890');
printf("%s\n", $filter->filter('message'));

// output:
//
04636a6cb8276fad0787a2e187803b6557f77825d5ca6ed4392be702b9754bb3MTIzNDU2Nzg5MDEyMzQ1NgZ+zPwTGpV6gQqPKECinig=
```

> #### Use diffrent vectors
>
> For security purposes, it's always better to use a different vector on each
> encryption. We suggest using `setVector()` only in exceptional circumstances.

### Decryption with BlockCipher

For decrypting content previously encrypted with `BlockCipher`, you need to use
the same options used for encryption.

If you used only the encryption key, you can just use it to decrypt the content.
As soon as you have provided all options, decryption works the same as
encryption.

```php
$content = '04636a6cb8276fad0787a2e187803b6557f77825d5ca6ed4392be702b9754bb3MTIzNDU2Nzg5MDEyMzQ1NgZ+zPwTGpV6gQqPKECinig=';
// use the default adapter (BlockCipher):
$filter = new Zend\Filter\Decrypt();
$filter->setKey('encryption key');
printf("Decrypt: %s\n", $filter->filter($content));

// output:
// Decrypt: message
```

Note that even if we did not specify the same vector, the `BlockCipher` is able
to decrypt the message because the vector is stored in the encryption string
itself (note that the vector can be stored in plaintext; it is not a secret, and
only used to improve the randomness of the encryption algorithm).

### Encryption with OpenSSL

If you have installed the `OpenSSL` extension, you can also use the `OpenSSL`
adapter. You can get or set the public key either during instantiation, or later
via the `setPublicKey()` method. The private key can also be set after-the-fact
via the `setPrivateKey()` method.

```php
// Use openssl and provide a private key
$filter = new Zend\Filter\Encrypt([
   'adapter' => 'openssl',
   'private' => '/path/to/mykey/private.pem',
]);

// Add the private key separately:
$filter->setPublicKey('/public/key/path/public.pem');
```

> #### Valid keys are required
>
> The `OpenSSL` adapter will not work with invalid or missing keys.

When you want to decode content encoded with a passphrase, you will not only
need the public key, but also the passphrase:

```php
// Use openssl and provide a private key
$filter = new Zend\Filter\Encrypt([
   'adapter' => 'openssl',
   'passphrase' => 'enter here the passphrase for the private key',
   'private' => '/path/to/mykey/private.pem',
   'public' => '/public/key/path/public.pem'
]);
```

When providing the encrypted content to the recipient, you will also need to
ensure they have the passphrase and the envelope keys so they may decrypt the
message. You can get the envelope keys using the `getEnvelopeKey()` method:

A complete example for encrypting content with `OpenSSL` looks like the
following:

```php
// Use openssl and provide a private key
$filter = new Zend\Filter\Encrypt([
   'adapter' => 'openssl',
   'passphrase' => 'enter here the passphrase for the private key',
   'private' => '/path/to/mykey/private.pem',
   'public' => '/public/key/path/public.pem'
]);

$encrypted = $filter->filter('text_to_be_encoded');
$envelope  = $filter->getEnvelopeKey();
print $encrypted;

// For decryption look at the Decrypt filter
```

### Simplified usage with OpenSSL

As noted in the previous section, you need to provide the envelope key to the
recipient in order for them to decrypt the message. This adds complexity,
particularly if you are encrypting multiple values.

To simplify usage, you can set the `package` option to `TRUE` when creating your
`Encrypt` instance (the default value is `FALSE`). This will return a value
containing both the encrypted message *and* the envelope key:

```php
// Use openssl and provide a private key
$filter = new Zend\Filter\Encrypt([
   'adapter' => 'openssl',
   'private' => '/path/to/mykey/private.pem',
   'public'  => '/public/key/path/public.pem',
   'package' => true,
]);

$encrypted = $filter->filter('text_to_be_encoded');
print $encrypted;

// For decryption look at the Decrypt filter
```

Now the returned value contains the encrypted value and the envelope. You don't
need to fetch the envelope key separately.

However, there is one negative aspect to this: the encrypted value can now only
be decrypted by using `Zend\Filter\Encrypt`.

### Compressing Content

Based on the original value, the encrypted value can be a very large string. To
reduce the value, `Zend\Filter\Encrypt` allows the usage of compression.

The `compression` option can either be set to the name of a compression adapter,
or to an array which sets all required options for the compression adapter.

```php
// Use basic compression adapter
$filter1 = new Zend\Filter\Encrypt([
   'adapter'     => 'openssl',
   'private'     => '/path/to/mykey/private.pem',
   'public'      => '/public/key/path/public.pem',
   'package'     => true,
   'compression' => 'bz2'
]);

// Compression adatper with options:
$filter2 = new Zend\Filter\Encrypt([
   'adapter'     => 'openssl',
   'private'     => '/path/to/mykey/private.pem',
   'public'      => '/public/key/path/public.pem',
   'package'     => true,
   'compression' => ['adapter' => 'zip', 'target' => '\usr\tmp\tmp.zip']
]);
```

> #### Decrypt using the same settings
>
> When you want to decrypt a value which is additionally compressed, then you
> need to set the same compression settings for decryption as for encryption;
> otherwise decryption will fail.

### Decryption with OpenSSL

Decryption with `OpenSSL` follows the same patterns as for encryption, with one
difference: you must have all data, including the envelope key, from the person
who encrypted the content.

As an example:

```php
// Use openssl and provide a private key
$filter = new Zend\Filter\Decrypt([
   'adapter' => 'openssl',
   'private' => '/path/to/mykey/private.pem'
]);

// Add the envelope key; you can also add this during instantiation.
$filter->setEnvelopeKey('/key/from/encoder/envelope_key.pem');
```

If encyption used a passphrase, you'll need to provide that as well:

```php
// Use openssl and provide a private key
$filter = new Zend\Filter\Decrypt([
   'adapter' => 'openssl',
   'passphrase' => 'enter here the passphrase for the private key',
   'private' => '/path/to/mykey/private.pem'
]);

// Add the envelope key; you can also add this during instantiation.
$filter->setEnvelopeKey('/key/from/encoder/envelope_key.pem');
```

Finally, you can decode the content.

Our complete example for decrypting the previously encrypted content looks like
this:

```php
// Use openssl and provide a private key
$filter = new Zend\Filter\Decrypt([
   'adapter' => 'openssl',
   'passphrase' => 'enter here the passphrase for the private key',
   'private' => '/path/to/mykey/private.pem'
]);

// Add the envelope key; you can also add this during instantiation.
$filter->setEnvelopeKey('/key/from/encoder/envelope_key.pem');

$decrypted = $filter->filter('encoded_text_normally_unreadable');
print $decrypted;
```

## HtmlEntities

Returns the string `$value`, converting characters to their corresponding HTML
entity equivalents when possible.

### Supported Options

The following options are supported for `Zend\Filter\HtmlEntities`:

- `quotestyle`: Equivalent to the PHP `htmlentities()` native function parameter
  `quote_style`.  This allows you to define what will be done with 'single' and
  "double" quotes. The following constants are accepted: `ENT_COMPAT`,
  `ENT_QUOTES`, and `ENT_NOQUOTES`, with the default being `ENT_COMPAT`.
- `charset`: Equivalent to the PHP `htmlentities()` native function parameter
  `charset`. This defines the character set to be used in filtering. Unlike the
  PHP native function, the default is 'UTF-8'. See the [PHP htmlentities
  manual](http://php.net/htmlentities) for a list of supported character sets.

  This option can also be set via the `$options` parameter as a Traversable
  object or array. The option key will be accepted as either `charset` or
  `encoding`.
- `doublequote`: Equivalent to the PHP `htmlentities()` native function
  parameter `double_encode`. If set to `false`, existing HTML entities will not
  be encoded. The default is to convert everything (`true`).

  This option must be set via the `$options` parameter or the
  `setDoubleEncode()` method.

### Basic Usage

```php
$filter = new Zend\Filter\HtmlEntities();

print $filter->filter('<');
```

### Quote Style

`Zend\Filter\HtmlEntities` allows changing the quote style used. This can be useful when you want to
leave double, single, or both types of quotes un-filtered.

```php
$filter = new Zend\Filter\HtmlEntities(['quotestyle' => ENT_QUOTES]);

$input = "A 'single' and " . '"double"';
print $filter->filter($input);
```

The above example returns `A &#039;single&#039; and &quot;double&quot;`. Notice
that 'single' as well as "double" quotes are filtered.

```php
$filter = new Zend\Filter\HtmlEntities(['quotestyle' => ENT_COMPAT]);

$input = "A 'single' and " . '"double"';
print $filter->filter($input);
```

The above example returns `A 'single' and &quot;double&quot;`. Notice that
"double" quotes are filtered while 'single' quotes are not altered.

```php
$filter = new Zend\Filter\HtmlEntities(['quotestyle' => ENT_NOQUOTES]);

$input = "A 'single' and " . '"double"';
print $filter->filter($input);
```

The above example returns `A 'single' and "double"`. Notice that neither
"double" or 'single' quotes are altered.

### Helper Methods

To change or retrieve the `quotestyle` after instantiation, the two methods
`setQuoteStyle()` and `getQuoteStyle()` may be used respectively.
`setQuoteStyle()` accepts one parameter, `$quoteStyle`, which accepts one of the
constants `ENT_COMPAT`, `ENT_QUOTES`, or `ENT_NOQUOTES`.

```php
$filter = new Zend\Filter\HtmlEntities();

$filter->setQuoteStyle(ENT_QUOTES);
print $filter->getQuoteStyle(ENT_QUOTES);
```

To change or retrieve the `charset` after instantiation, the two methods
`setCharSet()` and `getCharSet()` may be used respectively. `setCharSet()`
accepts one parameter, `$charSet`. See the [PHP htmlentities manual
page](http://php.net/htmlentities) for a list of supported character sets.

```php
$filter = new Zend\Filter\HtmlEntities();

$filter->setQuoteStyle(ENT_QUOTES);
print $filter->getQuoteStyle(ENT_QUOTES);
```

To change or retrieve the `doublequote` option after instantiation, the two methods
`setDoubleQuote()` and `getDoubleQuote()` may be used respectively. `setDoubleQuote()` accepts one
boolean parameter, `$doubleQuote`.

```php
$filter = new Zend\Filter\HtmlEntities();

$filter->setQuoteStyle(ENT_QUOTES);
print $filter->getQuoteStyle(ENT_QUOTES);
```

## ToInt

`Zend\Filter\ToInt` allows you to transform a scalar value into an integer.

### Supported Options

There are no additional options for `Zend\Filter\ToInt`.

### Basic Usage

```php
$filter = new Zend\Filter\ToInt();

print $filter->filter('-4 is less than 0');
```

This will return '-4'.

### Migration from 2.0-2.3 to 2.4+

Version 2.4 adds support for PHP 7. In PHP 7, `int` is a reserved keyword, which required renaming
the `Int` filter. If you were using the `Int` filter directly previously, you will now receive an
`E_USER_DEPRECATED` notice on instantiation. Please update your code to refer to the `ToInt` class
instead.

Users pulling their `Int` filter instance from the filter plugin manager receive a `ToInt` instance
instead starting in 2.4.0.

## ToNull

This filter will change the given input to be `NULL` if it meets specific
criteria. This is often necessary when you work with databases and want to have
a `NULL` value instead of a boolean or any other type.

### Supported Options

The following options are supported for `Zend\Filter\ToNull`:

- `type`: The variable type which should be supported.

### Default Behavior

Per default this filter works like PHP's `empty()` method; in other words, if
`empty()` returns a boolean `TRUE`, then a `NULL` value will be returned.

```php
$filter = new Zend\Filter\ToNull();
$value  = '';
$result = $filter->filter($value);
// returns null instead of the empty string
```

This means that without providing any configuration, `Zend\Filter\ToNull` will
accept all input types and return `NULL` in the same cases as `empty()`.

Any other value will be returned as is, without any changes.

### Changing the Default Behavior

Sometimes it's not enough to filter based on `empty()`. Therefore
`Zend\Filter\ToNull` allows you to configure which types will be converted, and
which not.

The following types can be handled:

- `boolean`: Converts a boolean `FALSE` value to `NULL`.
- `integer`: Converts an integer `0` value to `NULL`.
- `empty_array`: Converts an empty `array` to `NULL`.
- `float`: Converts an float `0.0` value to `NULL`.
- `string`: Converts an empty string `''` to `NULL`.
- `zero`: Converts a string containing the single character zero (`'0'`) to `NULL`.
- `all`: Converts all above types to `NULL`. (This is the default behavior.)

There are several ways to select which of the above types are filtered. You can
give one or multiple types and add them, you can give an array, you can use
constants, or you can give a textual string.  See the following examples:

```php
// converts false to null
$filter = new Zend\Filter\ToNull(Zend\Filter\ToNull::BOOLEAN);

// converts false and 0 to null
$filter = new Zend\Filter\ToNull(
    Zend\Filter\ToNull::BOOLEAN + Zend\Filter\ToNull::INTEGER
);

// converts false and 0 to null
$filter = new Zend\Filter\ToNull([
    Zend\Filter\ToNull::BOOLEAN,
    Zend\Filter\ToNull::INTEGER
]);

// converts false and 0 to null
$filter = new Zend\Filter\ToNull([
    'boolean',
    'integer',
]);
```

You can also give a `Traversable` or an array to set the wished types. To set
types afterwards use `setType()`.

### Migration from 2.0-2.3 to 2.4+

Version 2.4 adds support for PHP 7. In PHP 7, `null` is a reserved keyword, which required renaming
the `Null` filter. If you were using the `Null` filter directly previously, you will now receive an
`E_USER_DEPRECATED` notice on instantiation. Please update your code to refer to the `ToNull` class
instead.

Users pulling their `Null` filter instance from the filter plugin manager receive a `ToNull`
instance instead starting in 2.4.0.

## NumberFormat

The `NumberFormat` filter can be used to return locale-specific number and percentage strings. It
extends the `NumberParse` filter, which acts as wrapper for the `NumberFormatter` class within the
Internationalization extension (Intl).

This filter is part of the zend-i18n package; you will need to include that
package in your application to use it.

### Supported Options

The following options are supported for `NumberFormat`:

```php
NumberFormat([ string $locale [, int $style [, int $type ]]])
```

- `$locale`: (Optional) Locale in which the number would be formatted (locale
  name, e.g. `en_US`). If unset, it will use the default locale
  (`Locale::getDefault()`). Methods for getting/setting the locale are also
  available: `getLocale()` and `setLocale()`.

- `$style`: (Optional) Style of the formatting, one of the
  [format style constants](http://www.php.net/manual/class.numberformatter.php#intl.numberformatter-constants.unumberformatstyle).
  If unset, it will use `NumberFormatter::DEFAULT_STYLE` as the default style.
  Methods for getting/setting the format style are also available: `getStyle()`
  and `setStyle()`.

- `$type`: (Optional) The [formatting type](http://www.php.net/manual/class.numberformatter.php#intl.numberformatter-constants.types)
  to use. If unset, it will use `NumberFormatter::TYPE_DOUBLE` as the default
  type. Methods for getting/setting the format type are also available:
  `getType()` and `setType()`.

### Basic Usage

```php
$filter = new \Zend\I18n\Filter\NumberFormat('de_DE');
echo $filter->filter(1234567.8912346);
// Returns '1.234.567,891'

$filter = new \Zend\I18n\Filter\NumberFormat('en_US', NumberFormatter::PERCENT);
echo $filter->filter(0.80);
// Returns '80%'

$filter = new \Zend\I18n\Filter\NumberFormat('fr_FR', NumberFormatter::SCIENTIFIC);
echo $filter->filter(0.00123456789);
// Returns '1,23456789E-3'
```

## NumberParse

The `NumberParse` filter can be used to parse a number from a string. It acts as
a wrapper for the `NumberFormatter` class within the Internationalization
extension (Intl).

This filter is part of the zend-i18n package; you will need to include that
package in your application to use it.

### Supported Options

The following options are supported for `NumberParse`:

```php
NumberParse([ string $locale [, int $style [, int $type ]]])
```

- `$locale`: (Optional) Locale in which the number would be parsed (locale name,
  e.g. `en_US`). If unset, it will use the default locale
  (`Locale::getDefault()`). Methods for getting/setting the locale are also
  available: `getLocale()` and `setLocale()`.

- `$style`: (Optional) Style of the parsing, one of the
  [format style constants](http://www.php.net/manual/class.numberformatter.php#intl.numberformatter-constants.unumberformatstyle).
  If unset, it will use `NumberFormatter::DEFAULT_STYLE` as the default style.
  Methods for getting/setting the parse style are also available: `getStyle()`
  and `setStyle()`.

- `$type`: (Optional) The [parsing type](http://www.php.net/manual/class.numberformatter.php#intl.numberformatter-constants.types)
  to use. If unset, it will use `NumberFormatter::TYPE_DOUBLE` as the default
  type. Methods for getting/setting the parse type are also available:
  `getType()` and `setType()`.

### Basic Usage

```php
$filter = new \Zend\I18n\Filter\NumberParse('de_DE');
echo $filter->filter('1.234.567,891');
// Returns 1234567.8912346

$filter = new \Zend\I18n\Filter\NumberParse('en_US', NumberFormatter::PERCENT);
echo $filter->filter('80%');
// Returns 0.80

$filter = new \Zend\I18n\Filter\NumberParse('fr_FR', NumberFormatter::SCIENTIFIC);
echo $filter->filter('1,23456789E-3');
// Returns 0.00123456789
```

## PregReplace

`Zend\Filter\PregReplace` performs a search using regular expressions and replaces all found
elements.

### Supported Options

The following options are supported for `Zend\Filter\PregReplace`:

- `pattern`: The pattern to search for.
- `replacement`: The string which to use as a replacement for the matches; this
  can optionally contain placeholders for matched groups in the search pattern.

### Basic Usage

To use this filter properly, you must give both options listed above.

The `pattern` option has to be given to set the pattern to search for. It can be
a string for a single pattern, or an array of strings for multiple patterns.

The `replacement` option indicates the string to replace matches with, and can
contain placeholders for matched groups from the search `pattern`. The value may
be a string replacement, or an array of string replacements.

```php
$filter = new Zend\Filter\PregReplace([
    'pattern'     => '/bob/',
    'replacement' => 'john',
]);
$input  = 'Hi bob!';

$filter->filter($input);
// returns 'Hi john!'
```

You can also use `setPattern()` to set the pattern(s), and `setReplacement()` set
the replacement(s).

```php
$filter = new Zend\Filter\PregReplace();
$filter
    ->setPattern(array('bob', 'Hi'))
    ->setReplacement(array('john', 'Bye'));
$input = 'Hi bob!';

$filter->filter($input);
// returns 'Bye john!'
```

For more complex usage, read the
[PCRE Pattern chapter of the PHP manual](http://www.php.net/manual/en/reference.pcre.pattern.modifiers.php).

## RealPath

This filter will resolve given links and pathnames, and returns the canonicalized
absolute pathnames.

### Supported Options

The following options are supported for `Zend\Filter\RealPath`:

- `exists`: This option defaults to `TRUE`, which validates that the given path
  really exists.

### Basic Usage

For any given link or pathname, its absolute path will be returned. References
to `/./`, `/../` and extra `/` sequences in the input path will be stripped. The
resulting path will not have any symbolic links, `/./`, or `/../` sequences.

`Zend\Filter\RealPath` will return `FALSE` on failure, e.g. if the file does not exist. On BSD
systems `Zend\Filter\RealPath` doesn't fail if only the last path component doesn't exist, while
other systems will return `FALSE`.

```php
$filter = new Zend\Filter\RealPath();
$path = '/www/var/path/../../mypath';
$filtered = $filter->filter($path);

// returns '/www/mypath'
```

### Non-Existing Paths

Sometimes it is useful to get paths to files that do n0t exist; e.g., when you
want to get the real path for a path you want to create. You can then either
provide a `FALSE` `exists` value at initiation, or use `setExists()` to set it.

```php
$filter = new Zend\Filter\RealPath(false);
$path = '/www/var/path/../../non/existing/path';
$filtered = $filter->filter($path);

// returns '/www/non/existing/path'
// even when file_exists or realpath would return false
```

## StringToLower

This filter converts any input to lowercase.

### Supported Options

The following options are supported for `Zend\Filter\StringToLower`:

- `encoding`: This option can be used to set an encoding to use.

### Basic Usage

```php
$filter = new Zend\Filter\StringToLower();

print $filter->filter('SAMPLE');
// returns "sample"
```

### Handling alternate encoding

By default, `StringToLower` will only handle characters from the locale of your
server; characters from other charsets will be ignored. If you have the mbstring
extension, however, you can use the filter with other encodings.  Pass the
desired encoding when initiating the `StringToLower` filter, or use the
`setEncoding()` method to change it.

```php
// using UTF-8
$filter = new Zend\Filter\StringToLower('UTF-8');

// or give an array which can be useful when using a configuration
$filter = new Zend\Filter\StringToLower(['encoding' => 'UTF-8']);

// or do this afterwards
$filter->setEncoding('ISO-8859-1');
```

> #### Setting invalid encodings
>
> Be aware that you will get an exception when:
>
> - you attempt to set an encoding and the mbstring extension is unavailable; or
> - you attempt to set an encoding unsupported by the mbstring extension.

## StringToUpper

This filter converts any input to UPPERCASE.

### Supported Options

The following options are supported for `Zend\Filter\StringToUpper`:

- `encoding`: This option can be used to set the encoding to use.

### Basic Usage

```php
$filter = new Zend\Filter\StringToUpper();

print $filter->filter('Sample');
// returns "SAMPLE"
```

### Different Encoded Strings

Like the `StringToLower` filter, this filter will only handle characters
supported by your server locale, unless you have the mbstring extension enabled.
Using different character sets works the same as with `StringToLower`.

```php
$filter = new Zend\Filter\StringToUpper(['encoding' => 'UTF-8']);

// or do this afterwards
$filter->setEncoding('ISO-8859-1');
```

## StringTrim

This filter modifies a given string such that certain characters are removed
from the beginning and end.

### Supported Options

The following options are supported for `Zend\Filter\StringTrim`:

- `charlist`: List of characters to remove from the beginning and end of the
  string. If this is not set or is null, the default behavior will be invoked,
  which is to remove only whitespace from the beginning and end of the string.

### Basic Usage

```php
$filter = new Zend\Filter\StringTrim();

print $filter->filter(' This is (my) content: ');
```

The above example returns `This is (my) content:`. Notice that the whitespace
characters have been removed.

### Specifying alternate characters

```php
$filter = new Zend\Filter\StringTrim(':');
// or new Zend\Filter\StringTrim(array('charlist' => ':'));

print $filter->filter(' This is (my) content:');
```

The above example returns `This is (my) content`. Notice that the whitespace
characters and colon are removed. You can also provide a `Traversable` or an
array with a `charlist` key. To set the desired character list after
instantiation, use the `setCharList()` method. `getCharList()` returns the
current character list.

## StripNewlines

This filter modifies a given string and removes all new line characters within
that string.

### Supported Options

There are no additional options for `Zend\Filter\StripNewlines`:

### Basic Usage

```php
$filter = new Zend\Filter\StripNewlines();

print $filter->filter(' This is (my)``\n\r``content: ');
```

The above example returns `This is (my) content:`. Notice that all newline
characters have been removed.

## StripTags

This filter can strip XML and HTML tags from given content.

> ### Zend\\Filter\\StripTags is potentially insecure
>
> Be warned that `Zend\\Filter\\StripTags` should only be used to strip *all*
> available tags.  Using `Zend\\Filter\\StripTags` to make your site secure by
> stripping *some* unwanted tags will lead to unsecure and dangerous code,
> including potential XSS vectors.
> 
> For a fully secure solution that allows selected filtering of HTML tags, use
> either Tidy or HtmlPurifier.

### Supported Options

The following options are supported for `Zend\Filter\StripTags`:

- `allowAttribs`: This option sets the attributes which are accepted. All other
  attributes are stripped from the given content.
- `allowTags`: This option sets the tags which are accepted. All other tags will
  be stripped from; the given content.

### Basic Usage

```php
$filter = new Zend\Filter\StripTags();

print $filter->filter('<B>My content</B>');
```

The result will be the stripped content `My content`.

When the content contains broken or partial tags, any content following the
opening tag will be completely removed:

```php
$filter = new Zend\Filter\StripTags();

print $filter->filter('This contains <a href="http://example.com">no ending tag');
```

The above will return `This contains`, with the rest being stripped.

### Allowing Defined Tags

`Zend\Filter\StripTags` allows stripping all but a whitelist of tags. As an
example, this can be used to strip all markup except for links:

```php
$filter = new Zend\Filter\StripTags(['allowTags' => 'a']);

$input  = "A text with <br/> a <a href='link.com'>link</a>";
print $filter->filter($input);
```

The above will return `A text with a <a href='link.com'>link</a>`;
it strips all tags but the link. By providing an array, you can specify multiple
tags at once.

> #### Warning
>
> Do not use this feature to secure content. This component does not replace the
> use of a properly configured html filter.

### Allowing Defined Attributes

You can also strip all but a whitelist of attributes from a tag:

```php
$filter = new Zend\Filter\StripTags([
    'allowTags' => 'img',
    'allowAttribs' => 'src',
]);

$input  = "A text with <br/> a <img src='picture.com' width='100'>picture</img>";
print $filter->filter($input);
```

The above will return `A text with a <img src='picture.com'>picture</img>`; it
strips all tags but `<img>`, and all attributes but `src` from those tags.By
providing an array you can set multiple attributes at once.

### Allow specific tags with specific attributes

You can also pass the tag whitelist as a set of tag/attribute values. Each key
will be an allowed tag, pointing to a list of whitelisted attributes for that
tag.

```php
$allowedElements = [
    'img' => [
        'src',
        'width'
    ],
    'a' => [
        'href'
    ]
];
$filter = new Zend\Filter\StripTags($allowedElements);

$input = "A text with <br/> a <img src='picture.com' width='100'>picture</img> click "
    . "<a href='http://picture.com/zend' id='hereId'>here</a>!";
print $filter->filter($input);
```

The above will return `A text with a <img src='picture.com'
width='100'>picture</img> click <a href='<http://picture.com/zend>'>here</a>!`
as the result.

## UriNormalize

This filter sets the scheme on a URI if the scheme is missing.

### Supported Options

The following options are supported for `Zend\Filter\UriNormalize`:

- `defaultScheme`: This option can be used to set the default scheme to use when
  parsing scheme-less URIs.
- `enforcedScheme`: Set a URI scheme to enforce on schemeless URIs.

### Basic Usage

```php
$filter = new Zend\Filter\UriNormalize(array(
    'enforcedScheme' => 'https'
));

echo $filter->filter('www.example.com');
```

The above results int the string `https://www.example.com`.

## Whitelist

This filter will return `null` if the value being filtered is not present the
filter's allowed list of values. If the value is present, it will return that
value.

For the opposite functionality see the [Blacklist](#blacklist) filter.

### Supported Options

The following options are supported for `Zend\Filter\Whitelist`:

- `strict`: Uses strict mode for comparisons; passed to `in_array()`'s third argument.
- `list`: An array of allowed values.

### Basic Usage

```php
$whitelist = new \Zend\Filter\Whitelist([
    'list' => ['allowed-1', 'allowed-2']
]);
echo $whitelist->filter('allowed-2');   // => 'allowed-2'
echo $whitelist->filter('not-allowed'); // => null
```
