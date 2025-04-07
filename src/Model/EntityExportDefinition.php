<?php

namespace Codyas\SkeletonBundle\Model;

readonly final class EntityExportDefinition
{
    public function __construct(
        public string $format,
        public string $label,
        public string $icon,
        public string $implementationClass,
        public string $callbackMethod,
    )
    {
    }
}
