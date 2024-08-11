<?php

namespace Codyas\SkeletonBundle\Model;

final readonly class UserRole
{
    public function __construct(
        private string $role,
        private ?string $translatableLabel = null
    )
    {
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getTranslatableLabel(): ?string
    {
        return $this->translatableLabel;
    }
}
