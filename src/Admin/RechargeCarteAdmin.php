<?php

namespace App\Admin;

use App\Entity\Competition;
use App\Entity\Notification;
use App\Entity\Payment;
use App\Entity\User;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

final class RechargeCarteAdmin extends AbstractAdmin{
    protected $mCardUid;
    protected $mCardHolder;
    public function __construct(private NotificationService $notifyer, private EntityManagerInterface $em)
    {
        
    }

   /* protected function configureFormFields(FormMapper $form): void
    {
       
        $form->add('user', EntityType::class,[
            'class' => User::class,
            'choice_label' => 'username',
            'multiple' => false,
            'expanded' => false,
        ]);
        $form->add('amount', NumberType::class);
 
    }*/

    protected function configureDatagridFilters(DatagridMapper $datagrid): void
    {
        $datagrid->add('card.uid');
        $datagrid->add('amount');
        $datagrid->add('createdAt');
        $datagrid->add('createdBy');
    
    }

    protected function configureListFields(ListMapper $list): void
    {
        
        
        
        $list->add('card.uid');
	$list->add('card.cardHolder');
        $list->add('amount');
        $list->add('createdAt');
        $list->add('rechargeType');
        $list->add('oldBalance');
        $list->add('newBalance');
        $list->add('createdBy');
        $list->add('fromDate');
        $list->add('toDate');

        
    }

    protected function configureShowFields(ShowMapper $show): void
    {

        $show->add('card.uid', null, [
    'label' => 'Card UID'
]);
	$show->add('card.cardHolder');
        $show->add('amount');
        $show->add('createdAt');
        $show->add('createdBy');
        $show->add('oldBalance');
        $show->add('newBalance');
        $show->add('fromDate');
        $show->add('toDate');
        $show->add('oldFromDate');
        $show->add('oldToDate');



    }
    public function prePersist(object $recharge): void
    {
        //$user = $this->em->getRepository(User::class)->findBy(["username"])
        
       

    }

    public function preUpdate(object $recharge): void
    {
        //$ticket->setUpdatedAt(new \DateTime('now',new \DateTimeZone('Africa/Kinshasa')));
        


      
    }
    protected function configureExportFields(): array
    {
        return ['card.uid','card.cardHolder', 'amount', 'createdAt', 'createdBy','fromDate','toDate','reference'];
    }
    protected function createQueryBuilder($context)
    {
        die();
        // Get the default query builder from the parent class
        $qb = parent::createQueryBuilder($context);

        // Add a LEFT JOIN to include 'card' entity in the query
        $qb->leftJoin($qb->getRootAlias() . '.card', 'card')
            ->addSelect('card');

        return $qb;
    }
    protected function configureQuery(ProxyQueryInterface $query): ProxyQueryInterface
    {
        // Get the query builder from the ProxyQueryInterface
    $qb = $query->getQueryBuilder();

    // Use the confirmed root alias
    $rootAliases = $qb->getRootAliases();
    $rootAlias = $rootAliases[0]; // No need to check, as we know it's "o"

    // Add a LEFT JOIN to include 'card' entity in the query
    $qb->leftJoin("$rootAlias.card", 'card')
        ->addSelect('card');

        //die($qb->getQuery()->getSQL());

    return $query;
    }


}
