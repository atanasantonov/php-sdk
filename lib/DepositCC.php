<?php

namespace Zotapay;

/**
 * Class DepositCC.
 */
class DepositCC extends Deposit
{
    /**
     * Make a deposit request to Zotapay API with credit card integration.
     *
     * @param \Zotapay\DepositOrder $order
     *
     * @return \Zotapay\DepositCCApiResponse
     */
    public function request($order, $is_direct = true)
    {   
        Zotapay::getLogger()->info('merchantOrderID #{merchantOrderID} Deposit CC direct deposit request.', ['merchantOrderID' => $order->getMerchantOrderID()]);
        $depositResponse = parent::request($order, true);
        if ($depositResponse->getDepositUrl()===null) {
            Zotapay::getLogger()->error('merchantOrderID #{merchantOrderID} Deposit CC getting url from first deposit request failed.', ['merchantOrderID' => $order->getMerchantOrderID()]);
            return new \Zotapay\DepositCCApiResponse(false);
        }
        
        // return directly mock response if available.
        $mockResponse = $this->getMockResponse();
        if (!empty($mockResponse)) {
            Zotapay::getLogger()->debug('Using mocked response for depositCC request.', []);
            $response = new \Zotapay\DepositCCApiResponse($mockResponse);
            return $response;
        }

        // setup data
        Zotapay::getLogger()->debug('merchantOrderID #{merchantOrderID} Deposit CC prepare post data.', ['merchantOrderID' => $order->getMerchantOrderID()]);
        $data = $this->prepare($order);
        $signed = $this->sign($data);

        // make the request
        Zotapay::getLogger()->info('Deposit CC request.');
        $request = $this->apiRequest->request('post', $depositResponse->getDepositUrl(), $signed);
        
        // set the response
        Zotapay::getLogger()->debug('merchantOrderID #{merchantOrderID} Deposit CC response.', ['merchantOrderID' => $order->getMerchantOrderID()]);
        $response = new \Zotapay\DepositCCApiResponse($request);

        return $response;
    }


    /**
     * @param \Zotapay\DepositOrder $order
     *
     * @return array
     */
    private function prepare($order)
    {
        return [
            'cardHolderName'    => $order->getCardHolderName(),
            'cardNumber'        => $order->getCardNumber(),
            'cardExpirationMonth' => $order->getCardExpirationMonth(),
            'cardExpirationYear' => $order->getCardExpirationYear(),
            'cardCvv'           => $order->getCardCvv(),
        ];
    }


    /**
     * @param array $data
     *
     * @return array
     */
    private function sign($data)
    {
        $dataToSign = [
            $data['cardNumber'],
            $data['cardHolderName'],
            $data['cardExpirationYear'],
            $data['cardExpirationMonth'],
            $data['cardCvv'],
            \Zotapay\Zotapay::getMerchantSecretKey(),
        ];

        $stringToSign = implode($dataToSign);

        return array(
            'data'      => $data,
            'signature' => hash('sha256', $stringToSign)
        );
    }
}
