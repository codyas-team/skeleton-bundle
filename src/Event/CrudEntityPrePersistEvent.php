<?php


namespace Codyas\SkeletonBundle\Event;


use Codyas\SkeletonBundle\Model\CrudEntityInterface;
use Symfony\Component\Form\FormInterface;

class CrudEntityPrePersistEvent extends CrudEntityEvent
{
    protected $entity;
    protected $form;

    public function __construct(CrudEntityInterface $entity, FormInterface $form)
    {
        parent::__construct($entity);
        $this->form = $form;
    }

    public function getForm(): FormInterface
    {
        return $this->form;
    }

}
