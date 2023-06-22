[8] Advanced usage with PagerFanta
====================================

```php
// your controller

    public function __invoke(Request $request): Response
    {
        $form = $this->createForm(FilterType::class, null, [
            'method' => Request::METHOD_GET,
        ]);
        $form->handleRequest($request);

        $pager = $this->helperClass->paginateWithFilters(
            new ListOptions([
                'page' => $request->query->get('page', '1'),
                'limit' => $request->query->get('per_page', '20'),
                'sorting' => ['date' => 'ASC'],
            ]),
            $form,
            $this->getQueryBuilder(),
        );

        return $this->render('demo/test.html.twig', [
            'pager' => $pager,
            'form' => $form,
        ]);
    }
```

```php
    // your helper class 
    public function getAllQb(): QueryBuilder
    {
        return $this->createQueryBuilder($this->getAlias());
    }
    
    public function paginateWithFilters(
        ListOptions $options,
        FormInterface $form = null,
        QueryBuilder $qb = null,
    ): Pagerfanta {
        try {
            $sorting = $options->get('sorting');

            if (!\is_array($sorting)) {
                throw new \RuntimeException('need array');
            }
        } catch (\RuntimeException $e) {
            $sorting = [];
        }

        if (null === $form) {
            $queryBuilder = $qb ?? $this->getCollectionQueryBuilder();

            if (!empty($sorting)) {
                $this->applySorting($queryBuilder, $sorting);
            }

            return $this->paginate($options, $queryBuilder);
        }

        return $this->paginate(
            $options,
            $this->getFilteredResource($form, $sorting, $qb),
        );
    }
    
    public function getPager(QueryBuilder $qb, int $page = 1, int $maxPerPage = 10): Pagerfanta
    {
        $pager = new Pagerfanta(new QueryAdapter($qb));
        $pager->setMaxPerPage($maxPerPage);
        $pager->setCurrentPage($page);

        return $pager;
    }

    public function paginate(ListOptions $options, QueryBuilder $queryBuilder = null): Pagerfanta
    {
        if (null === $queryBuilder) {
            $queryBuilder = $this->getAllQb();
        }

        try {
            $page = $options->get('page');
            $limit = $options->get('limit');

            if (!\is_int($page) || !\is_int($limit)) {
                throw new \RuntimeException('int parameter');
            }
        } catch (\RuntimeException $e) {
            $page = 1;
            $limit = 10;
        }

        return $this->getPager(
            $queryBuilder,
            $page,
            $limit,
        );
    }

    public function getFilteredResource(
        FormInterface $form,
        ?array $sorting = [],
        QueryBuilder $qb = null,
    ): QueryBuilder {
        $queryBuilder = $qb ?? $this->getCollectionQueryBuilder();

        $this->applyFilters($form, $queryBuilder);

        if (!empty($sorting)) {
            $this->applySorting($queryBuilder, $sorting);
        }

        return $queryBuilder;
    }

    protected function getPropertyName(string $name): string
    {
        if (!str_contains($name, '.')) {
            return $this->getAlias().'.'.$name;
        }

        return $name;
    }

    protected function applySorting(QueryBuilder $queryBuilder, array $sorting = []): void
    {
        foreach ($sorting as $property => $order) {
            if (!empty($order)) {
                $queryBuilder->addOrderBy($this->getPropertyName($property), $order);
            }
        }
    }

    protected function getAlias(): string
    {
        return 'o';
    }

    protected function applyFilters(FormInterface $form, QueryBuilder $queryBuilder): void
    {
        if (null === $form->getData()) {
            return;
        }
        
        $this->filterBuilderUpdater->addFilterConditions($form, $queryBuilder);
    }
```

Bonus: Twig list component
==========================

```php
#[AsTwigComponent(name: 'crud-list', template: 'components/crud/list.html.twig')]
class ListComponent
{
    public ?FormView $form = null;
    public ?Pagerfanta $pager = null;

    /**
     * @param mixed[] $data
     *
     * @return mixed[]
     */
    #[PreMount]
    public function preMount(array $data): array
    {
        $resolver = new OptionsResolver();
        $resolver->setDefault('form', null);
        $resolver->setRequired('pager');
        $resolver->setAllowedTypes('form', ['null', FormView::class]);
        $resolver->setAllowedTypes('pager', Pagerfanta::class);

        return $resolver->resolve($data);
    }
}
```

-Bootstrap html-

```html
{% trans_default_domain 'admin' %}

{% block preList '' %}

{% if form is not none %}
    <div class="card mb-3">
        <div class="card-body">
            {{ form_start(form, {attr: {class: 'form-inline'}}) }}
            <div class="row">
                {% block formFilterRows '' %}
            </div>
            {{ form_end(form) }}
        </div>
    </div>
{% endif %}

<div class="card">
    <div class="card-body">
        <div class="table-responsive-lg">
            {{ pager.count }} results
            <table class="table table-centered table-striped">
                <thead>
                <tr>
                    {% block tableTh '' %}
                    {% block tableAction %}
                        <th>actions</th>
                    {% endblock %}
                </tr>
                </thead>

                <tbody>
                {% for object in pager.currentPageResults %}
                    <tr>
                        {% block tableTd '' %}

                        {% block tableEdit %}
                            <td></td>
                        {% endblock %}
                    </tr>
                {% endfor %}
                </tbody>
            </table>
            {% block pagination %}
                <div class="pagerfanta mt-4 pagination justify-content-end">
                    {{ pagerfanta(pager) }} {# extension from bundle pagerfanta #}
                </div>
            {% endblock %}
        </div>
    </div>
</div>
```

-usage-

```html
{% block body %}
    {% component 'crud-list' with {
        pager: pager,
        form: form,
        } %}

        {% block formFilterRows %}
            <div class="col-lg-3">
                {{ form_widget(form.name) }}
            </div>
            <div class="col-lg-3">
                {{ form_widget(form.email) }}
            </div>
        {% endblock %}
    
        {% block tableTh %}
            <th>name</th>
            <th>email</th>
        {% endblock %}
    
        {% block tableTd %}
            {# @var object \My\Folder\Object #}
    
            <td>{{ object.name }}</td>
            <td>{{ object.email }}</td>
        {% endblock %}
    
        {% block tableEdit %}
            <td>
                <a class="icon-edit" href="{{ path('object_edit', {id: object.id}) }}">
                    edit
                </a>
            </td>
        {% endblock %}
    {% endcomponent %}
{% endblock %}
```
