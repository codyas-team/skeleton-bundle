<?php


namespace Codyas\SkeletonBundle\Event;


use Codyas\SkeletonBundle\Model\CrudEntityInterface;
use Symfony\Contracts\EventDispatcher\Event;

abstract class CrudEntityEvent extends Event
{

	protected $entity;

	public function __construct(CrudEntityInterface $entity)
	{
		$this->entity = $entity;
	}

	public function getEntity(): CrudEntityInterface
	{
		return $this->entity;
	}

}
