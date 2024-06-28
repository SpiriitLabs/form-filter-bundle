[2] Configuration
=================

Twig
----

You only need to add the following lines in your `config/packages/twig.yaml`. This file contains the template blocks for the filter types.

```yaml
# config/packages/twig.yaml
twig:
    form_themes:
        - '@SpiriitFormFilter/Form/form_div_layout.html.twig'
```

Bundle's options
----------------

* Enable listeners you need:

The bundle provides some listener to apply conditions on Doctrine ORM, DBAL and MongoDB query builders.
By default only Doctrine ORM listeners are enabled.

You can create a file `spiriit_form_filter.yaml`

```yaml
# config/packages/spiriit_form_filter.yaml
spiriit_form_filter:
    listeners:
        doctrine_orm: true
        doctrine_dbal: false
```

* Case insensitivity:

If your RDBMS is Postgres, case insensitivity will be forced for LIKE comparisons.
If you want to avoid that, there is a configuration option:

```yaml
# config/packages/spiriit_form_filter.yaml
spiriit_form_filter:
    force_case_insensitivity: false
    encoding: ~ # Encoding for case insensitive LIKE comparisons. For example: UTF-8
```

If you use Postgres and you want your LIKE comparisons to be case sensitive
anyway, set it to `true`.

* Query builder method:

**For Doctrine ORM and DBAL only.**
This option will define which method to use on the (doctrine) query builder to add the **entire** condition computed from the form type (this option is not about the operator between each filter condition).
By default this option is set to `and`, so the bundle will call the `andWhere()` method to set the entire condition on the doctrine query builder.
If you set it to `null` or `or`, the bundle will use the `where()` or `orWhere()` method to set the entire condition.
And so if the value is `null` it will override the existing where clause (in case of you initialized one on the query builder).

```yaml
# config/packages/spiriit_form_filter.yaml
spiriit_form_filter:
    where_method: ~  # null | and | or
```

* Globally define the `condition_pattern` for the `TextFilterType`:

This option allow you to define the default text pattern the `TextFilterType` will use.

```yaml
# config/packages/spiriit_form_filter.yaml
spiriit_form_filter:
    condition_pattern: text.starts
```
Available values for this option are: `text.contains`, `text.starts`, `text.ends`, `text.equal`.

***

Next: [3. Provided form types](provided-types.md)
