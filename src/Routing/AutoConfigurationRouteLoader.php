<?php

namespace Codyas\SkeletonBundle\Routing;

use App\Attribute\RouteConfig;
use Codyas\SkeletonBundle\Exception\ConfigurationException;
use Codyas\SkeletonBundle\Model\CrudEntity;
use Codyas\SkeletonBundle\Model\CrudEntityInterface;
use Doctrine\ORM\EntityManagerInterface;
use ReflectionClass;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class AutoConfigurationRouteLoader extends Loader
{
    private bool $loaded = false;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private CacheInterface         $cache,
        private KernelInterface        $kernel,
    )
    {
    }

    public function load(mixed $resource, ?string $type = null): RouteCollection
    {
        if ($this->loaded) {
            throw new \RuntimeException('The "dynamic_routes" loader cannot be loaded twice.');
        }
        if ($this->kernel->getEnvironment() === 'dev') {
            return $this->buildRoutes();
        }
        return $this->cache->get('dynamic_routes', function (ItemInterface $item) {
            $item->expiresAfter(3600); // Cache for 1 hour
            return $this->buildRoutes();
        });
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return 'dynamic_routes' === $type;
    }

    private function getRoutableEntities(): array
    {
        return array_map(
            fn($metadata) => $metadata->getName(),
            array_filter(
                $this->entityManager->getMetadataFactory()->getAllMetadata(),
                fn($metadata) => in_array(CrudEntityInterface::class, class_implements($metadata->getName()))
            )
        );
    }

    private function buildRoutes()
    {
        $routes = new RouteCollection();
        $entities = $this->getRoutableEntities();

        foreach ($entities as $entityClass) {
            $reflection = new ReflectionClass($entityClass);
            $attributes = $reflection->getAttributes(CrudEntity::class);
            foreach ($attributes as $crudAttribute) {
                /** @var CrudEntity $crudEntity */
                $crudEntity = $crudAttribute->newInstance();
                if ($crudEntity->autoConfigureRoutes !== true) {
                    continue;
                }
                if (!$crudEntity->isAutoConfigurationCompliant()) {
                    throw new ConfigurationException("When using autoConfigureRoutes feature, is required to set autoConfigurationEditPath, autoConfigurationCreatePath, autoConfigurationListPath and their respective custom route names.");
                }
                $listRoute = new Route(
                    path: $crudEntity->autoConfigurationListPath,
                    defaults: [
                        '_controller' => "Codyas\SkeletonBundle\Controller\CrudController::autoConfiguredList",
                        'fqdn' => $entityClass,
                        'autoconfigured' => true
                    ],
                    methods: [Request::METHOD_GET]
                );
                $routes->add($crudEntity->customListRoute, $listRoute);

                $createRoute = new Route(
                    path: $crudEntity->autoConfigurationCreatePath,
                    defaults: [
                        '_controller' => "Codyas\SkeletonBundle\Controller\CrudController::autoConfiguredCreate",
                        'fqdn' => $entityClass,
                        'autoconfigured' => true
                    ],
                    methods: [Request::METHOD_GET]
                );
                $routes->add($crudEntity->customCreateRoute, $createRoute);

                $editRoute = new Route(
                    path: $crudEntity->autoConfigurationEditPath,
                    defaults: [
                        '_controller' => "Codyas\SkeletonBundle\Controller\CrudController::autoConfiguredEdit",
                        'fqdn' => $entityClass,
                        'autoconfigured' => true
                    ],
                    requirements: [
                        'id' => '\d+'
                    ],
                    methods: [Request::METHOD_GET]
                );
                $routes->add($crudEntity->customEditRoute, $editRoute);

                if ($crudEntity->customDetailsRoute && $crudEntity->autoConfigurationDetailsPath){
                    $detailsRoute = new Route(
                        path: $crudEntity->autoConfigurationDetailsPath,
                        defaults: [
                            '_controller' => "Codyas\SkeletonBundle\Controller\CrudController::autoConfiguredDetails",
                            'fqdn' => $entityClass,
                            'autoconfigured' => true
                        ],
                        requirements: [
                            'id' => '\d+'
                        ],
                        methods: [Request::METHOD_GET]
                    );
                    $routes->add($crudEntity->customDetailsRoute, $detailsRoute);
                }
            }
        }

        return $routes;
    }
}
