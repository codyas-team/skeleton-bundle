<?php

namespace Codyas\SkeletonBundle\Model;

readonly final class DialogButtonDefinition
{
    public function __construct(
        public string  $label,
        public string  $cssClasses,
        public ?string $icon = 'fa fa-save',
        public ?string $type = 'submit',
        public ?string $action = "csk-crud-dialog#submit",
    )
    {
    }
}
