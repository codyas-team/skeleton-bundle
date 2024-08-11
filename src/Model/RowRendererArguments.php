<?php

namespace Codyas\SkeletonBundle\Model;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

readonly final class RowRendererArguments
{

    public function __construct(
        public TranslatorInterface           $translator,
        public RouterInterface               $router,
        public Environment                   $twig,
        public AuthorizationCheckerInterface $authChecker,
        public UserInterface                 $user)
    {

    }

}
