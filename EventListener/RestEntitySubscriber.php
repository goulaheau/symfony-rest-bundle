<?php

namespace Goulaheau\RestBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Goulaheau\RestBundle\Entity\RestEntity;

/**
 * Class RestEntitySubscriber
 *
 * @package Goulaheau\RestBundle\EventListener
 */
class RestEntitySubscriber implements EventSubscriber
{
    /**
     * @param OnFlushEventArgs $args
     * @throws \Exception
     */
    public function onFlush($args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof RestEntity) {
                $entity->setCreatedAt(new \DateTime());
                $uow->recomputeSingleEntityChangeSet($em->getClassMetadata(get_class($entity)), $entity);
            }
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof RestEntity) {
                $entity->setUpdatedAt(new \DateTime());
                $uow->recomputeSingleEntityChangeSet($em->getClassMetadata(get_class($entity)), $entity);
            }
        }
    }

    /**
     * @return array|string[]
     */
    public function getSubscribedEvents()
    {
        return [Events::onFlush];
    }
}
