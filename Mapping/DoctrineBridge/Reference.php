<?php

namespace hacfi\Bundle\DoctrineBridgeBundle\Mapping\DoctrineBridge;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Reference
{
    /** @var string */
    public $type;
    /** @var string */
    public $name;
    /** @var string */
    public $manager = 'default';
}
