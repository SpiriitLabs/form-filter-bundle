UPGRADE FROM previous to 9.0
=============================

You need to search and replace all namespace in your code:

before

```php
Lexik\Bundle\FormFilterBundle
```

after

```php
Spiriit\Bundle\FormFilterBundle
```


In `bundles.php`:

before

```php
use Lexik\Bundle\FormFilterBundle\LexikFormFilterBundle;

...

LexikFormFilterBundle::class => ['all' => true],
```

after

```php
use Spiriit\Bundle\FormFilterBundle\SpiriitFormFilterBundle;

...

SpiriitFormFilterBundle::class => ['all' => true],
```


In `lexik_form_filter.yaml` / `lexik_form_filter.php` bundle configuration file:

before

```php
lexik_form_filter
```

after

```php
spiriit_form_filter
```

