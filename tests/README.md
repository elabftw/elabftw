# Tips

To debug a var during unit tests, use this:

~~~php
fwrite(STDERR, print_r($var, true));
~~~
