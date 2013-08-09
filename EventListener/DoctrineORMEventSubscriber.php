<?php

namespace Hacfi\Bundle\ObjectBridgeBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\Common\Util\ClassUtils;

use Hacfi\Bundle\ObjectBridgeBundle\Mapping\Driver\AnnotationDriver;

class DoctrineORMEventSubscriber implements EventSubscriber
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
        $this->transform($args->getEntity(), $args->getEntityManager());
    }


    public function postPersist(LifecycleEventArgs $args)
    {
        $this->reverseTransform($args->getEntity(), $args->getEntityManager());
    }


    public function preUpdate(LifecycleEventArgs $args)
    {
        $this->transform($args->getEntity(), $args->getEntityManager());
    }


    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->reverseTransform($args->getEntity(), $args->getEntityManager());
    }


    public function postLoad(LifecycleEventArgs $args)
    {
        $this->reverseTransform($args->getEntity(), $args->getEntityManager());
    }


    private function transform($entity, $em)
    {
        $entityClass       = ClassUtils::getClass($entity);
        $bridgedProperties = $this->metadata->loadMetadataForClass($entityClass);

        foreach ($bridgedProperties as $property => $documentMappingData) {
            // @TODO: Validate type here?
            if (false && $documentMappingData['type']) {
                throw new \Exception(sprintf('Unknown target "%s". Did you set up the specified persistance service?', $documentMappingData['type']));
            }

            $dm = $this->doctrine->getManager($documentMappingData['manager']);

            list($namespaceAlias, $simpleClassName) = explode(':', $documentMappingData['name']);
            $realClassName = $dm->getConfiguration()->getDocumentNamespace($namespaceAlias).'\\'.$simpleClassName;

            /** @var \Doctrine\ODM\PHPCR\Mapping\ClassMetadata $documentMetaData */
            $documentMetaData = $em->getClassMetadata($entityClass);

            /** @var \Doctrine\ORM\Mapping\ClassMetadata $entityMetaData */
            $entityMetaData = $em->getClassMetadata($realClassName);

            $document = $entityMetaData->getFieldValue($entity, $property);

            if (!is_object($document)) {
                // Null or already serialized
                continue;
            }

            $identifier = $documentMetaData->getIdentifierValues($document);
            $entityMetaData->setFieldValue($entity, $property, serialize($identifier));
        }
    }


    private function reverseTransform($entity, $em)
    {
        $entityClass       = ClassUtils::getClass($entity);
        $bridgedProperties = $this->metadata->loadMetadataForClass($entityClass);

        foreach ($bridgedProperties as $property => $documentMappingData) {
            // @TODO: Validate type here?
            if (false && $documentMappingData['type']) {
                throw new \Exception(sprintf('Unknown target "%s". Did you set up the specified persistance service?', $documentMappingData['type']));
            }

            $dm = $this->doctrine->getManager($documentMappingData['manager']);
            list($namespaceAlias, $simpleClassName) = explode(':', $documentMappingData['name']);
            $realClassName = $dm->getConfiguration()->getDocumentNamespace($namespaceAlias).'\\'.$simpleClassName;

            /** @var \Doctrine\ORM\Mapping\ClassMetadata $entityMetaData */
            $entityMetaData = $em->getClassMetadata($entityClass);

            /** @var \Doctrine\ODM\PHPCR\Mapping\ClassMetadata $documentMetaData */
            $documentMetaData = $dm->getClassMetadata($realClassName);

            $documentReference = $entityMetaData->getFieldValue($entity, $property);

            if (empty($documentReference)) {
                // Null
                continue;
            }

            $identifer = unserialize($documentReference);

            if ($identifer === false || !is_array($identifer)) {
                // Not a serialized array
                continue;
            }
            $documentId = array_shift($identifer);

            $reference = $dm->getReference($realClassName, $documentId);

            if (!is_null($reference)) {
                $entityMetaData->setFieldValue($entity, $property, $reference);
            }
        }
    }
}
