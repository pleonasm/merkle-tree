# Merkle Tree Implementation #

[![Build Status](https://travis-ci.org/pleonasm/merkle-tree.png?branch=master)](https://travis-ci.org/pleonasm/merkle-tree)
[![Coverage Status](https://coveralls.io/repos/pleonasm/merkle-tree/badge.png)](https://coveralls.io/r/pleonasm/merkle-tree)

This is an implementation of a hash tree or [Merkle Tree](http://en.wikipedia.org/wiki/Merkle_Tree)
for PHP. 

## Install ##

Install via [Composer](http://getcomposer.org) (make sure you have composer in your path or in your project).

Put the following in your package.json:

```javascript
{
    "require": {
        "pleonasm/merkle-tree": "*"
    }
}
```

Then run `composer install`.

## Usage ##

```php
<?php
use Pleo\Merkle\FixedSizeTree;

require 'vendor/autoload.php';

$hasher = function ($data) {
    return md5($data, true);
};

$tree = new FixedSizeTree(16, $hasher);

$tree->set(0, 'Science');
$tree->set(1, 'is');
$tree->set(2, 'made');
$tree->set(3, 'up');
$tree->set(4, 'of');
$tree->set(5, 'so');
$tree->set(6, 'many');
$tree->set(7, 'things');
$tree->set(8, 'that');
$tree->set(9, 'appear');
$tree->set(10, 'obvious');
$tree->set(11, 'after');
$tree->set(12, 'they');
$tree->set(13, 'are');
$tree->set(14, 'explained');
$tree->set(15, '.');

$hash = implode('', unpack('H*', $tree->hash()));
echo $hash . "\n"; // ac264d7c8de67a27345e752e5a56c66b
```

## Note ##

The class names are not really good and will probably change before they are
considered done.
