<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use SwedbankPaymentPortal\Options\CommunicationOptions;
use SwedbankPaymentPortal\Options\ServiceOptions;
use SwedbankPaymentPortal\SharedEntity\Authentication;
use SwedbankPaymentPortal\SwedbankPaymentPortal;
use SwedbankPaymentPortal\SharedEntity\Amount;
use SwedbankPaymentPortal\CC\HPSCommunicationEntity\SetupRequest\SetupRequest;
use SwedbankPaymentPortal\CC\HPSCommunicationEntity\SetupRequest\Transaction;
use SwedbankPaymentPortal\CC\Type\ScreeningAction;
use SwedbankPaymentPortal\CC\Type\TransactionChannel;
use SwedbankPaymentPortal\CC\HPSCommunicationEntity\SetupRequest\Transaction\TxnDetails;
use SwedbankPaymentPortal\CC\HPSCommunicationEntity\SetupRequest\Transaction\ThreeDSecure;
use SwedbankPaymentPortal\CC\HPSCommunicationEntity\SetupRequest\Transaction\CardTxn;

class PaymentController extends Controller
{
    public function create(){
        $auth = new Authentication(env('SWEDBANK_CARD_CLIENT'), env('SWEDBANK_CARD_PASS')); // VtID and password
        // Generating unique merchant reference. To generate merchant reference
        //please use your one logic. This is only example.
        $merchantReferenceId = 'ID235r' . strtotime('now');
        $purchaseAmount = '4.99'; // Euro and cents needs to be separated by dot.

        $options = new ServiceOptions(
            new CommunicationOptions(
                'https://accreditation.datacash.com/Transaction/acq_a' //this is test environment
            // for production/live use this URL: https://mars.transaction.datacash.com/Transaction
            ), $auth
        );

        SwedbankPaymentPortal::init($options);  // <- library  initiation
        $spp = SwedbankPaymentPortal::getInstance();  // <- library usage

        $riskAction = new Transaction\Action(
            ScreeningAction::preAuthorization(), new Transaction\MerchantConfiguration(
            TransactionChannel::web(), 'Vilnius' //Merchant location (city)
        ), new Transaction\CustomerDetails(
                new Transaction\BillingDetails(// Customer details
                    'Mr', // title
                    'Name Surname', // Name and surname
                    'Zip0000', // Post code
                    'Street address', // address line 1
                    '', // address line 2
                    'London', // City
                    'UK' // Country
                ), new Transaction\PersonalDetails(// Personal details
                'Name', // Required, Card holder name
                'Surname', // Required. Card holder surname
                '+3705555555' // Required. Card holder phone
            ), new Transaction\ShippingDetails(// Shipping details
                'Mr', // title
                'Name', // name
                'Surname', // surname
                'Street address', // address line 1
                '', // address line 2
                'City', // City
                'UK', // Country
                'Zip0000' // Post code
            ), new Transaction\RiskDetails(
                    '127.15.21.55', // Required. Card holder IP address
                    'test@test.lt' // Required. Card holder email
                )
            )
        );

        $txnDetails = new TxnDetails(
            $riskAction, $merchantReferenceId, new Amount($purchaseAmount), new ThreeDSecure(
                'Order nr: ' . $merchantReferenceId, 'http://swedbank.localhost', new \DateTime()
            )
        );

        $hpsTxn = new Transaction\HPSTxn(
            'http://swedbank.localhost/expireway=expiry&order_id=' . $merchantReferenceId, // expire url
            'http://swedbank.localhost/return?way=confirmed&order_id=' . $merchantReferenceId, // return url
            'http://swedbank.localhost/error?way=cancelled&order_id=' . $merchantReferenceId, // error url
            164, // Page set ID
            // Firs field to show in card input form Name and Surname field.
            //Firs parameter goes as string 'show' or null. Second field is url for back button in card input form.
            new Transaction\DynamicData(null, 'http://sppdemoshop.eu/')
        );

        $transaction = new Transaction($txnDetails, $hpsTxn, new CardTxn());
        $setupRequest = new SetupRequest($auth, $transaction);
        $response = $spp->getPaymentCardHostedPagesGateway()->initPayment(
            $setupRequest,
            new Swedbank_Ordering_Handler_PaymentCompletedCallback($merchantReferenceId)
        );
        $url = $response->getCustomerRedirectUrl(); // Getting redirect url

        return redirect($url);

        header('Location: ' . $url); // redirecting card holder to card input form.
    }

    public function confirm(){
        $orderId = $_GET['order_id'];
        $way  = $_GET['way'];

        if ($way == 'confirmed'){
            $auth = new Authentication(env('SWEDBANK_CARD_CLIENT'),env('SWEDBANK_CARD_PASS'));
            $options = new ServiceOptions(
                new CommunicationOptions(
                    'https://accreditation.datacash.com/Transaction/acq_a' //this is test environment
                // for production/live use this URL: https://mars.transaction.datacash.com/Transaction
                ),
                $auth
            );
            SwedbankPaymentPortal::init($options);  // <- library  initiation
            $spp = SwedbankPaymentPortal::getInstance();  // <- library usage

            $rez = $spp->getPaymentCardHostedPagesGateway()->handlePendingTransaction($orderId);
            // now you can show user "thank you for your payment, but don't put flag
            //flag need to put inside callback


            Log::info('Thank you');
            echo 'Thank you';
        } else if ($way == 'expiry'){
            Log::info('Session expired');
            echo 'Session expired';
            // do same logic if seesion expired
        } else { // cancelled
            Log::info('Payment cancelled');
            echo 'Payment cancelled';
            // do some action for cancel logic
        }
    }

}
