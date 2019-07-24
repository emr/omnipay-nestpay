<?php
namespace Omnipay\NestPay\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RequestInterface;
use Omnipay\Common\Message\RedirectResponseInterface;
use Omnipay\Common\Exception\InvalidResponseException;
use Psr\Http\Message\ResponseInterface;

/**
 * NestPay Response
 *
 * (c) Yasin Kuyu
 * 2015, insya.com
 * http://www.github.com/yasinkuyu/omnipay-nestpay
 */
class Response extends AbstractResponse implements RedirectResponseInterface
{
    protected $statusCode;

    /**
     * Constructor
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @throws InvalidResponseException
     */
    public function __construct(RequestInterface $request, ResponseInterface $response)
    {
        try {
            $data = (array) simplexml_load_string((string) $response->getBody());
        } catch (\Exception $ex) {
            throw new InvalidResponseException();
        }

        $this->statusCode = $response->getStatusCode();

        parent::__construct($request, $data);
    }

    /**
     * Whether or not response is successful
     *
     * @return bool
     */
    public function isSuccessful()
    {
        if (isset($this->data["ProcReturnCode"])) {
            return (string) $this->data["ProcReturnCode"] === '00' || $this->data["Response"] === 'Approved';
        } else {
            return false;
        }
    }

    /**
     * Get is redirect
     *
     * @return bool
     */
    public function isRedirect()
    {
        return false; // todo
    }

    /**
     * Get a code describing the status of this response.
     *
     * @return int code
     */
    public function getCode()
    {
        return $this->statusCode;
    }

    /**
     * Get transaction reference
     *
     * @return string
     */
    public function getTransactionReference()
    {
        return $this->isSuccessful() ? $this->data["TransId"] : '';
    }

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage()
    {
        if ($this->isSuccessful()) {
            $moneyPoints = $this->data["Extra"]->KULLANILABILIRBONUS;
            if (! empty($moneyPoints))
                return (string) $this->data["Response"] . '. Available money points : ' . $moneyPoints;
            else
                return $this->data["Response"];
        }
        return $this->data["ErrMsg"];
    }

    /**
     * Get error
     *
     * @return string
     */
    public function getError()
    {
        return $this->data["ErrMsg"];
    }

    /**
     * Get Redirect url
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        if ($this->isRedirect()) {
            $data = array(
                'TransId' => $this->data["TransId"]
            );
            return $this->getRequest()->getEndpoint() . '/test/index?' . http_build_query($data);
        }
    }

    /**
     * Get Redirect method
     *
     * @return POST
     */
    public function getRedirectMethod()
    {
        return 'POST';
    }

    /**
     * Get Redirect url
     *
     * @return null
     */
    public function getRedirectData() {
        return null;
    }

}
