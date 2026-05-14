<?php

class PaymentService
{
    protected $baseApiUrl;
    protected $apiToken;
    public function __construct()
    {
        $this->baseApiUrl = 'https://api.bakongrelay.com';
        $this->apiToken = 'rbkRpbTcb0eSGHlUqo0DI_GjM-ajpDQJuUwVEDUctyZ2uQ';

    }

    public function generateQR()
    {

    }
}