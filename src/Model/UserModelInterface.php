<?php

namespace Codyas\SkeletonBundle\Model;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Security\Core\User\UserInterface;

interface UserModelInterface
{
    public function getAvatar() : File|string|null;
    public function isVerified() : bool;
    public function setVerified(bool $verified) : static;
}
