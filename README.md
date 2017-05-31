php-docblocks
=======

Parses php doc blocks and makes them easily accessible.

This is based on using reflection and results should be heavily cached or only be used in processes which aren't time critical.

## Install

Run `composer require crazyfactory/docblocks` to install the latest version into your composer powered project.

## Usage

You can pass in any doc-block formatted string into the constructor of *DocBlock* to have it parsed.

```php
$dc = new DocBlock('/** myDocBlockString */');
```

Or you can pass in any Reflection object offering *getDocComment()* like *ReflectionMethod*, *ReflectionClass* etc.

```php
$dc = new DocBlock(new \ReflectionClass(MyClass::class));
```

If you only care for the results in form of simple DocBlockParameter-array you can use the parser directly.

```php
$results = DocBlock::parse($myDocBlockString);
```


