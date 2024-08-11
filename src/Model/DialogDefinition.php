<?php

namespace Codyas\SkeletonBundle\Model;

readonly final class DialogDefinition
{
    public function __construct(
        public string                          $id,
        public string                          $label,
        public string                          $loadUrl,
        public bool                            $containsForm,
        public ?DialogActionLauncherDefinition $actionLauncherDefinition = null,
        public ?array                          $buttonDefinitions = [],
        public ?bool                           $displayHeader = true,
        public ?string                         $dialogWidthClass = "",
        public ?string                         $icon = "",
    )
    {
    }
}
