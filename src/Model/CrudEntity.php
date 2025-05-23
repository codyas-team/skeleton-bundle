<?php

namespace Codyas\SkeletonBundle\Model;

use Attribute;
use Codyas\SkeletonBundle\Exception\ConfigurationException;
use Codyas\SkeletonBundle\Helper\Constants;
use Codyas\SkeletonBundle\Security\RoleVoter;

#[Attribute(Attribute::TARGET_CLASS)]
class CrudEntity
{
    public function __construct(
        public string          $fqdn,
        public string          $label,
        public string          $formType,
        public array           $dataTableColumns,
        public ?string         $customListRoute = null,
        public ?string         $customDetailsRoute = null,
        public ?string         $customCreateRoute = null,
        public ?string         $customEditRoute = null,
        public ?string         $customDeleteRoute = 'csk_crud_delete',
        public ?string         $formTemplate = '@Skeleton/crud/partials/_form.html.twig',
        public ?string         $filterType = null,
        public ?array          $filterTypeOptions = [],
        public ?int            $listPageSize = 15,
        public ?string         $filterTemplate = '@Skeleton/crud/partials/_form_filter.html.twig',
        public ?string         $actionButtonsTemplate = '@Skeleton/crud/partials/_action_buttons.html.twig',
        public ?string         $actionButtonsColumnWidth = '10%',
        public ?bool           $displayActionsButtons = true,
        public ?TemplateEnum   $template = TemplateEnum::Tabler,
        public ?TemplateEnum   $layout = TemplateEnum::TablerLayoutHorizontal,
        public ?string         $customListTemplate = null,
        public ?string         $customDetailsTemplate = null,
        public ?string         $detailsContentTemplate = "",
        public ?string         $detailsButtonsTemplate = "",
        public ?string         $customCreateTemplate = null,
        public ?string         $customEditTemplate = null,
        public ?bool           $displayRowNumber = true,
        public ?bool           $customFetch = false,
        public ?string         $genericFormLabel = null,
        public ?array          $dialogs = null,
        public ?bool           $displayNomenclatorStatusColumn = false,
        public ?string         $nomenclatorStatusColumnWidth = '15%',
        public ?string         $nomenclatorStatusColumnTemplate = '@Skeleton/crud/partials/_nomenclator_status.html.twig',
        public ?bool           $autoConfigureRoutes = false,
        public ?string         $autoConfigurationListPath = null,
        public ?string         $autoConfigurationDetailsPath = null,
        public ?string         $autoConfigurationCreatePath = null,
        public ?string         $autoConfigurationEditPath = null,
        public VoterDefinition $voter = new VoterDefinition(RoleVoter::class, arguments: [
            CrudEntityInterface::LIST => 'ROLE_ADMIN',
            CrudEntityInterface::VIEW => 'ROLE_ADMIN',
            CrudEntityInterface::DETAILS => 'ROLE_ADMIN',
            CrudEntityInterface::CREATE => 'ROLE_ADMIN',
            CrudEntityInterface::EDIT => 'ROLE_ADMIN',
            CrudEntityInterface::DELETE => 'ROLE_ADMIN'
        ]),
        public ?array          $exportConfiguration = null,
        public ?string         $icon = null,
        public ?string         $crudMode = CrudEntityInterface::CRUD_MODE_NON_SPA,
        public ?string         $spaModalFormWidth = 'modal-lg',
        public ?string         $dataTableClass = "table-hover",
    )
    {
    }

    public function getBaseLayout(): string
    {
        return match ($this->layout) {
            TemplateEnum::TablerLayoutHorizontal => '@Skeleton/crud/layout/tabler/layout_horizontal.html.twig',
            TemplateEnum::TablerLayoutVertical => '@Skeleton/crud/layout/tabler/layout_vertical.html.twig',
            default => throw new ConfigurationException("Unsupported template layout {$this->template->name}::{$this->layout->name}. Make sure the template is registered in Codyas\SkeletonBundle\Model\TemplateEnum."),
        };
    }

    public function getListTemplate(): string
    {
        if ($this->customListTemplate) {
            return $this->customListTemplate;
        }
        return match ($this->template) {
            TemplateEnum::Tabler => '@Skeleton/crud/layout/tabler/list.html.twig',
            default => throw new ConfigurationException("Unsupported template {$this->template->name}. Make sure the template is registered in Codyas\SkeletonBundle\Model\TemplateEnum.")
        };
    }

    public function getDetailsTemplate(): string
    {
        if ($this->customDetailsTemplate) {
            return $this->customDetailsTemplate;
        }
        return match ($this->template) {
            TemplateEnum::Tabler => '@Skeleton/crud/layout/tabler/details.html.twig',
            default => throw new ConfigurationException("Unsupported template {$this->template->name}. Make sure the template is registered in Codyas\SkeletonBundle\Model\TemplateEnum.")
        };
    }

    public function getCreateTemplate(): string
    {
        if ($this->customCreateTemplate) {
            return $this->customCreateTemplate;
        }
        return match ($this->template) {
            TemplateEnum::Tabler => '@Skeleton/crud/layout/tabler/create.html.twig',
            default => throw new ConfigurationException("Unsupported template {$this->template->name}. Make sure the template is registered in Codyas\SkeletonBundle\Model\TemplateEnum.")
        };
    }

    public function getEditTemplate(): string
    {
        if ($this->customEditTemplate) {
            return $this->customEditTemplate;
        }
        return match ($this->template) {
            TemplateEnum::Tabler => '@Skeleton/crud/layout/tabler/edit.html.twig',
            default => throw new ConfigurationException("Unsupported template {$this->template->name}. Make sure the template is registered in Codyas\SkeletonBundle\Model\TemplateEnum.")
        };
    }

    public function getEncodedFqdn(): string
    {
        return base64_encode($this->fqdn);
    }

    public function getFqdnRouteArgument(?string $action = Constants::ACTION_LIST): ?string
    {
        $baseFqdn = base64_encode($this->fqdn);
        return match ($action) {
            Constants::ACTION_LIST => $this->customListRoute ? null : $baseFqdn,
            Constants::ACTION_DETAILS => $this->customDetailsRoute ? null : $baseFqdn,
            Constants::ACTION_CREATE => $this->customCreateRoute ? null : $baseFqdn,
            Constants::ACTION_EDIT => $this->customEditRoute ? null : $baseFqdn,
            Constants::ACTION_DELETE => $baseFqdn,
        };
    }

    public function getCreateTranslatableLabel(): string
    {
        $label = strtolower($this->label);
        return "New {$label}";
    }

    public function getEditTranslatableLabel(): string
    {
        return "Edit %identifier%";
    }

    public function getGenericFormTranslatableLabel(): string
    {
        return $this->genericFormLabel ?: "{$this->label} form";
    }

    public function getHeaderTranslatableLabel(): string
    {
        return "{$this->label}";
    }

    public function isFilterable(): bool
    {
        return $this->filterType !== null;
    }

    public function getColumnCount(): int
    {
        $count = count($this->dataTableColumns);
        if ($this->displayRowNumber) {
            $count++;
        }
        if ($this->displayNomenclatorStatusColumn) {
            $count++;
        }
        if ($this->displayActionsButtons) {
            $count++;
        }

        return $count;
    }

    public function getListRoute(): ?string
    {
        return $this->customListRoute ?: 'csk_crud_list';
    }

    public function getDetailsRoute(): ?string
    {
        return $this->customDetailsRoute ?: 'csk_crud_details';
    }

    public function getCreateRoute(): ?string
    {
        return $this->customCreateRoute ?: 'csk_crud_create';
    }

    public function getEditRoute(): ?string
    {
        return $this->customEditRoute ?: 'csk_crud_edit';
    }

    public function supportsVoter(string $voterFqdn): bool
    {
        return $voterFqdn === $this->voter->voterClass;
    }

    public function getFlattenedFqdn(): string
    {
        return $this->normalizeString($this->label);
    }

    function normalizeString(string $input): string
    {
        $normalized = mb_strtolower($input, 'UTF-8');
        $normalized = preg_replace('/[^a-z0-9\s-]/', '', $normalized);
        return preg_replace('/[\s-]+/', '-', $normalized);
    }


    public function getDataTableColumnDefinition(): array
    {
        $columnDefinition = [];
        if ($this->displayRowNumber === true) {
            $columnDefinition = array_merge([
                new ColumnDefinition(label: "", width: "5%")
            ], $columnDefinition);
        }
        if ($this->displayNomenclatorStatusColumn === true) {
            $columnDefinition = array_merge($columnDefinition, [
                new ColumnDefinition(label: "Status", width: $this->nomenclatorStatusColumnWidth, renderHtml: true)
            ]);
        }
        $columnDefinition = array_merge($columnDefinition, $this->dataTableColumns);
        if ($this->displayActionsButtons) {
            $columnDefinition = array_merge($columnDefinition, [
                new ColumnDefinition(label: "", renderHtml: true, width: $this->actionButtonsColumnWidth)
            ]);
        }
        return $columnDefinition;

    }

    public function getInstanceDetailsContentTemplate(): string
    {
        if (!$this->detailsContentTemplate) {
            throw new ConfigurationException("Entity {$this->fqdn} is not properly configured for display details. Please set \"detailsContentTemplate\" in entity configuration.");
        }
        return $this->detailsContentTemplate;
    }

    public function isAutoConfigurationCompliant(): bool
    {
        return
            $this->autoConfigurationEditPath
            && $this->autoConfigurationCreatePath
            && $this->autoConfigurationListPath
            && $this->customEditRoute
            && $this->customCreateRoute
            && $this->customListRoute;
    }

    public function isExportable(): bool
    {
        return $this->exportConfiguration !== null;
    }

    public function getExportConfigurationForFormat(string $format): ?EntityExportDefinition
    {
        /** @var EntityExportDefinition $exportConfiguration */
        foreach ($this->exportConfiguration as $exportConfiguration) {
            if ($exportConfiguration->format === $format) {
                return $exportConfiguration;
            }
        }
        return null;
    }
}
