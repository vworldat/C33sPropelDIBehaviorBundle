<?php

class C33sPropelDependencyInjectorBehavior extends Behavior
{
    protected $dependencies;

    /**
     * @var int
     */
    protected $tableModificationOrder = 100;

    /**
     * Add the create_column and update_columns to the current table
     */
    public function modifyTable()
    {
        foreach ($this->getTable()->getBehaviors() as $behavior)
        {
            if (!($behavior instanceof C33sPropelDependencyCollectorBehavior))
            {
                continue;
            }

            foreach ($behavior->getModelDependenciesToInject() as $name => $data)
            {
                $this->addDependency('model', $name, $data['method'], $data['typehint']);
            }
            foreach ($behavior->getQueryDependenciesToInject() as $name => $data)
            {
                $this->addDependency('query', $name, $data['method'], $data['typehint']);
            }
        }
    }

    protected function addDependency($type, $name, $method, $typehint)
    {
        $this->dependencies[$type][$name] = array(
            'method' => $method,
            'typehint' => $typehint,
        );
    }

    protected function getDependencies($type)
    {
        return isset($this->dependencies[$type]) ? $this->dependencies[$type] : array();
    }

    public function objectAttributes(OMBuilder $builder)
    {
        return $this->getGeneralAttributes($builder, 'model');
    }

    public function queryAttributes(OMBuilder $builder)
    {
        return $this->getGeneralAttributes($builder, 'query');
    }

    public function objectMethods(OMBuilder $builder)
    {
        return
            $this->getGeneralMethods($builder, 'model').
            $this->getDependencyMethods($builder, 'model')
        ;
    }

    public function queryMethods(OMBuilder $builder)
    {
        return
            $this->getGeneralMethods($builder, 'query').
            $this->getDependencyMethods($builder, 'query')
        ;
    }

    /**
     * Attributes to be included in all affected classes.
     *
     * @param OMBuilder $builder
     * @param string $type
     *
     * @return string
     */
    protected function getGeneralAttributes(OMBuilder $builder, $type)
    {
        $attributes = <<<EOF

/**
 * @var array
 */
protected \$injectedDependencies = array();

EOF;

        return $attributes;
    }

    /**
     * Methods to be included in all affected classes.
     *
     * @param OMBuilder $builder
     * @param string $type
     *
     * @return string
     */
    protected function getGeneralMethods(OMBuilder $builder, $type)
    {
        $builder->declareClass('C33s\\PropelDIBehaviorBundle\\Injector\\DependencyInjectorInterface');

        $names = array_keys($this->getDependencies($type));
        $names = var_export($names, true);

        $methods = <<<EOF

/**
 * Get names of dependencies that this class requires
 *
 * @return array
 */
public function getDependencyNamesToInject()
{
    return {$names};
}

/**
 * Inject a dependency callable into the class, allowing for on-demand service instantiation.
 *
 * @param string    \$name
 * @param callable  \$dependencyCallable
 *
 * @return self
 */
public function setInjectedDependencyCallable(\$name, callable \$dependencyCallable)
{
    \$this->injectedDependencies[\$name] = \$dependencyCallable;

    return \$this;
}

/**
 * Get injected dependency by name. This will instantiate services on demand if needed.
 *
 * @return mixed
 */
public function getInjectedDependency(\$name)
{
    if (!array_key_exists(\$name, \$this->injectedDependencies) || !is_callable(\$this->injectedDependencies[\$name]))
    {
        throw new \InvalidArgumentException('Trying to get unknown dependency: '.\$name);
    }

    \$callable = \$this->injectedDependencies[\$name];

    return \$callable();
}

/**
 * Remove injected dependency callbacks during serialization.
 * Fix for  Serialization of 'Closure' is not allowed
 *
 * @return array
 */
public function __sleep()
{
    return array_diff(parent::__sleep(), array('injectedDependencies'));
}

EOF;

        return $methods;
    }

    protected function getDependencyMethods(OMBuilder $builder, $type)
    {
        $methods = '';

        foreach ($this->getDependencies($type) as $name => $data)
        {
            if (empty($data['method']))
            {
                continue;
            }

            $methods .= <<<EOF

/**
 * Get injected {$name} dependency (service or parameter)
 *
 * @return {$data['typehint']}
 */
public function {$data['method']}()
{
    return \$this->getInjectedDependency('{$name}');
}

EOF;

        }

        return $methods;
    }

    public function objectFilter(&$script)
    {
        $this->addInterfaceToModelClass($script, 'DependencyInjectorInterface');
    }

    public function queryFilter(&$script)
    {
        $this->addInterfaceToQueryClass($script, 'DependencyInjectorInterface');
    }

    protected function addInterfaceToModelClass(&$script, $interface)
    {
        $script = preg_replace('#(implements Persistent)#', '$1, '.$interface, $script);
    }

    protected function addInterfaceToQueryClass(&$script, $interface)
    {
        if (preg_match('#extends ModelCriteria implements#', $script))
        {
            $script = preg_replace('#(extends ModelCriteria implements)#', '$1 '.$interface.',', $script);
        }
        else
        {
            $script = preg_replace('#(extends ModelCriteria)#', '$1 implements '.$interface, $script);
        }
    }

    /**
     * Hard-coded so there can always be only one instance per table.
     *
     * @see Behavior::getName()
     */
    public function getName()
    {
        return '_c33s_dependency_injector';
    }
}
