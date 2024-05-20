<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\MultiSafePayService;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class CheckoutController
 * @package App\Controller
 */
class CheckoutController extends AbstractController
{
    /** @var MultiSafePayService */
    private $multiSafepayService;

    /** @var ValidatorInterface */
    private $validator;

    /**
     * CheckoutController constructor.
     * @param MultiSafePayService $multiSafepayService
     * @param ValidatorInterface $validator
     */
    public function __construct(MultiSafePayService $multiSafepayService, ValidatorInterface $validator)
    {
        $this->multiSafepayService = $multiSafepayService;
        $this->validator = $validator;
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
        $transactionId = $request->query->get('transactionid');

        return $this->render('checkout/success.html.twig', [
            'transactionId' => $transactionId,
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     * @Route("/process", name: "checkout_process")
     */
    #[Route('/process', name: 'checkout_process')]
    public function process(Request $request): Response
    {
        $requestData = $request->request->all();

        // Perform custom validation
        $validationErrors = $this->validate($requestData);

        if (!empty($validationErrors)) {
            return new JsonResponse(['success' => false, 'errors' => $validationErrors], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Proceed with processing the request
            $response = $this->multiSafepayService->createOrder(
                $requestData['price'],
                $requestData['quantity'],
                [
                    'addressLine' => $requestData['address_line'],
                    'city' => $requestData['city'],
                    'postalCode' => $requestData['postal_code'],
                    'firstname' => $requestData['firstname'],
                    'lastname' => $requestData['lastname'],
                    'number' => $requestData['number'],
                ]
            );

            return new JsonResponse(['success' => true, 'payment_url' => $response['paymentUrl']]);
        } catch (HttpExceptionInterface $e) {
            return new JsonResponse(['error' => $e->getMessage()], $e->getStatusCode());
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'An error occurred'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Custom validation method
     *
     * @param array $requestData
     * @return array Array of validation errors
     */
    private function validate(array $requestData): array
    {
        $validationErrors = [];

        // Create a validator
        $validator = $this->validator;

        // Define constraints for each field
        $constraints = new Assert\Collection([
            'price' => [
                new Assert\NotBlank(),
                new Assert\Type(['type' => 'numeric', 'message' => 'The price must be a numeric value.']),
            ],
            'total' => [
                new Assert\NotBlank(),
                new Assert\Type(['type' => 'numeric', 'message' => 'The total must be a numeric value.']),
            ],
            'quantity' => [
                new Assert\NotBlank(),
                new Assert\Type(['type' => 'numeric', 'message' => 'The quantity must be a numeric value.']),
            ],
            'address_line' => [
                new Assert\NotBlank(['message' => 'The address line is required.']),
                new Assert\Type(['type' => 'string', 'message' => 'The address line must be a string.']),
            ],
            'city' => [
                new Assert\NotBlank(['message' => 'The city is required.']),
                new Assert\Type(['type' => 'string', 'message' => 'The city must be a string.']),
            ],
            'postal_code' => [
                new Assert\NotBlank(['message' => 'The postal code is required.']),
                new Assert\Type(['type' => 'string', 'message' => 'The postal code must be a string.']),
            ],
            'firstname' => [
                new Assert\NotBlank(['message' => 'The first name is required.']),
                new Assert\Type(['type' => 'string', 'message' => 'The first name must be a string.']),
            ],
            'lastname' => [
                new Assert\NotBlank(['message' => 'The last name is required.']),
                new Assert\Type(['type' => 'string', 'message' => 'The last name must be a string.']),
            ],
            'number' => [
                new Assert\NotBlank(['message' => 'The number is required.']),
                new Assert\Type(['type' => 'string', 'message' => 'The number must be a string.']),
            ],
        ]);

        // Validate the request data
        $violations = $validator->validate($requestData, $constraints);

        // Convert violations to validation errors
        foreach ($violations as $violation) {
            $validationErrors[] = $violation->getMessage();
        }

        return $validationErrors;
    }
}
