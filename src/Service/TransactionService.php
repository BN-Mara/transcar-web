<?php

namespace App\Service;

use App\Entity\NfcCard;
use App\Entity\RechargeCarte;
use App\Entity\SubscriptionPlan;
use App\Entity\User;
use App\Helper\StringGenerator;
use App\Repository\CurrencyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TransactionService extends AbstractController
{
    private StringGenerator $generator;

    public function __construct(
        private readonly CurrencyRepository $currencyRepository,
        private readonly EntityManagerInterface $em,
    ) {
        $this->generator = new StringGenerator();
    }

    public function recharge($type, $subsId, $amount, $cardUid, $refs = "",$phone = ""): array {
        $currency = $this->currencyRepository->findCurrentCurrency();
        $subs = $type === "SUBSCRIPTION" ? $this->em->getRepository(SubscriptionPlan::class)->find($subsId) : null;

        if ($type === "SUBSCRIPTION" && !$subs) {
            return ["status" => false, "message" => "Subscription Plan invalid"];
        }

        $user = $this->getUser();
        $card = $this->em->getRepository(NfcCard::class)->findOneBy(["uid" => $cardUid]);

        if (!$card) {
            return ["status" => false, "message" => "Votre carte est invalide."];
        }

        if (!$card->isIsActive()) {
            return ["status" => false, "message" => "Votre carte est invalide."];
        }

        if ($user && ($user->getBalance() - $amount) < 0) {
            return ["status" => false, "message" => "Votre balance est insuffisante."];
        }

        $date = new \DateTime('now', new \DateTimeZone('Africa/Kinshasa'));
        $trans = (new RechargeCarte())
            ->setAmount($amount)
            ->setCard($card)
            ->setCreatedAt($date)
            ->setRechargeType($type)
            ->setReference($refs)
            ->setCreatedBy($user ? $user->getUsername() : $phone);

        if ($type === "SUBSCRIPTION") {
            $durationInDays = $subs->getDuration();
            $now = (clone $date)->add(new \DateInterval("P{$durationInDays}D"));
            $card->setSubscriptionFromDate($date);
            $card->setSubscriptionEndDate($now);
            $trans->setFromDate($date)->setToDate($now);
            $trans->setSubscriptionId($subsId)->setRechargeType($type);
        } else {
            $oldBalance = $card->getBalance() ?? 0;
            $newBalance = $oldBalance + $amount;
            $card->setBalance($newBalance);
            $trans->setOldBalance($oldBalance)->setNewBalance($newBalance);
        }

        if ($user) {
            $balance = $user->getBalance() ?? 0;
            $user->setBalance($user->getBalance() - $amount);
            $this->em->persist($user);
        }

        $this->em->persist($card);
        $this->em->persist($trans);
        $this->em->flush();

        return [
            "status" => true,
            "message" => "Recharge effectuée avec succès",
            "balance" => $user ? $user->getBalance() : null,
            "ref" => $trans->getReference()
        ];
    }
}