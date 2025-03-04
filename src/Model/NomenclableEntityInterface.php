<?php

namespace Codyas\SkeletonBundle\Model;

interface NomenclableEntityInterface
{

    public function setDeletedAt(\DateTime $param);
    public function isNomenclator() : bool;
}
