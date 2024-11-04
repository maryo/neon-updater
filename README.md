# maryo/neon-updater

Format Preserving Nette NEON Update.

> [!WARNING]  
> Updating of inline arrays is not yet supported.

> [!WARNING]  
> Deleting of keys is not yet supported.

## Installation

```bash
composer require maryo/neon-updater
```

## Usage

```neon
foo: # lorem ipsum
    foo: foo # foo
    bar: bar
```

Given this NEON string, you can update the value of `foo.foo` key to `value` using the following code:

```php
use Maryo\NeonUpdater;

$updatedNeon = NeonUpdater::update($neon, ['foo', 'foo'], 'value');
```

The value of the `$updatedNeon` variable will be:

```neon
foo: # lorem ipsum
    foo: value # foo
    bar: bar
```

To append a new value, pass null as the segment in the `$path`:

```php
$updatedNeon = NeonUpdater::update($neon, ['foo', null], 'baz');
```

The value of the `$updatedNeon` variable will be:

```neon
foo: # lorem ipsum
    foo: foo # foo
    bar: bar
    - baz
```
