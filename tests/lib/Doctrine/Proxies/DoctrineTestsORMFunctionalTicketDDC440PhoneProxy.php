<?php

namespace Doctrine\Tests\Proxies;

/**
 * THIS CLASS WAS GENERATED BY THE DOCTRINE ORM. DO NOT EDIT THIS FILE.
 */
class DoctrineTestsORMFunctionalTicketDDC440PhoneProxy extends \Doctrine\Tests\ORM\Functional\Ticket\DDC440Phone implements \Doctrine\ORM\Proxy\Proxy
{
    private $_entityPersister;
    private $_identifier;
    public $__isInitialized__ = false;
    public function __construct($entityPersister, $identifier)
    {
        $this->_entityPersister = $entityPersister;
        $this->_identifier = $identifier;
    }
    private function _load()
    {
        if (!$this->__isInitialized__ && $this->_entityPersister) {
            $this->__isInitialized__ = true;
            if ($this->_entityPersister->load($this->_identifier, $this) === null) {
                throw new \Doctrine\ORM\EntityNotFoundException();
            }
            unset($this->_entityPersister);
            unset($this->_identifier);
        }
    }

    
    public function setNumber($value)
    {
        $this->_load();
        return parent::setNumber($value);
    }

    public function getNumber()
    {
        $this->_load();
        return parent::getNumber();
    }

    public function setClient(\Doctrine\Tests\ORM\Functional\Ticket\DDC440Client $value, $update_inverse = true)
    {
        $this->_load();
        return parent::setClient($value, $update_inverse);
    }

    public function getClient()
    {
        $this->_load();
        return parent::getClient();
    }

    public function getId()
    {
        $this->_load();
        return parent::getId();
    }

    public function setId($value)
    {
        $this->_load();
        return parent::setId($value);
    }


    public function __sleep()
    {
        if (!$this->__isInitialized__) {
            throw new \RuntimeException("Not fully loaded proxy can not be serialized.");
        }
        return array('id', 'client', 'number');
    }
}