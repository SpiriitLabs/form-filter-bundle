[1] Installation
================

Add the bundle to your `composer.json` file:

```javascript
require: {
    // ...
    "spiriitlabs/form-filter-bundle": "~8.0" // check packagist.org for more tags
    // ...
}
```

Or install directly through composer with:

```
composer.phar require spiriitlabs/form-filter-bundle ~8.0
# For latest version
composer.phar require spiriitlabs/form-filter-bundle dev-master
```

Then run a composer update:

```shell
composer.phar update
# OR
composer.phar update spiriitlabs/form-filter-bundle # to only update the bundle
```

Register the bundle with your kernel:

```php
    // in AppKernel::registerBundles()
    $bundles = array(
        // ...
        new Spiriit\Bundle\FormFilterBundle\SpiriitFormFilterBundle(),
        // ...
    );
```

***

Next: [2. Configuration](configuration.md)
