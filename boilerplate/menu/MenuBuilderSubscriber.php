<?php

namespace App\EventSubscriber;

use KevinPapst\TablerBundle\Event\MenuEvent;
use KevinPapst\TablerBundle\Model\MenuItemModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MenuBuilderSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            MenuEvent::class => ['onSetupMenu', 100],
        ];
    }

    public function onSetupMenu(MenuEvent $event): void
    {
        $blog = new MenuItemModel('blogId', 'Blog', 'item_symfony_route', [], 'fas fa-tachometer-alt');

        $blog->addChild(
            new MenuItemModel('ChildOneItemId', 'ChildOneDisplayName', 'app_app', [], 'fas fa-rss-square')
        );
        $blog->addChild(
            new MenuItemModel('ChildTwoItemId', 'ChildTwoDisplayName', 'app_app')
        );

        $event->addItem($blog);

        $this->activateByRoute(
            $event->getRequest()->get('_route'),
            $event->getItems()
        );
    }

    protected function activateByRoute($route, $items): void
    {
        foreach ($items as $item) {
            if ($item->hasChildren()) {
                $this->activateByRoute($route, $item->getChildren());
            } elseif ($item->getRoute() == $route) {
                $item->setIsActive(true);
            }
        }
    }
}