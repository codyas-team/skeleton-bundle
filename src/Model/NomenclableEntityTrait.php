<?php

namespace Codyas\SkeletonBundle\Model;

use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;

trait NomenclableEntityTrait
{
    use TimestampableEntity;
    use SoftDeleteableEntity;

    public function isNomenclator() : bool
    {
        return true;
    }
}
