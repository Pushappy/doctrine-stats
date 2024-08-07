<?php

declare(strict_types=1);

namespace Steevanb\DoctrineStats\EventSubscriber;

use Steevanb\DoctrineStats\{
    Bridge\DoctrineStatsBundle\DataCollector\DoctrineCollectorInterface,
    Doctrine\ORM\Event\PostCreateEntityEventArgs,
    Doctrine\ORM\Event\PostHydrationEventArgs,
    Doctrine\ORM\Event\PostLazyLoadEventArgs,
    Doctrine\ORM\Event\PreHydrationEventArgs
};

/** @noinspection PhpUnused */
class DoctrineEventSubscriber
{
    /** @var string|null */
    protected $preHydrationEventId;

    /** @var float|null */
    protected $preHydrationTime;

    /** @var DoctrineCollectorInterface  */
    protected $collector;

    public function __construct(DoctrineCollectorInterface $collector)
    {
        $this->collector = $collector;
    }

    /** @noinspection PhpUnused */
    public function postLazyLoad(PostLazyLoadEventArgs $eventArgs): void
    {
        $this
            ->collector
            ->addLazyLoadedEntity($eventArgs->getEntityManager(), $eventArgs->getEntity());
    }

    /** @noinspection PhpUnused */
    public function preHydration(PreHydrationEventArgs $eventArgs): void
    {
        if ($this->preHydrationEventId === null) {
            $this->preHydrationEventId = $eventArgs->getEventId();
            $this->preHydrationTime = microtime(true);
        }
    }

    /** @noinspection PhpUnused */
    public function postHydration(PostHydrationEventArgs $eventArgs): void
    {
        if ($this->preHydrationEventId === $eventArgs->getPreHydrationEventId()) {
            $postHydrationTime = microtime(true);
            $this->collector->addHydrationTime(
                $eventArgs->getHydratorClassName(),
                ($postHydrationTime - ($this->preHydrationTime ?? 0)) * 1000
            );
            $this->preHydrationEventId = null;
            $this->preHydrationTime = null;
        }
    }

    /** @noinspection PhpUnused */
    public function postCreateEntity(PostCreateEntityEventArgs $eventArgs): void
    {
        $this->collector->addHydratedEntity(
            $eventArgs->getHydratorClassName(),
            $eventArgs->getClassName(),
            $eventArgs->getClassIdentifiers()
        );
    }
}
