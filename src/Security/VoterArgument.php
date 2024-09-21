<?php

namespace Codyas\SkeletonBundle\Security;

use Codyas\SkeletonBundle\Model\CrudEntity;
use Codyas\SkeletonBundle\Model\CrudEntityInterface;

final readonly class VoterArgument
{
    public function __construct(
        public CrudEntity          $entityConfig,
        public ?CrudEntityInterface $instance = null
    )
    {
    }
}
