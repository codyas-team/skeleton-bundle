<?php

namespace Codyas\SkeletonBundle\Model;

readonly final class VoterDefinition
{
    public function __construct(
        public string $voterClass,
        public mixed $arguments = null,
    )
    {
    }
}
