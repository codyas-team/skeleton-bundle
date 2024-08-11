<?php


namespace Codyas\SkeletonBundle\Event;


use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\Event;

class UserPasswordChangedEvent extends Event
{
    public function __construct(public UserInterface $user)
    {
    }

}
