<?php

namespace Codyas\SkeletonBundle\Form;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NomenclableEntityType extends AbstractType
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'query_builder' => function (ServiceEntityRepository $er) {
                return $er->createQueryBuilder('e')
                    ->where('e.deletedAt IS NULL');
            },
            'preserve_selected_deleted' => true,
        ]);

    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!$options['preserve_selected_deleted']) {
            return;
        }
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            $form = $event->getForm();
            $data = $event->getData();

            if ($data === null) {
                return;
            }
            $entityClass = $options['class'];
            $repository = $this->entityManager->getRepository($entityClass);

            if (is_array($data)) {
                $deletedEntities = [];
                foreach ($data as $entity) {
                    if (method_exists($entity, 'getDeletedAt') && $entity->getDeletedAt() !== null) {
                        $deletedEntities[] = $entity;
                    }
                }
            } else {
                $deletedEntities = (method_exists($data, 'getDeletedAt') && $data->getDeletedAt() !== null) ? [$data] : [];
            }

            if (!empty($deletedEntities)) {
                $currentQueryBuilder = $options['query_builder'];
                if ($currentQueryBuilder instanceof \Closure) {
                    $currentQueryBuilder = $currentQueryBuilder($repository);
                }

                $orX = $currentQueryBuilder->expr()->orX();
                foreach ($deletedEntities as $deletedEntity) {
                    $orX->add($currentQueryBuilder->expr()->eq('e.id', $deletedEntity->getId()));
                }
                $currentQueryBuilder->orWhere($orX);
            }
        });
    }

    public function getParent(): string
    {
        return EntityType::class;
    }
}
