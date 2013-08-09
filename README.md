hacfi Object Bridge Bundle
===

This bundle allows associations between different Doctrine ORMs and ODMs.

This project is at a experimental state and has limited functionality at the moment. It should work for Doctrine ORM to Doctrine PHPCR ODM, and vice versa (not ORM to ORM or PHPCR ODM to PHPCR ODM though).

The related objects are lazy-loaded if they haven’t been loaded by the object manager.


TODO:
---

- [x] Mapping via annotations
- [x] Bridge to and from Doctrine ORM entities
- [x] Bridge to and from Doctrine PHPCR ODM documents
- [ ] Specify correct dependencies in composer.json
- [ ] Use loadClassMetadata event to properly map fields (so you don’t have to add the string mapping yourself)
- [ ] Add support for XML and/or YAML mapping
- [ ] Create a registry for Doctrine mappers to make it extendable (MongoDB ODM & CouchDB ODM)
- [ ] Validate the mappings and make sure the mapper specified via ```type``` is available

Please note that the use-statement for the annotations has to be "use Hacfi\Bundle\ObjectBridgeBundle\Mapping\ObjectBridge;"
and doesn’t allow aliasing because the Doctrine’s SimpleAnnotationReader doesn’t support it. This might change in the future.


Install
---

app/AppKernel.php:

```php
            new Doctrine\Bundle\DoctrineObjectBridgeBundle\DoctrineObjectBridgeBundle(),
```

app/autoload.php:

```php
AnnotationRegistry::registerFile(__DIR__.'/../vendor/hacfi/object-bridge-bundle/Hacfi/Bundle/ObjectBridgeBundle/Mapping/ObjectBridge/Reference.php');
```

Example
---

/src/Hacfi/AppBundle/Entity/Product.php:

```php
<?php
namespace Hacfi\AppBundle\Entity;

use Hacfi\AppBundle\Document\ProductProperties;

use Doctrine\ORM\Mapping as ORM;
use Hacfi\Bundle\ObjectBridgeBundle\Mapping\ObjectBridge;

/**
 * Product
 *
 * @ORM\Table(name="ecommerce_product")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 */
class Product implements ProductInterface
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

...

    /**
     * @ORM\Column(name="properties", type="string", length=255, nullable=false)
     *
     * @ObjectBridge\Reference(type="phpcr", name="HacfiAppBundle:ProductProperties", manager="default")
     */
    private $properties;

}

```

/src/Hacfi/AppBundle/Document/ProductProperties.php:

```php
<?php
namespace Hacfi\AppBundle\Document;

use Hacfi\AppBundle\Entity\Product;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Hacfi\Bundle\ObjectBridgeBundle\Mapping\ObjectBridge;

/**
 * ProductProperties
 *
 * @PHPCRODM\Document(referenceable=true)
 */
class ProductProperties
{
    /** @PHPCRODM\Id(strategy="parent") */
    protected $id;

...


    /**
     * @PHPCRODM\String
     *
     * @ObjectBridge\Reference(type="orm", name="HacfiAppBundle:Product", manager="default")
     */
    protected $product;

}

```
