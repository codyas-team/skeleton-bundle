<?php

namespace Codyas\SkeletonBundle\Model;

interface CrudEntityInterface
{
    const string LIST = 'list';

    /** @deprecated  */
    const string VIEW = 'view';

    const string DETAILS = 'details';

    const string CREATE = 'create';

    const string EDIT = 'edit';

    const string DELETE = 'delete';

    const string EXPORT_IMPLEMENTATION_SERVICE_TAG = "csk_export_implementation";

    public function getId(): ?int;

    public function renderDataTableRow(RowRendererArguments $arguments): array;

    public function __toString(): string;
}
