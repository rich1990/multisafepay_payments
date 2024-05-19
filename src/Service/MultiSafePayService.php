<?php

namespace App\Service;

use MultiSafepay\Sdk;
use MultiSafepay\ValueObject\Customer\Country;
use MultiSafepay\ValueObject\Customer\Address;
use MultiSafepay\ValueObject\Customer\PhoneNumber;
use MultiSafepay\ValueObject\Customer\EmailAddress;
use MultiSafepay\ValueObject\Money;
use MultiSafepay\ValueObject\Weight;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\CustomerDetails;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\PluginDetails;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\PaymentOptions;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\ShoppingCart;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\ShoppingCart\Item;

/**
 * Class MultiSafePayService
 * @package App\Service
 */
class MultiSafePayService
{
    /** @var string */
    private $apiKey;

    /** @var bool */
    private $isProduction;

    /** @var float */
    private $taxRate;

    /** @var float */
    private $taxRateMultiplier;

    /**
     * MultiSafePayService constructor.
     */
    public function __construct()
    {
        $this->apiKey = $_SERVER['MULTI_SAFE_PAY_API_KEY'];
        $this->isProduction = $_SERVER['MULTI_SAFE_PAY_IS_PRODUCTION'];
        $this->taxRate = $_SERVER['MULTI_SAFE_PAY_TAX_RATE'];
        $this->taxRateMultiplier = $_SERVER['MULTI_SAFE_PAY_TAX_RATE'] / 100;
    }

    /**
     * Create an order.
     *
     * @param $product_amount
     * @param $paymentType
     * @param $quantity
     * @param $addressData
     * @return array
     */
    public function createOrder($product_amount, $quantity, $addressData)
    {
        try {
            
            $sub_total = $product_amount * $quantity;
            // Initialize MultiSafepay Client
            $multiSafepaySdk = new Sdk($this->apiKey, false);
            $tax_amount = (int) $sub_total * $this->taxRateMultiplier; // Amount must be in cents

            $orderId = (string) time();
            $description = 'Order #' . $orderId;
            $address = (new Address())
                ->addStreetName($addressData['addressLine'])
                ->addHouseNumber($addressData['number'])
                ->addZipCode($addressData['postalCode'])
                ->addCity($addressData['city'])
                ->addCountry(new Country('NL'));

            $customer = (new CustomerDetails())
                ->addFirstName($addressData['firstname'])
                ->addLastName($addressData['lastname'])
                ->addAddress($address)
                ->addEmailAddress(new EmailAddress('noreply@example.org'))
                ->addPhoneNumber(new PhoneNumber('0208500500'))
                ->addLocale('en_US');

            $paymentOptions = (new PaymentOptions())
                ->addNotificationUrl('http://multisafepay.test/success')
                ->addRedirectUrl('http://multisafepay.test/success')
                ->addCancelUrl('http://multisafepay.test/')
                ->addCloseWindow(true);

            $items[] = (new Item())
                ->addName('MultiSafePay hoodie')
                ->addUnitPrice(new Money($product_amount * 100, 'EUR')) // Amount must be in cents
                ->addQuantity($quantity)
                ->addDescription('COOL MultiSafePay hoodie')
                ->addTaxRate($this->taxRate)
                ->addMerchantItemId('1')
                ->addWeight(new Weight('KG', 1));


            $orderRequest = (new OrderRequest())
                ->addType('redirect')
                ->addOrderId($orderId)
                ->addDescriptionText($description)
                ->addMoney(new Money(($sub_total + $tax_amount) * 100, 'EUR'))
                ->addGatewayCode('IDEAL')
                ->addCustomer($customer)
                ->addDelivery($customer)
                ->addPaymentOptions($paymentOptions)
                ->addShoppingCart(new ShoppingCart($items));

            /** @var TransactionResponse $transaction */
            $transactionManager = $multiSafepaySdk->getTransactionManager()->create($orderRequest);
            $payment_url = $transactionManager->getPaymentUrl();

            $response = ['paymentUrl' => $payment_url];
            return $response;

        } catch (ApiException $e) {
            echo 'API Error: ' . $e->getCode() . $e->getMessage();
        } catch (ConnectionException $e) {
            echo 'Connection Error: ' . $e->getCode() . $e->getMessage();
        } catch (\Exception $e) {
            echo 'An error occurred: ' . $e->getCode() . $e->getMessage();
        }
    }
}