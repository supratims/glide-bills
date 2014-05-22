# Glide API Interface

A simple PHP interface to return quotes from the [Glide](https://www.glide.uk.com/) API. Longer term plan to abstract out REST interface to make other endpoints accessible.

### Install

You can install Glide with [Composer](http://getcomposer.org). Add the following to the `require` key of your `composer.json` file:

    "m1ke/glide-bills": "dev-master"

### Authors

Written by [Mike Lehan](http://twitter.com/m1ke) and [StuRents.com](http://sturents.com).

### Documentation

A PDF API specification used to build this library, provided by Glide, can be found in `docs/`. I have been unable to find a copy of this made available elsewhere on the web.

### Testing

Run `phpunit test.php` to test all implemented interfaces. Other interfaces can be added to tests without changing the class thanks to the `__call` magic method.