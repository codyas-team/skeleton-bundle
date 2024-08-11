<?php

namespace Codyas\SkeletonBundle\Model;

interface CrudEntityInterface
{
    public function getId(): ?int;

    public function renderDataTableRow(RowRendererArguments $arguments): array;
}
