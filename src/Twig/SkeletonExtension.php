<?php

namespace Codyas\SkeletonBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class SkeletonExtension extends AbstractExtension
{
    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
//            new TwigFilter('tabler_icon', [RuntimeExtension::class, 'icon']),
        ];
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('csk_option', [RuntimeExtension::class, 'getOption']),
            new TwigFunction('csk_menu_breadcrumb', [RuntimeExtension::class, 'getMenuBreadcrumb']),
            new TwigFunction('csk_is_authorized', [RuntimeExtension::class, 'isAuthorized']),
            new TwigFunction('csk_build_partial_layout', [RuntimeExtension::class, 'buildPartialLayout']),
            new TwigFunction('csk_get_encoded_class', [RuntimeExtension::class, 'getClass']),
        ];
    }
}
