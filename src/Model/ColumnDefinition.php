<?php

namespace Codyas\SkeletonBundle\Model;

readonly final class ColumnDefinition
{
    public function __construct(
        public string  $label,
        public ?string $propertyKey = null,
        public ?array  $translationParameters = null,
        public ?array  $translationDomain = null,
        public ?bool   $sort = false,
        public ?string $sortDirection = 'ASC',
        public ?bool   $renderHtml = false,
    )
    {
    }
}
