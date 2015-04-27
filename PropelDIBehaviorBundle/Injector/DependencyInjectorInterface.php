<?php

namespace C33s\PropelDIBehaviorBundle\Injector;

interface DependencyInjectorInterface
{
    /**
     * Get injected dependency by name. This will instantiate services on demand if needed.
     *
     * @return mixed
     */
    public function getInjectedDependency($name);

    /**
     * Inject a dependency callable into the class, allowing for on-demand service instantiation.
     *
     * @param string    $name
     * @param callable  $service
     *
     * @return self
     */
    public function setInjectedDependencyCallable($name, callable $serviceCallable);

    /**
     * Get names of dependencies that this class requires
     *
     * @return array
     */
    public function getDependencyNamesToInject();
}
