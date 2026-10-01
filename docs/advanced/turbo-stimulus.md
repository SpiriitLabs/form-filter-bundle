---
description: Filter a list without reloading the page, using Symfony UX Turbo and a small Stimulus controller.
---

# Live filtering with Turbo and Stimulus

A filter form is a plain GET form, which makes it a good fit for [Symfony UX Turbo](https://symfony.com/bundles/ux-turbo/current/index.html): the results are wrapped in a Turbo Frame, the form targets that frame, and only the results are refreshed. A tiny [Stimulus](https://symfony.com/bundles/StimulusBundle/current/index.html) controller submits the form while the user types.

No JavaScript is needed for the frame itself, and without JavaScript the form still works as a regular page submission.

```bash
composer require symfony/ux-turbo symfony/stimulus-bundle
```

## The controller

The form must use the GET method so the filter ends up in the URL. Disable CSRF protection: a search form changes no state.

```php
class UserListController extends AbstractController
{
    #[Route('/users', name: 'user_list')]
    public function __invoke(
        Request $request,
        UserRepository $userRepository,
        FilterBuilderUpdater $filterBuilderUpdater,
    ): Response {
        $form = $this->createForm(UserFilterType::class, null, [
            'method' => Request::METHOD_GET,
            'csrf_protection' => false,
        ]);
        $form->handleRequest($request);

        $queryBuilder = $userRepository->createQueryBuilder('u');
        $filterBuilderUpdater->addFilterConditions($form, $queryBuilder);

        return $this->render('user/list.html.twig', [
            'form' => $form,
            'users' => $queryBuilder->getQuery()->getResult(),
        ]);
    }
}
```

## The template

The form lives outside the frame, so it is never re-rendered and keeps its focus while the user types. `data-turbo-frame` makes the submission target the results frame, and `data-turbo-action="advance"` pushes the filtered URL in the browser history, so a filtered list can be bookmarked and shared.

```twig
{{ form_start(form, {attr: {
    'data-controller': 'filter-form',
    'data-action': 'input->filter-form#submit',
    'data-turbo-frame': 'results',
    'data-turbo-action': 'advance',
}}) }}
    {{ form_widget(form) }}
    <button type="submit">Filter</button>
{{ form_end(form) }}

<turbo-frame id="results" data-turbo-action="advance">
    <ul>
        {% for user in users %}
            <li>{{ user.name }}</li>
        {% endfor %}
    </ul>
</turbo-frame>
```

Links inside the frame, such as pagination links, also navigate only the frame. The `pagerfanta()` Twig function of [PagerfantaBundle](/advanced/pagerfanta) merges the current query string into every link it generates, so the filter survives a page change. For hand-written links, do the same:

```twig
<a href="{{ path('user_list', app.request.query.all|merge({page: 2})) }}">Next</a>
```

## The Stimulus controller

Create `assets/controllers/filter_form_controller.js`. It waits for the user to stop typing before submitting the form:

```js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = { delay: { type: Number, default: 300 } };

    submit() {
        clearTimeout(this.timeout);
        this.timeout = setTimeout(() => this.element.requestSubmit(), this.delayValue);
    }

    disconnect() {
        clearTimeout(this.timeout);
    }
}
```

`requestSubmit()` fires a real `submit` event, which Turbo intercepts, whereas `submit()` would bypass it and reload the whole page.

To change the delay, add `'data-filter-form-delay-value': 500` to the form attributes.

## Going further

The same list can be wrapped in a [Twig component](/advanced/pagerfanta#bonus-twig-list-component) so the frame, the table and the pagination are reused across screens.
