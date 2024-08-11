<?php

namespace Codyas\SkeletonBundle\Form;

use Codyas\SkeletonBundle\Model\UserModel;
use Codyas\SkeletonBundle\Service\UserRoleProviderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{

    public function __construct(
        private readonly UserRoleProviderInterface $roleProvider
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $roleChoices = [];
        foreach ($this->roleProvider->getAvailableUserRoles() as $availableUserRole) {
            $label = $availableUserRole->getTranslatableLabel() ?: $availableUserRole->getRole();
            $roleChoices[$label] = $availableUserRole->getRole();
        }
        $builder
            ->add('name')
            ->add('lastName')
            ->add('email', EmailType::class, [
                'attr' => [
                    "autocomplete" => "off shipping"
                ]
            ])
            ->add('roles', ChoiceType::class, [
                'choices' => $roleChoices,
                'multiple' => true
            ])
            ->add('enabled', CheckboxType::class, [
                'required' => false
            ]);

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            /** @var UserModel $formData */
            $formData = $event->getData();
            $form = $event->getForm();
            if (!$formData->getId()) {
                $form->add('password', RepeatedType::class, [
                    'type' => PasswordType::class,
                    'invalid_message' => 'The password fields must match.',
                    'options' => ['attr' => ['class' => 'password-field', "autocomplete" => "new-password"]],
                    'required' => true,
                    'first_options' => ['label' => 'Password'],
                    'second_options' => ['label' => 'Repeat Password'],
                ]);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserModel::class,
        ]);
    }
}
