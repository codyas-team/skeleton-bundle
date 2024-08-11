<?php

namespace Codyas\SkeletonBundle\Twig\Component;

use Codyas\SkeletonBundle\Model\CrudEntity;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
    name: "BsFormButtons",
    template: '@Skeleton/crud/components/bs_form_buttons.html.twig')]
final class BsFormButtons
{
    public function __construct(public ?CrudEntity $entityConfig)
    {
    }
}
