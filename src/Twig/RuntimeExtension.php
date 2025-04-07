<?php

namespace Codyas\SkeletonBundle\Twig;

use Codyas\SkeletonBundle\Exception\ConfigurationException;
use Codyas\SkeletonBundle\Model\CrudEntity;
use Codyas\SkeletonBundle\Model\CrudEntityInterface;
use Codyas\SkeletonBundle\Service\CrudService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Twig\Extension\RuntimeExtensionInterface;

final class RuntimeExtension implements RuntimeExtensionInterface
{
    public function __construct(
        readonly private EventDispatcherInterface $eventDispatcher,
        readonly private ParameterBagInterface    $parameterBag, private readonly CrudService $crudService
    )
    {
    }

    public function getOption(string $path)
    {
        $options = $this->parameterBag->get('skeleton');
        if (array_key_exists($path, $options) && !$options[$path]) {
            return $options[$path];
        }
        if (str_contains($path, ".")) {
            $current = $options;
            $parts = explode(".", $path);
            foreach ($parts as $part) {
                if (!isset($current[$part])) {
                    throw new ConfigurationException("Option skeleton.$path is not  configured.");
                }
                $current = $current[$part];
            }
            return $current;
        }

        throw new ConfigurationException("Option skeleton.$path is not  configured.");

    }

    public function getMenuBreadcrumb(array $menuItems): ?array
    {
        $breadcrumbs = [];
        foreach ($menuItems as $menuItem) {
            if (!$menuItem->isActive()) {
                continue;
            }
            $breadcrumbs = array_merge($breadcrumbs, [$menuItem], $this->getMenuBreadcrumb($menuItem->getChildren()));
        }
        return $breadcrumbs;
    }

    public function isAuthorized(string $attribute, CrudEntity $entityConfig, ?CrudEntityInterface $instance = null): bool
    {
        return $this->crudService->isAuthorized($attribute, $entityConfig, $instance);
    }

}
