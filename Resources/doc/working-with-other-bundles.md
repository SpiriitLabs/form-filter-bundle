[6] Working with other bundles
==============================

i. KNP Paginator example
-----------------

[KNP Paginator](https://github.com/KnpLabs/KnpPaginatorBundle) example based on the [simple example](working-with-the-bundle.md#i-simple-example).

```php
<?php
// DefaultController.php
namespace Project\Bundle\SuperBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Project\Bundle\SuperBundle\Filter\ItemFilterType;

class DefaultController extends Controller
{
    public function __construct(private FilterBuilderUpdaterInterface $filterBuilderUpdater)
    {
    }
        
    public function testFilterAction(Request $request)
    {
        // initialize a query builder
        $filterBuilder = $this->myRepository
            ->createQueryBuilder('e');
    
        $form = $this->formFactory->create(new ItemFilterType());

        if ($request->query->has($form->getName())) {
            // manually bind values from the request
            $form->submit($request->query->all($form->getName()));

            // build the query from the given form object
            $this->filterBuilderUpdater->addFilterConditions($form, $filterBuilder);
        }

        $query = $filterBuilder->getQuery();

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->get('page', 1)/*page number*/,
            10/*limit per page*/
        );

        return $this->render('ProjectSuperBundle:Default:testFilter.html.twig', array(
            'form' => $form->createView(),
            'pagination' => $pagination
        ));
    }
}
```

ii. PagerFanta example
----------------------

[PagerFanta](https://github.com/BabDev/Pagerfanta) example based on the [simple example](working-with-the-bundle.md#i-simple-example).

```php
<?php
// DefaultController.php
namespace Project\Bundle\SuperBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Project\Bundle\SuperBundle\Filter\ItemFilterType;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;

class DefaultController extends Controller
{
    public function __construct(private FilterBuilderUpdaterInterface $filterBuilderUpdater)
    {
    }
    
    public function testFilterAction(Request $request)
    {
        $form = $this->formFactory->create(new ItemFilterType());

        if ($request->query->has($form->getName())) {
            // manually bind values from the request
            $form->submit($request->query->get($form->getName()));

            // build the query from the given form object
            $this->filterBuilderUpdater->addFilterConditions($form, $filterBuilder);
        }

        $query = $filterBuilder->getQuery();

        $pager = $this->paginateWithFilters(
            new ListOptions([
                'page' => $request->query->get('page', '1'),
                'limit' => $request->query->get('per_page', '100'),
                'sorting' => $request->query->get('sorting') ?? [
                    'status' => 'ASC',
                ],
            ]),
            $form,
            $this->myRepository->getAllQueryBuilder(),
        );

        return $this->render('ProjectSuperBundle:Default:testFilter.html.twig', array(
            'form' => $form->createView(),
            'pager' => $pager
        ));
    }
}

// A helper class
class ListOptions extends AbstractOptions
{
    protected function getDefaultsOptions(): array
    {
        return [
            'page' => 1,
            'limit' => 100,
            'sorting' => [],
        ];
    }
}

```
