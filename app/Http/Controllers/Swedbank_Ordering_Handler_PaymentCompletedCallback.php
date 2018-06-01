<?php

namespace App\Http\Controllers;


use Illuminate\Support\Facades\Log;
use SwedbankPaymentPortal\BankLink\CommunicationEntity\HPSQueryResponse\HPSQueryResponse;
use SwedbankPaymentPortal\BankLink\CommunicationEntity\NotificationQuery\ServerNotification;
use SwedbankPaymentPortal\CallbackInterface;
use SwedbankPaymentPortal\CC\PaymentCardTransactionData;
use SwedbankPaymentPortal\SharedEntity\Type\TransactionResult;
use SwedbankPaymentPortal\Transaction\TransactionFrame;

class Swedbank_Ordering_Handler_PaymentCompletedCallback implements CallbackInterface
{

    private $merchantReferenceId;

    public function __construct($merchantReferenceId)
    {
        $this->merchantReferenceId = $merchantReferenceId;
    }

    /**
     * Method for handling finished transaction which ended because of the specified response status.
     *
     * @param TransactionResult         $status
     * @param TransactionFrame          $transactionFrame
     * @param PaymentCardTransactionData $creditCardTransactionData
     */
    public function handleFinishedTransaction(TransactionResult $status,
                                              TransactionFrame $transactionFrame,
                                              PaymentCardTransactionData $creditCardTransactionData = null)
    {
        if ($status == TransactionResult::success()) {
           Log::info('---------------success');
        } else if ($status == TransactionResult::failure()) {
            // failure. Do some action here
        } else {
            // unfinished payment
        }
        // This is only for debug. You can log into file if needed.
        mail('YourEmail@domain.lt',
            'DONE', print_r($status, true).print_r($transactionFrame, true).print_r($creditCardTransactionData, true));

    }

    public function serialize()
    {
        return json_encode(
            [
                'merchantReferenceId' => $this->merchantReferenceId
            ]
        );
    }

    public function unserialize($serialized)
    {
        $data = json_decode($serialized);

        $this->merchantReferenceId = $data->merchantReferenceId;
    }
}