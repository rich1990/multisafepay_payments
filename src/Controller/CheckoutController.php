<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\MultiSafePayService;

/**
 * Class CheckoutController
 * @package App\Controller
 */
class CheckoutController extends AbstractController
{
     /** @var MultiSafePayService */
    private $multiSafepayService;

    /**
     * CheckoutController constructor.
     * @param MultiSafePayService $multiSafepayService
     */
    public function __construct(MultiSafePayService $multiSafepayService)
    {
        $this->multiSafepayService = $multiSafepayService;
    }

    /**
     * @return Response
     * @Route("/", name="app_checkout")
     */
    #[Route('/', name: 'app_checkout')]
    public function index(): Response
    {
        return $this->render('checkout/index.html.twig');
    }

    /**
     * @param Request $request
     * @return Response
     * @Route("/success", name="checkout_success")
     */
    #[Route('/success', name: 'checkout_success')]
    public function success(Request $request): Response
    {
      // Get the transaction ID from the query string
        $transactionId = $request->query->get('transactionid');

        return $this->render('checkout/success.html.twig', [
            'transactionId' => $transactionId,
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     * @Route("/process", name="checkout_process")
     */
    #[Route('/process', name: 'checkout_process')]
    public function process(Request $request): Response
    {

        try {
            $amount = $request->request->get('total');
            $paymentType = $request->request->get('payment_type');
            
            $postData = $this->processAddressPostData($request);

            // Call the createOrder method from the MultiSafepayService
            $response = $this->multiSafepayService->createOrder($amount, $postData);

            return new JsonResponse(['success' => true, 'payment_url' => $response['paymentUrl']]);
        } catch (HttpExceptionInterface $e) {
            // If an HTTP exception occurs (e.g., 404, 403), return a JSON response with the error message and status code
            return new JsonResponse(['error' => $e->getMessage()], $e->getStatusCode());
        } catch (\Throwable $e) {
            // For other types of exceptions, return a JSON response with a generic error message and status code 500
            return new JsonResponse(['error' => 'An error occurred'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

      
    }

    /**
     * @param Request $request
     * @return array
     */
    private function processAddressPostData(Request $request): array {
        // Collecting fields from the request
        $addressLine = $request->request->get('address_line');
        $number = $request->request->get('number');
        $city = $request->request->get('city');
        $postalCode = $request->request->get('postal_code');
        $country = $request->request->get('country');
        $paymentType = $request->request->get('payment_type');
        $firstname = $request->request->get('firstname');
        $lastname = $request->request->get('lastname');

        // Organizing the collected fields into an associative array
        $data = [
            'addressLine' => $addressLine,
            'city' => $city,
            'postalCode' => $postalCode,
            'country' => $country,
            'paymentType' => $paymentType,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'number' => $number,
        ];

        return $data;
    }
}