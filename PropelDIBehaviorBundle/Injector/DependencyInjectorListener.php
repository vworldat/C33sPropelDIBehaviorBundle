<?php

namespace C33s\PropelDIBehaviorBundle\Injector;

use Glorpen\Propel\PropelBundle\Events\ModelEvent;
use Glorpen\Propel\PropelBundle\Events\QueryEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

class DependencyInjectorListener
{
    /**
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Called during any Propel model's __construct()
     *
     * @param ModelEvent $event
     */
    public function onModelConstruct(ModelEvent $event)
    {
        $model = $event->getModel();

        if ($model instanceof DependencyInjectorInterface)
        {
            $this->injectDependecies($model);
        }
    }

    /**
     * Called during any Propel query's __construct()
     *
     * @param QueryEvent $event
     */
    public function onQueryConstruct(QueryEvent $event)
    {
        $query = $event->getQuery();

        if ($query instanceof DependencyInjectorInterface)
        {
            $this->injectDependecies($query);
        }
    }

    /**
     * Injecting any required services as anonymous callback function.
     *
     * @param DependencyInjectorInterface $injectable
     */
    protected function injectDependecies(DependencyInjectorInterface $injectable)
    {
        foreach ($injectable->getDependencyNamesToInject() as $name)
        {
            $injectable->setInjectedDependencyCallable($name, function() use ($name) { return $this->getServiceOrParameter($name); });
        }
    }

    /**
     * Get a service or parameter from the dependency injection container by name.
     * Names starting with % are requested as parameters. Parameter names also may end with %.
     *
     * @throws InvalidArgumentException     If the given service or parameter does not exist
     *
     * @param string $name
     *
     * @return mixed
     */
    protected function getServiceOrParameter($name)
    {
        if (0 === strpos($name, '%'))
        {
            return $this->container->getParameter(trim($name, '%'));
        }

        return $this->container->get($name);
    }
}
