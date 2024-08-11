<?php

namespace Codyas\SkeletonBundle\EventSubscriber;

use App\Controller\AbstractMarketplaceController;
use Codyas\SkeletonBundle\Controller\CrudController;
use Codyas\SkeletonBundle\Service\CrudService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CrudSubscriber implements EventSubscriberInterface
{
    public function __construct(private CrudService $crudService)
    {
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        $controllerData = $event->getController();
        $controller = reset($controllerData);
        if (!$controller instanceof CrudController) {
            return;
        }
        $fqdn = base64_decode($request->get('fqdn'));
        $controller->setEntityConfiguration($this->crudService->getEntityConfiguration($fqdn));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
}
