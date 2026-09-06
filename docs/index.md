---
layout: home

hero:
  name: Form Filter Bundle
  text: Turn a Symfony Form into a Doctrine query
  tagline: Build a filter form the way you build any other Symfony form, and let the bundle translate it into WHERE clauses on your query builder.
  image:
    src: /logo.svg
    alt: Form Filter Bundle
  actions:
    - theme: brand
      text: Get Started
      link: /guide/installation
    - theme: alt
      text: View on GitHub
      link: https://github.com/SpiriitLabs/form-filter-bundle

features:
  - icon: 🧩
    title: Provided filter types
    details: TextFilterType, NumberFilterType, DateRangeFilterType, EntityFilterType and more — drop-in replacements for the core Symfony form types.
    link: /features/provided-types
    linkText: Browse the types
  - icon: 🔗
    title: Associations & embeddables
    details: Filter across Doctrine associations and embeddables by nesting filter form types and declaring the joins you need.
    link: /features/working-with-the-bundle
    linkText: Learn more
  - icon: 💾
    title: Filter state persistence
    details: Remember a filter across requests, turn it into a shareable permalink, or store it as a named preset — all opt-in.
    link: /features/persistence
    linkText: Read the guide
  - icon: 🔍
    title: Web profiler integration
    details: See exactly which form field produced which DQL condition, and why a field was silently ignored.
    link: /features/debugging
    linkText: Debug a filter
---

<div class="home-extra">

## Up and running in minutes

```
composer require spiriitlabs/form-filter-bundle
```

Create a filter form using the bundle's filter types instead of the plain Symfony ones:

```php
<?php
namespace Project\Form\Filter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type as Filters;

class RankFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', Filters\TextFilterType::class);
        $builder->add('rank', Filters\NumberFilterType::class);
    }
}
```

Then build the query from the submitted form:

```php
class DefaultController extends AbstractController
{
    public function __invoke(
        Request $request,
        FormFactoryInterface $formFactory,
        EntityManagerInterface $em,
        FilterBuilderUpdater $filterBuilderUpdater
    ): Response
    {
        $form = $formFactory->create(RankFilterType::class);

        $form->handleRequest($request);

        $filterBuilder = $em
            ->getRepository(MyEntity::class)
            ->createQueryBuilder('e');

        $filterBuilderUpdater->addFilterConditions($form, $filterBuilder);

        // now look at the DQL =)
        dump($filterBuilder->getDql());

        return $this->render('testFilter.html.twig', [
            'form' => $form,
        ]);
    }
}
```

</div>
