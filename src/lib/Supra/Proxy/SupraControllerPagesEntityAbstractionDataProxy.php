<?php

namespace Supra\Proxy;

/**
 * THIS CLASS WAS GENERATED BY THE DOCTRINE ORM. DO NOT EDIT THIS FILE.
 */
class SupraControllerPagesEntityAbstractionDataProxy extends \Supra\Controller\Pages\Entity\Abstraction\Data implements \Doctrine\ORM\Proxy\Proxy
{
    private $_entityPersister;
    private $_identifier;
    public $__isInitialized__ = false;
    public function __construct($entityPersister, $identifier)
    {
        $this->_entityPersister = $entityPersister;
        $this->_identifier = $identifier;
    }
    /** @private */
    public function __load()
    {
        if (!$this->__isInitialized__ && $this->_entityPersister) {
            $this->__isInitialized__ = true;
            if ($this->_entityPersister->load($this->_identifier, $this) === null) {
                throw new \Doctrine\ORM\EntityNotFoundException();
            }
            unset($this->_entityPersister, $this->_identifier);
        }
    }
    
    
    public function getId()
    {
        $this->__load();
        return parent::getId();
    }

    public function getLocale()
    {
        $this->__load();
        return parent::getLocale();
    }

    public function setTitle($title)
    {
        $this->__load();
        return parent::setTitle($title);
    }

    public function getTitle()
    {
        $this->__load();
        return parent::getTitle();
    }

    public function setMaster(\Supra\Controller\Pages\Entity\Abstraction\Page $master)
    {
        $this->__load();
        return parent::setMaster($master);
    }

    public function getMaster()
    {
        $this->__load();
        return parent::getMaster();
    }

    public function addBlockProperty(\Supra\Controller\Pages\Entity\BlockProperty $blockProperty)
    {
        $this->__load();
        return parent::addBlockProperty($blockProperty);
    }

    public function getRepository()
    {
        $this->__load();
        return parent::getRepository();
    }

    public function getProperty($name)
    {
        $this->__load();
        return parent::getProperty($name);
    }

    public function getDiscriminator()
    {
        $this->__load();
        return parent::getDiscriminator();
    }

    public function matchDiscriminator(\Supra\Controller\Pages\Entity\Abstraction\Entity $object, $strict = true)
    {
        $this->__load();
        return parent::matchDiscriminator($object, $strict);
    }

    public function __toString()
    {
        $this->__load();
        return parent::__toString();
    }

    public function equals(\Supra\Controller\Pages\Entity\Abstraction\Entity $entity)
    {
        $this->__load();
        return parent::equals($entity);
    }


    public function __sleep()
    {
        return array('__isInitialized__', 'id', 'locale', 'title', 'blockProperties', 'master');
    }

    public function __clone()
    {
        if (!$this->__isInitialized__ && $this->_entityPersister) {
            $this->__isInitialized__ = true;
            $class = $this->_entityPersister->getClassMetadata();
            $original = $this->_entityPersister->load($this->_identifier);
            if ($original === null) {
                throw new \Doctrine\ORM\EntityNotFoundException();
            }
            foreach ($class->reflFields AS $field => $reflProperty) {
                $reflProperty->setValue($this, $reflProperty->getValue($original));
            }
            unset($this->_entityPersister, $this->_identifier);
        }
        
    }
}