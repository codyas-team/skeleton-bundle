<?php


namespace Codyas\SkeletonBundle\Exception;


use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class InvalidFormException extends BadRequestException
{
    public function __construct(private FormInterface $form)
    {
        parent::__construct();
    }

    public function getForm(): FormInterface
    {
        return $this->form;
    }

}
