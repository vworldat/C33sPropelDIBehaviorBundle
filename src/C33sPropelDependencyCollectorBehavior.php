<?php

require_once(__DIR__.'/C33sPropelDependencyInjectorBehavior.php');

/**
 * This class only acts as collection so that several instances of this behavior can be used.
 *
 * The ExozetPropelBehaviorServiceInjectorBase behavior then collects all services to inject
 * from behaviors like this.
 *
 * @author david
 *
 */
class C33sPropelDependencyCollectorBehavior extends Behavior
{
    protected $parameters = array(
        'model' => '',
        'query' => '',
    );

    /**
     * @var int
     */
    protected $tableModificationOrder = 50;

    /**
     * Sets the table this behavior is applied to
     *
     * @param Table $table the table this behavior is applied to
     */
    public function setTable(Table $table)
    {
        if (!$table->hasBehavior('_c33s_dependency_injector'))
        {
            $table->addBehavior(new C33sPropelDependencyInjectorBehavior());
        }

        parent::setTable($table);
    }

    protected function getDependenciesToInject($type)
    {
        $dependecies = array();

        $deps = explode(',', $this->getParameter($type));
        $deps = array_filter(array_map('trim', $deps));

        foreach ($deps as $dependency)
        {
            $method = null;
            $typehint = 'mixed';

            if (false !== strpos($dependency, ':'))
            {
                list($dependecy, $method) = explode(':', $dependency, 2);
            }
            if (false !== strpos($method, ':'))
            {
                list($method, $typehint) = explode(':', $method, 2);
            }

            $dependecies[$dependecy] = array(
                'method' => $method,
                'typehint' => $typehint,
            );
        }

        return $dependecies;
    }

    /**
     * Get all dependencies to inject into model classes.
     *
     * Returns an array:
     * $service_name => array('methodToAddToModel', 'Typehint to use in method return phpdoc')
     *
     * methodToAddToModel may be null if there is no method to be added
     *
     * @return array
     */
    public function getModelDependenciesToInject()
    {
        return $this->getDependenciesToInject('model');
    }

    /**
     * Get all dependencies to inject into query classes.
     *
     * Returns an array:
     * $service_name => array('methodToAddToModel', 'Typehint to use in method return phpdoc')
     *
     * methodToAddToModel may be null if there is no method to be added
     *
     * @return array
     */
    public function getQueryDependenciesToInject()
    {
        return $this->getDependenciesToInject('query');
    }
}
