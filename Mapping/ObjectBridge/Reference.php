<?php

namespace Hacfi\Bundle\ObjectBridgeBundle\Mapping\ObjectBridge;

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
