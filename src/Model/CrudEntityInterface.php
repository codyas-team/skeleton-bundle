<?php

namespace Codyas\SkeletonBundle\Model;

interface CrudEntityInterface
{
    const string LIST = 'list';
    const string VIEW = 'view';
    const string CREATE = 'create';
    const string EDIT = 'edit';
    const string DELETE = 'delete';

    public function getId(): ?int;

    public function renderDataTableRow(RowRendererArguments $arguments): array;

    public function __toString(): string;
}
