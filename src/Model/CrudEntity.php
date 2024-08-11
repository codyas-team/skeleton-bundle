<?php

namespace Codyas\SkeletonBundle\Model;

use Attribute;
use Codyas\SkeletonBundle\Exception\ConfigurationException;
use Codyas\SkeletonBundle\Helper\Constants;

#[Attribute(Attribute::TARGET_CLASS)]
class CrudEntity
{
    public function __construct(
        public string        $fqdn,
        public string        $label,
        public string        $formType,
        public array         $dataTableColumns,
        public ?string       $customListRoute = null,
        public ?string       $customCreateRoute = null,
        public ?string       $customEditRoute = null,
        public ?string       $customDeleteRoute = 'csk_crud_delete',
        public ?string       $formTemplate = '@Skeleton/crud/partials/_form.html.twig',
        public ?string       $filterType = null,
        public ?string       $filterTemplate = '@Skeleton/crud/partials/_form_filter.html.twig',
        public ?string       $actionButtonsTemplate = '@Skeleton/crud/partials/_action_buttons.html.twig',
        public ?bool         $displayActionsButtons = true,
        public ?TemplateEnum $template = TemplateEnum::Tabler,
        public ?TemplateEnum $layout = TemplateEnum::TablerLayoutHorizontal,
        public ?string       $customListTemplate = null,
        public ?string       $customCreateTemplate = null,
        public ?string       $customEditTemplate = null,
        public ?bool         $displayRowNumber = true,
        public ?bool         $customFetch = false,
        public ?string       $genericFormLabel = null,
        public ?array        $dialogs = null,
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
            Constants::ACTION_CREATE => $this->customCreateRoute ? null : $baseFqdn,
            Constants::ACTION_EDIT => $this->customEditRoute ? null : $baseFqdn,
            Constants::ACTION_DELETE => $baseFqdn,
        };
    }

    public function getCreateTranslatableLabel(): string
    {
        return "New {$this->label}";
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
        if ($this->displayActionsButtons) {
            $count++;
        }

        return $count;
    }

    public function getListRoute(): ?string
    {
        return $this->customListRoute ?: 'csk_crud_list';
    }

    public function getCreateRoute(): ?string
    {
        return $this->customCreateRoute ?: 'csk_crud_create';
    }

    public function getEditRoute(): ?string
    {
        return $this->customEditRoute ?: 'csk_crud_edit';
    }

}
