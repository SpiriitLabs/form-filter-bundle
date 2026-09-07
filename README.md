# SpiriitFormFilterBundle

[![PHP Version](https://img.shields.io/packagist/php-v/spiriitlabs/form-filter-bundle)](https://packagist.org/packages/spiriitlabs/form-filter-bundle)
[![Latest Stable Version](https://poser.pugx.org/spiriitlabs/form-filter-bundle/v/stable.svg)](https://packagist.org/packages/spiriitlabs/form-filter-bundle)
![Packagist Downloads](https://img.shields.io/packagist/dm/spiriitlabs/form-filter-bundle?style=flat-square&label=Downloads%20Monthly)
[![CI Tests](https://github.com/SpiriitLabs/form-filter-bundle/actions/workflows/ci.yml/badge.svg)](https://github.com/SpiriitLabs/form-filter-bundle/actions/workflows/ci.yml)

Build a Symfony form dedicated to filtering an entity, then let the bundle turn it into Doctrine ORM
query builder conditions.

> `LexikFormFilterBundle` is now `SpiriitFormFilterBundle`: only the name and the GitHub organization
> changed, the code remains the same.

**📖 Full documentation: [spiriitlabs.github.io/form-filter-bundle](https://spiriitlabs.github.io/form-filter-bundle/)**

The idea is:

1. Create a form type extending `Symfony\Component\Form\AbstractType`, as usual.
2. Add fields using the provided filter types (e.g. `TextFilterType::class` instead of `TextType::class`).
3. Call `FilterBuilderUpdater` to build the query from the form instance, then execute it.

Any type works, but filtering on a type other than a `XxxFilterType::class` requires a
[custom listener](https://spiriitlabs.github.io/form-filter-bundle/features/working-with-the-bundle) to apply the filter.

## Installation

```bash
composer require spiriitlabs/form-filter-bundle
```

Requires PHP 8.1+ and Symfony 5.4+. For Symfony 2.8/3.4, use the latest `v5.*` tag.

## Use it in two steps

### 1. Create a filter form

```php
<?php

namespace Project\Form\Filter;

use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type as Filters;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class RankFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', Filters\TextFilterType::class);
        $builder->add('rank', Filters\NumberFilterType::class);
    }
}
```

### 2. Apply it to a query builder

```php
class DefaultController extends AbstractController
{
    public function __invoke(
        Request $request,
        FormFactoryInterface $formFactory,
        EntityManagerInterface $em,
        FilterBuilderUpdater $filterBuilderUpdater,
    ): Response {
        $form = $formFactory->create(RankFilterType::class);
        $form->handleRequest($request);

        $filterBuilder = $em->getRepository(MyEntity::class)->createQueryBuilder('e');

        $filterBuilderUpdater->addFilterConditions($form, $filterBuilder);

        // now look at the DQL =)
        dump($filterBuilder->getDql());

        return $this->render('testFilter.html.twig', ['form' => $form]);
    }
}
```

## Documentation

- [Installation](https://spiriitlabs.github.io/form-filter-bundle/guide/installation)
- [Basics](https://spiriitlabs.github.io/form-filter-bundle/guide/basics)
- [Configuration](https://spiriitlabs.github.io/form-filter-bundle/guide/configuration)
- [Provided filter types](https://spiriitlabs.github.io/form-filter-bundle/features/provided-types)
- [Filter state persistence](https://spiriitlabs.github.io/form-filter-bundle/features/persistence)
- [Debugging & profiler](https://spiriitlabs.github.io/form-filter-bundle/features/debugging)

## Community support

Please [open a question on StackOverflow](http://stackoverflow.com/questions/ask) using the
[`spiriitformfilterbundle` tag](http://stackoverflow.com/questions/tagged/spiriitformfilterbundle), the
official support platform for this bundle. GitHub issues are dedicated to bug reports and feature requests.

## Credits

- Spiriit <dev@spiriit.com>
- [All contributors](https://github.com/SpiriitLabs/form-filter-bundle/graphs/contributors)

## License

This bundle is under the MIT license. For the whole copyright, see the [LICENSE](LICENSE) file
distributed with this source code.
