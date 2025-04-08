<?php

namespace Codyas\SkeletonBundle\Security;

use Codyas\SkeletonBundle\Exception\ConfigurationException;
use Codyas\SkeletonBundle\Model\CrudEntityInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class RoleVoter extends Voter
{
    public function __construct(
        private Security $security,
    )
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!$subject instanceof VoterArgument) {
            return false;
        }
        if (!$subject->entityConfig->supportsVoter(self::class)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $attribute
     * @param VoterArgument $subject
     * @param TokenInterface $token
     * @return bool
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $attributesSet = $subject->entityConfig->voter->arguments;
        if (!array_key_exists($attribute, $attributesSet) || !$attributesSet[$attribute]) {
            throw new ConfigurationException("Role mapping for attribute \"$attribute\" is not configured in RoleVoter for entity \"{$subject->entityConfig->fqdn}\"");
        }
        if (is_array($attributesSet[$attribute])) {
            return !empty(array_intersect($attributesSet[$attribute], $token->getRoleNames()));
        }
        return $this->security->isGranted($attributesSet[$attribute]);
    }
}
