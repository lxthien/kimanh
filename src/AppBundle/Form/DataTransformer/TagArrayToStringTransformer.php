<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Form\DataTransformer;

use AppBundle\Entity\Tag;
use AppBundle\Utils\Slugger;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * This data transformer is used to translate the array of tags into a comma separated format
 * that can be displayed and managed by Bootstrap-tagsinput js plugin (and back on submit).
 *
 * See https://symfony.com/doc/current/form/data_transformers.html
 *
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 * @author Jonathan Boyer <contact@grafikart.fr>
 */
class TagArrayToStringTransformer implements DataTransformerInterface
{
    private $manager;

    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($array)
    {
        // The value received is an array of Tag objects generated with
        // Symfony\Bridge\Doctrine\Form\DataTransformer\CollectionToArrayTransformer::transform()
        // The value returned is a string that concatenates the string representation of those objects

        /* @var Tag[] $array */
        return implode(',', $array);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($string)
    {
        $slugger = new Slugger();
        if ('' === $string || null === $string) {
            return [];
        }

        $names = array_filter(array_unique(array_map('trim', explode(',', $string))));

        // Get the current tags and find the new ones that should be created.
        $tags = $this->manager->getRepository(Tag::class)->findBy([
            'name' => $names,
        ]);

        $newNames = array_diff($names, $tags);

        foreach ($newNames as $name) {
            $tagByUrl = $this->manager->getRepository(Tag::class)->findBy([
                'url' => $slugger->slugifyVn($name),
            ]);
            
            if (!$tagByUrl) {
                $tag = new Tag();
                $tag->setName($name);
                $tag->setUrl($slugger->slugifyVn($name));
            } else {
                $tag = $tagByUrl[0];
                $tag->setName($name);
            }
            
            $tags[] = $tag;

            // There's no need to persist these new tags because Doctrine does that automatically
            // thanks to the cascade={"persist"} option in the AppBundle\Entity\Post::$tags property.
        }

        // Return an array of tags to transform them back into a Doctrine Collection.
        // See Symfony\Bridge\Doctrine\Form\DataTransformer\CollectionToArrayTransformer::reverseTransform()
        return $tags;
    }
}
