<?php

namespace App\Controller;

use App\Entity\NfcCard;
use App\Entity\Payment;
use App\Entity\Vehicle;
use App\Form\TrackRequestFormType;
use App\Repository\CurrencyRepository;
use App\Repository\NfcCardRepository;
use App\Repository\PaymentRepository;
use App\Repository\SubscriptionPlanRepository;
use App\Service\PaymentService;
use App\Service\RechargeService;
use App\Service\TransactionService;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class PublicController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly NfcCardRepository $cardRepository,
        private readonly SubscriptionPlanRepository $subscriptionPlanRepository,
        private readonly PaymentService $paymentService,
        private readonly PaymentRepository $paymentRepository,
        private readonly TransactionService $transactionService,
        private readonly RechargeService $rechargeService,
        private readonly CurrencyRepository $currencyRepository,
    ){}

    /**
     * @return Response
     */
    #[Route('/', name: 'app_public')]
    public function index(Request $request): Response
    {
        $form = $this->createForm(TrackRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $code = $data['code'];
            $type = $data['type'];

            return $this->redirectToRoute('buses_map', [
                'code' => $code,
                'type' => $type
            ]);
        }

        return $this->render('public/index.html.twig', [
            'controller_name' => 'PublicController',
            'form'            => $form->createView()
        ]);
    }

    #[Route('/bus/mapview/{code}/{type}', name:'buses_map')]
    public function mapView($code, $type): Response{
        $card = $this->em->getRepository(NfcCard::class)->findOneBy(['code'=> $code]);
        if (!$card) {
            $this->addFlash('error',"Code fourni ($code)  ne correspond en aucune carte.");
            return $this->redirectToRoute('app_public');
        }

        $lines = $card->getLiness();
        $vehicles = [];

        foreach ($lines as $line) {
            $lineVehicles = $this->em->getRepository(Vehicle::class)->findBy(['line' => $line]);
            foreach ($lineVehicles as $vehicle) {
                $vehicles[] = $vehicle->toArray();
            };
        }

        return $this->render('public/mapview2.html.twig', ['vehicles'=>$vehicles,"card"=>$card, "type"=>$type,"code"=>$code]);
    }

    /**
     * @throws \JsonException
     */
    #[Route('/recharge', name:'app_recharge')]
    public function recharge(Request $request): Response{
        $step = $request->getSession()->get('recharge_step') ?? 1;
        $subs = null;
        $type = $request->getSession()->get('recharge_type') ?? null;
        $paying = $request->getSession()->get('recharge_paying') ?? false;
        $paymentId = $request->getSession()->get('recharge_payment_id') ?? false;
        $subscriptionId =$request->getSession()->get('recharge_subscription_id') ?? null;
        $amount = $request->getSession()->get('recharge_amount') ?? null;

        $currency = $this->currencyRepository->findCurrentCurrency();

        if (empty($currency)) {
            $this->addFlash('error', 'Cette operation ne peut etre faite pour le moment. Veuillez ressayer plutard.');
            return $this->redirect($request->headers->get('referer'));
        }

        $card = null;
        $codeData = $request->getSession()->get('recharge_card') ?? null;

        if ($codeData != null) {
            $card = $this->cardRepository->findOneByCodeOrCardHolderOrUid($codeData);
        }

        if ($request->getMethod() === Request::METHOD_POST) {
            $cancel = $request->request->get("cancel");

            if (isset($cancel)) {
                $this->killRechargeProcess($request);
                return $this->redirectToRoute('app_recharge');
            }

            if ($step == 1) {
                $stepData = $this->rechargeService->processInfosStep($request, $step);
                if (!$stepData['success']) {
                    $this->addFlash('error', $stepData['message']);
                }
                return $this->redirect($request->headers->get('referer'));
            } else if ($step == 2) {
                $stepData = $this->rechargeService->processPaymentStep($request, $type, $card);
                if (!$stepData['success']) {
                    $this->addFlash('error', $stepData['message']);
                }
                return $this->redirect($request->headers->get('referer'));
            }
        }

        if ($type == "SUBSCRIPTION") {
            $subs = $this->subscriptionPlanRepository->findAll();
        }

        return $this->render('public/recharge.html.twig', [
            'step'      => $step,
            'type'      => $type,
            'cardUid'      => $card == null ? null : $card->getUid(),
            'subs'      => $subs,
            'paying'    => $paying,
            'card'      => $card,
            'paymentId' => $paymentId,
            'currency'  => $currency,
            'subscriptionId' => $subscriptionId,
            'amount' => $amount
        ]);
    }

    #[Route('/bus/mapview/api', name:'buses_map-api')]
    public function mapViewJson(Request $request): Response{
        $code = $request->request->get('code');
        $type = $request->request->get('type');
        $card = $this->em->getRepository(NfcCard::class)->findOneBy(['code'=> $code]);
        if(!$card){
            $this->addFlash('error',"Code fourni ($code)  ne correspond em aucune carte.");
            return $this->json(["message"=>"Code fourni ($code)  ne correspond em aucune carte."],404);
        }
        $vehicles = $card->getLiness()[0]->getVehicles();
        return $this->json($vehicles, 200);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */

    #[Route('/check-payment-status', name:'check_payment_status')]
    public function checkPaymentStatus(Request $request): JsonResponse {
        $paymentId = $request->query->get('paymentId');
        $type = $request->query->get('type');
        $cardUid = $request->query->get('cardUid');
        $subscriptionId = $request->query->get('subscriptionId');
        $amount = $request->query->get('amount');

        $payment = $this->paymentRepository->find($paymentId);
        if (!$payment) {
            return $this->json(['success' => false, 'message' => 'Payment not found', 'waiting' => false]);
        }

        $paymentStatusData = $this->paymentService->checkPaymentStatus($payment);
        $success = false;
        $waiting = false;

        if ($paymentStatusData['success']) {
            $payment->setPaid(true);
            $this->paymentRepository->save($payment);

            $subsResponse = $this->transactionService->recharge($type, $subscriptionId, $amount, $cardUid, $payment->getRef(), $payment->getPhoneNumber());
            if ($subsResponse['status']) {
                $payment->setRef($subsResponse['ref']);
                $this->paymentRepository->save($payment);
                $message = 'Payment successful';
                $success = true;
            } else {
                $message = $subsResponse['message'];
            }
        } elseif ($paymentStatusData['waiting']) {
            $message = 'Payment still processing';
            $waiting = true;
        } else {
            $message = 'Payment failed';
        }

        if (!$waiting) {
            $this->killRechargeProcess($request);
            $this->addFlash($success ? 'success' : 'error', $message);
        }

        return $this->json(['success' => $success, 'message' => $message, 'waiting' => $waiting]);
    }

    #[Route('/search-cards', name:'search_cards_match')]
    public function searchCard(Request $request): JsonResponse{
        $query = $request->query->get('query', '');
        $results = $this->em->getRepository(NfcCard::class)->findByLikeValue($query);

        $suggestions = array_map(function ($result) {
            return ['code' => $result->getCode(), 'cardHolder' => $result->getCardHolder(), 'uid'=>$result->getUid()];
        }, $results);
        return new JsonResponse($suggestions);

    }

    private function killRechargeProcess (Request $request) : void {
        $request->getSession()->remove('recharge_step');
        $request->getSession()->remove('recharge_type');
        $request->getSession()->remove('recharge_paying');
        $request->getSession()->remove('recharge_finish');
        $request->getSession()->remove('recharge_payment_id');
        $request->getSession()->remove('recharge_subscription_id');
    }
}
