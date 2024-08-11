<?php

namespace Codyas\SkeletonBundle\Service;

use Codyas\SkeletonBundle\Model\UserRole;

interface UserRoleProviderInterface
{

    const string TAG = "skeleton.user_role_provider";

    /**
     * @return  UserRole[]
     */
    public function getAvailableUserRoles() : array;
}
