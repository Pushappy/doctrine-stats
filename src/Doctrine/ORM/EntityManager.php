<?php

declare(strict_types=1);

namespace Steevanb\DoctrineStats\Doctrine\ORM;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\{
    Configuration,
    EntityManager as DoctrineEntityManager,
    ORMException
};
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Steevanb\DoctrineStats\Doctrine\ORM\Proxy\ProxyFactory;

class EntityManager extends DoctrineEntityManager
{
    public function __construct(Connection $conn, Configuration $config, EventManager $eventManager)
    {
        if ($config->getMetadataDriverImpl() instanceof MappingDriver === false) {
            throw ORMException::missingMappingDriverImpl();
        }

        switch (true) {
            case (is_array($conn)):
                $conn = \Doctrine\DBAL\DriverManager::getConnection(
                    $conn,
                    $config,
                    $eventManager ?? new EventManager()
                );
                break;

            case ($conn instanceof Connection):
                if ($eventManager !== null && $conn->getObjectManager() !== $eventManager) {
                    throw ORMException::mismatchedEventManager();
                }
                break;

            default:
                throw new \InvalidArgumentException('Invalid argument: ' . $conn);
        }

        parent::__construct($conn, $config, $eventManager);

        $proxyDir = $config->getProxyDir();
        if (is_string($proxyDir) === false) {
            throw new \Exception('Proxy directory must be configured.');
        }

        $proxyNamespace = $config->getProxyNamespace();
        if (is_string($proxyNamespace) === false) {
            throw new \Exception('Proxy namespace must be configured.');
        }

        $this->setParentPrivatePropertyValue(
            'proxyFactory',
            new ProxyFactory(
                $this,
                $proxyDir,
                $proxyNamespace,
                $config->getAutoGenerateProxyClasses()
            )
        );
        $this->setParentPrivatePropertyValue('unitOfWork', new UnitOfWork($this));
    }

    /** @param mixed $value */
    protected function setParentPrivatePropertyValue(string $name, $value): self
    {
        $reflectionProperty = new \ReflectionProperty(parent::class, $name);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this, $value);
        $reflectionProperty->setAccessible(false);

        return $this;
    }
}
