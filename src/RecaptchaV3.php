<?php
/**
 * Created by Josias Montag
 * Date: 10/30/18 11:04 AM
 * Mail: josias@montag.info
 */

namespace Lunaweb\RecaptchaV3;


use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Contracts\Config\Repository;

class RecaptchaV3
{

    const VERIFY_URL = '/recaptcha/api/siteverify';

    /**
     * @var string
     */
    protected $secret;
    /**
     * @var string
     */
    protected $sitekey;
    /**
     * @var \GuzzleHttp\Client
     */
    protected $http;

    /**
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * RecaptchaV3 constructor.
     *
     * @param $secret
     * @param $sitekey
     */
    public function __construct(Repository $config, Client $client, Request $request)
    {
        $this->secret = $config['recaptchav3']['secret'];
        $this->sitekey = $config['recaptchav3']['sitekey'];
        $this->http = $client;
        $this->request = $request;
    }


    /*
     * Verify the given token and retutn the score.
     * Returns false if token is invalid.
     * Returns the score if the token is valid.
     *
     * @param $token
     */
    public function verify($token, $action = null)
    {

        $response = $this->http->request('POST', config('recaptchav3.origin') . static::VERIFY_URL, [
            'form_params' => [
                'secret'   => $this->secret,
                'response' => $token,
                'remoteip' => $this->request->getClientIp(),
            ],
        ]);


        $body = json_decode($response->getBody(), true);

        if (!isset($body['success']) || $body['success'] !== true) {
            return false;
        }

        if ($action && (!isset($body['action']) || $action != $body['action'])) {
            return false;
        }


        return isset($body['score']) ? $body['score'] : false;

    }


    /**
     * @return string
     */
    public function sitekey()
    {
        return $this->sitekey;
    }

    /**
     * @return string
     */
    public function initJs()
    {
        return '<script src="' . config('recaptchav3.origin', 'https://www.google.com') . '/recaptcha/api.js?onload=initField&render=' . $this->sitekey . '"></script>';
    }


    /**
     * @param $action
     */
    public function field($action, $name = 'g-recaptcha-response', $callback = 'callback_empty')
    {
        $fieldId = uniqid($name . '-', false);
        $html = '<input type="hidden" name="' . $name . '" id="' . $fieldId . '">';
        $html .= "<script>
        window.callback_empty = function (token){}
        window.initField = function (){
            grecaptcha.ready(function() {
                grecaptcha.execute('" . $this->sitekey . "', {action: '" . $action . "'}).then(function(token) {
                    document.getElementById('" . $fieldId . "').value = token;
                    " . $callback . "(token);
                });
            });
        };
        </script>";
        return $html;
    }
}
