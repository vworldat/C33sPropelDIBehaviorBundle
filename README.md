# C33sPropelDIBehaviorBundle

Clean Symfony2 dependency injection for your Propel models

Installation
------------

This bundle depends on [`GlorpenPropelBundle`](https://bitbucket.org/glorpen/glorpenpropelbundle). **Install that one first**.

Require [`c33s/propel-di-behavior-bundle`](https://packagist.org/packages/c33s/propel-di-behavior-bundle)
in your `composer.json` file:


```js
{
    "require": {
        "c33s/propel-di-behavior-bundle": "@stable",
    }
}
```

Add propel behaviors to your propel config:

```yml
# app/config/config.yml

propel:
    # ...
    
    behaviors:
        c33s_di:        vendor.c33s.propel-di-behavior-bundle.src.C33sPropelDependencyCollectorBehavior
        
        # Optional: add another "instance" for global usage that does not interfere with model-specific behavior instances
        c33s_di_global: vendor.c33s.propel-di-behavior-bundle.src.C33sPropelDependencyCollectorBehavior

```

Register the bundle in `app/AppKernel.php`:

```php

    // app/AppKernel.php

    public function registerBundles()
    {
        return array(
            // ...

            new C33s\PropelDIBehaviorBundle\C33sPropelDIBehaviorBundle(),
        );
    }

```

Usage
-----

Add behavior to your propel models - either globally (use the `c33s_di_global` name for that) or to a specific model.
You may inject Symfony2 services or parameters into any `Model` or `Query` class. Each definition consists of the service
or parameter name to inject (enclose parameters in %-chars) followed by optional getter methods and type hints for this methods, separated
by colons:

* `logger:getLogger:\Psr\Log\LoggerInterface` injects the Symfony2 logger service, making it accessible by `$model->getLogger()` 
(or $query->getLogger()) and providing `\Psr\Log\LoggerInterface` as a type hint for that getter
* `logger` injects the Symfony2 logger service without an explicit getter. Use `$model->getInjectedDependency('logger')` to access it.
* `%locale%:getLocale():string` injects the `locale` parameter, providing a getLocale() method for accessing it. 

### Example schema

```xml
<!-- my/Bundle/Resources/config/schema.xml -->

    <!-- this will inject the logger into ALL Propel model and query instances -->
    <behavior name="c33s_di_global">
        <parameter name="model" value="logger:getLogger:\Psr\Log\LoggerInterface" />
        <parameter name="query" value="logger:getLogger:\Psr\Log\LoggerInterface" />
    </behavior>

    <table name="book">
        <!-- this will inject the mailer and session into the Book instances and request_stack into BookQuery instances -->
        <behavior name="c33s_di">
            <parameter name="model" value="
                mailer:getMailer:\Swift_Mailer,
                session:getSession:\Symfony\Component\HttpFoundation\Session\Session,
            " />
            <parameter name="query" value="request_stack:getRequestStack:\Symfony\Component\HttpFoundation\RequestStack" />
        </behavior>
        
        <...>
    </table>
```

How it works
------------

`C33sPropelDIBehaviorBundle` registers event listeners for `model.create` and `query.create` that are processed by `GlorpenPropelBundle`. Upon each
model or query creation anonymous callbacks for each service will be injected into the classes, making sure that only the services that were specified
in the schema can be accessed. The callback furthermore ensures that no services will be instantiated without actually being used.

So far this is the cleanest way I have found to inject specific Symfony2 dependencies into Propel models without messing around with the full DI container.
