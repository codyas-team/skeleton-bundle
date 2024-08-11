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
        ];
    }
}
