<?php

namespace Hacfi\Bundle\ObjectBridgeBundle\EventListener;

use Doctrine\Common\EventSubscriber;
//use Doctrine\ODM\PHPCR\Event\LifecycleEventArgs;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\Common\Util\ClassUtils;

use Hacfi\Bundle\ObjectBridgeBundle\Mapping\Driver\AnnotationDriver;

class DoctrinePhpcrEventSubscriber implements EventSubscriber
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var
     */
    protected $doctrine;

    /**
     * @var AnnotationDriver
     */
    protected $metadata;


    /**
     * Constructor.
     *
     * @param ContainerInterface $container The service container instance
     * @param                    $doctrine
     * @param                    $metadata
     */
    public function __construct($container, $doctrine, $metadata)
    {
        $this->container = $container;
        $this->doctrine  = $doctrine;
        $this->metadata  = $metadata;
    }


    public function getSubscribedEvents()
    {
        return array(
            'prePersist',
            'postPersist',
            'preUpdate',
            'postUpdate',
            'postLoad',
        );
    }


    public function prePersist(LifecycleEventArgs $args)
    {
        $this->transform($args->getObject(), $args->getObjectManager());
    }


    public function postPersist(LifecycleEventArgs $args)
    {
        $this->reverseTransform($args->getObject(), $args->getObjectManager());
    }


    public function preUpdate(LifecycleEventArgs $args)
    {
        $this->transform($args->getObject(), $args->getObjectManager());
    }


    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->reverseTransform($args->getObject(), $args->getObjectManager());
    }


    public function postLoad(LifecycleEventArgs $args)
    {
        $this->reverseTransform($args->getObject(), $args->getObjectManager());
    }


    private function transform($document, $documentManager)
    {
        $documentClass     = ClassUtils::getClass($document);
        $bridgedProperties = $this->metadata->loadMetadataForClass($documentClass);

        foreach ($bridgedProperties as $property => $entityMappingData) {
            // @TODO: Validate type here?
            if (false && $entityMappingData['type']) {
                throw new \Exception(sprintf('Unknown target "%s". Did you set up the specified persistance service?', $entityMappingData['type']));
            }

            $em = $this->doctrine->getManager($entityMappingData['manager']);
            list($namespaceAlias, $simpleClassName) = explode(':', $entityMappingData['name']);
            $realClassName = $em->getConfiguration()->getEntityNamespace($namespaceAlias).'\\'.$simpleClassName;

            /** @var \Doctrine\ORM\Mapping\ClassMetadata $entityMetaData */
            $entityMetaData = $em->getClassMetadata($realClassName);

            /** @var \Doctrine\ODM\PHPCR\Mapping\ClassMetadata $documentMetaData */
            $documentMetaData = $documentManager->getClassMetadata($documentClass);

            $entity = $documentMetaData->getFieldValue($document, $property);

            if (!is_object($entity)) {
                // Null or already serialized
                continue;
            }

            $identifier = $entityMetaData->getIdentifierValues($entity);
            $documentMetaData->setFieldValue($document, $property, serialize($identifier));
        }
    }


    private function reverseTransform($document, $documentManager)
    {
        $documentClass     = ClassUtils::getClass($document);
        $bridgedProperties = $this->metadata->loadMetadataForClass($documentClass);

        foreach ($bridgedProperties as $property => $entityMappingData) {
            // @TODO: Validate type here?
            if (false && $entityMappingData['type']) {
                throw new \Exception(sprintf('Unknown target "%s". Did you set up the specified persistance service?', $entityMappingData['type']));
            }

            $em = $this->doctrine->getManager($entityMappingData['manager']);
            list($namespaceAlias, $simpleClassName) = explode(':', $entityMappingData['name']);
            $realClassName = $em->getConfiguration()->getEntityNamespace($namespaceAlias).'\\'.$simpleClassName;

            /** @var \Doctrine\ORM\Mapping\ClassMetadata $entityMetaData */
            $entityMetaData = $em->getClassMetadata($realClassName);

            /** @var \Doctrine\ODM\PHPCR\Mapping\ClassMetadata $documentMetaData */
            $documentMetaData = $documentManager->getClassMetadata($documentClass);

            $entityReference = $documentMetaData->getFieldValue($document, $property);

            if (empty($entityReference)) {
                // Null
                continue;
            }

            $identifer = unserialize($entityReference);

            if ($identifer === false || !is_array($identifer)) {
                // Not a serialized array
                continue;
            }
            $entityId = array_shift($identifer);

            $reference = $em->getReference($realClassName, $entityId);

            if (!is_null($reference)) {
                $documentMetaData->setFieldValue($document, $property, $reference);
            }
        }
    }
}
