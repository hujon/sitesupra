<?php

namespace Supra\Proxy;

/**
 * THIS CLASS WAS GENERATED BY THE DOCTRINE ORM. DO NOT EDIT THIS FILE.
 */
class SupraControllerPagesEntityTemplateProxy extends \Supra\Controller\Pages\Entity\Template implements \Doctrine\ORM\Proxy\Proxy
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

            if (method_exists($this, "__wakeup")) {
                // call this after __isInitialized__to avoid infinite recursion
                // but before loading to emulate what ClassMetadata::newInstance()
                // provides.
                $this->__wakeup();
            }

            if ($this->_entityPersister->load($this->_identifier, $this) === null) {
                throw new \Doctrine\ORM\EntityNotFoundException();
            }
            unset($this->_entityPersister, $this->_identifier);
        }
    }
    
    
    public function addTemplateLayout(\Supra\Controller\Pages\Entity\TemplateLayout $templateLayout)
    {
        $this->__load();
        return parent::addTemplateLayout($templateLayout);
    }

    public function getTemplateLayouts()
    {
        $this->__load();
        return parent::getTemplateLayouts();
    }

    public function addLayout($media, \Supra\Controller\Pages\Entity\Layout $layout)
    {
        $this->__load();
        return parent::addLayout($media, $layout);
    }

    public function getLayout($media = 'screen')
    {
        $this->__load();
        return parent::getLayout($media);
    }

    public function getTemplateHierarchy()
    {
        $this->__load();
        return parent::getTemplateHierarchy();
    }

    public function getId()
    {
        $this->__load();
        return parent::getId();
    }

    public function getPlaceHolders()
    {
        $this->__load();
        return parent::getPlaceHolders();
    }

    public function getDataCollection()
    {
        $this->__load();
        return parent::getDataCollection();
    }

    public function getData($locale)
    {
        $this->__load();
        return parent::getData($locale);
    }

    public function setData(\Supra\Controller\Pages\Entity\Abstraction\Data $data)
    {
        $this->__load();
        return parent::setData($data);
    }

    public function addPlaceHolder(\Supra\Controller\Pages\Entity\Abstraction\PlaceHolder $placeHolder)
    {
        $this->__load();
        return parent::addPlaceHolder($placeHolder);
    }

    public function getLeftValue()
    {
        $this->__load();
        return parent::getLeftValue();
    }

    public function getRightValue()
    {
        $this->__load();
        return parent::getRightValue();
    }

    public function getLevel()
    {
        $this->__load();
        return parent::getLevel();
    }

    public function setLeftValue($left)
    {
        $this->__load();
        return parent::setLeftValue($left);
    }

    public function setRightValue($right)
    {
        $this->__load();
        return parent::setRightValue($right);
    }

    public function setLevel($level)
    {
        $this->__load();
        return parent::setLevel($level);
    }

    public function moveLeftValue($diff)
    {
        $this->__load();
        return parent::moveLeftValue($diff);
    }

    public function moveRightValue($diff)
    {
        $this->__load();
        return parent::moveRightValue($diff);
    }

    public function moveLevel($diff)
    {
        $this->__load();
        return parent::moveLevel($diff);
    }

    public function treeChangeTrigger()
    {
        $this->__load();
        return parent::treeChangeTrigger();
    }

    public function getNestedSetRepositoryClassName()
    {
        $this->__load();
        return parent::getNestedSetRepositoryClassName();
    }

    public function __call($method, $arguments)
    {
        $this->__load();
        return parent::__call($method, $arguments);
    }

    public function removeTrigger()
    {
        $this->__load();
        return parent::removeTrigger();
    }

    public function free()
    {
        $this->__load();
        return parent::free();
    }

    public function isBlockPropertyEditable(\Supra\Controller\Pages\Entity\BlockProperty $blockProperty)
    {
        $this->__load();
        return parent::isBlockPropertyEditable($blockProperty);
    }

    public function isBlockEditable(\Supra\Controller\Pages\Entity\Abstraction\Block $block)
    {
        $this->__load();
        return parent::isBlockEditable($block);
    }

    public function isBlockManageable(\Supra\Controller\Pages\Entity\Abstraction\Block $block)
    {
        $this->__load();
        return parent::isBlockManageable($block);
    }

    public function isPlaceHolderEditable(\Supra\Controller\Pages\Entity\Abstraction\PlaceHolder $placeHolder)
    {
        $this->__load();
        return parent::isPlaceHolderEditable($placeHolder);
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

    public function equals(\Supra\Controller\Pages\Entity\Abstraction\Entity $entity = NULL)
    {
        $this->__load();
        return parent::equals($entity);
    }


    public function __sleep()
    {
        return array('__isInitialized__', 'id', 'data', 'placeHolders', 'left', 'right', 'level', 'templateLayouts');
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