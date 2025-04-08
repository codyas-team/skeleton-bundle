<?php

namespace Codyas\SkeletonBundle\Model;

readonly final class PageDefinition
{
    public function __construct(
        public string                          $id,
        public string                          $label,
        public string                          $path,
        public bool                            $containsForm,
        public ?array                          $buttonDefinitions = [],
        public ?bool                           $displayHeader = true,
        public ?string                         $dialogWidthClass = "",
        public ?string                         $icon = "",
    )
    {
    }
}
