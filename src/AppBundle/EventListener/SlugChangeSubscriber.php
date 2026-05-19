<?php

namespace AppBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use AppBundle\Entity\News;
use AppBundle\Entity\NewsCategory;
use AppBundle\Entity\Tag;
use AppBundle\Entity\Redirect;

class SlugChangeSubscriber implements EventSubscriber
{
    public function getSubscribedEvents()
    {
        return [
            Events::onFlush,
        ];
    }

    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof News) {
                $changeSet = $uow->getEntityChangeSet($entity);
                if (isset($changeSet['url'])) {
                    $oldUrl = $changeSet['url'][0];
                    $newUrl = $changeSet['url'][1];

                    if (!empty($oldUrl) && !empty($newUrl) && $oldUrl !== $newUrl) {
                        $this->createRedirect($em, $uow, '/' . $oldUrl . '.html', '/' . $newUrl . '.html');
                        $this->createRedirect($em, $uow, '/amp/' . $oldUrl . '.html', '/amp/' . $newUrl . '.html');
                    }
                }
            } elseif ($entity instanceof Tag) {
                $changeSet = $uow->getEntityChangeSet($entity);
                if (isset($changeSet['url'])) {
                    $oldUrl = $changeSet['url'][0];
                    $newUrl = $changeSet['url'][1];

                    if (!empty($oldUrl) && !empty($newUrl) && $oldUrl !== $newUrl) {
                        $this->createRedirect($em, $uow, '/tag/' . $oldUrl, '/tag/' . $newUrl);
                    }
                }
            } elseif ($entity instanceof NewsCategory) {
                $changeSet = $uow->getEntityChangeSet($entity);
                if (isset($changeSet['url'])) {
                    $oldUrl = $changeSet['url'][0];
                    $newUrl = $changeSet['url'][1];

                    if (!empty($oldUrl) && !empty($newUrl) && $oldUrl !== $newUrl) {
                        $parent = $entity->getParentcat();
                        if ($parent && $parent !== 'root') {
                            $parentUrl = $parent->getUrl();
                            $oldPath = '/' . $parentUrl . '/' . $oldUrl;
                            $newPath = '/' . $parentUrl . '/' . $newUrl;
                        } else {
                            $oldPath = '/' . $oldUrl;
                            $newPath = '/' . $newUrl;
                        }
                        $this->createRedirect($em, $uow, $oldPath, $newPath);
                    }
                }
            }
        }
    }

    private function createRedirect($em, $uow, $oldPath, $newPath)
    {
        // Check if a redirect already exists for this old URL to avoid Unique constraint violation
        $existing = $em->getRepository(Redirect::class)->findOneBy(['oldUrl' => $oldPath]);
        if ($existing) {
            $existing->setNewUrl($newPath);
            $existing->setEnable(true);
            $existing->setStatusCode(301);
            
            $classMetadata = $em->getClassMetadata(Redirect::class);
            $uow->recomputeSingleEntityChangeSet($classMetadata, $existing);
            return;
        }

        $redirect = new Redirect();
        $redirect->setOldUrl($oldPath);
        $redirect->setNewUrl($newPath);
        $redirect->setStatusCode(301);
        $redirect->setEnable(true);

        $em->persist($redirect);
        
        $classMetadata = $em->getClassMetadata(Redirect::class);
        $uow->computeChangeSet($classMetadata, $redirect);
    }
}
