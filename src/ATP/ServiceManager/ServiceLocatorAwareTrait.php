<?php
/**
 * Created by PhpStorm.
 * User: Andy
 * Date: 10/25/2015
 * Time: 11:40 PM
 */

namespace ATP\ServiceManager;

trait ServiceLocatorAwareTrait
{
    private $sm = null;

    public function setServiceLocator(\Zend\ServiceManager\ServiceLocatorInterface $serviceLocator)
    {
        $this->sm = $serviceLocator;
    }

    /**
     * Get service locator
     *
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->sm;
    }

    protected function get($service)
    {
        return $this->sm->get($service);
    }
}