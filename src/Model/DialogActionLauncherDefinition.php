<?php

namespace Codyas\SkeletonBundle\Model;

readonly final class DialogActionLauncherDefinition
{
    public function __construct(
        public string  $title,
        public string  $icon,
        public ?string $cssClass = null,
    )
    {
    }
}
