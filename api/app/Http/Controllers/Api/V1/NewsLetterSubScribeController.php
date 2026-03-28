<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Customer\NewsLetterSubRequest;
use App\Services\Api\V1\Queue\NewsletterSubscribeService;
// use Illuminate\Http\Request;
// use OpenApi\Attributes as OA;

class NewsLetterSubScribeController extends Controller
{
    private $newsletter_sub_service;

    public function __construct(NewsletterSubscribeService $newsletterSubService)
    {
        $this->newsletter_sub_service = $newsletterSubService;

    }
    //**
    // Subscribe Mail
    //  */
    // #[OA\Post(

    // )]

    public function subscribeMail(NewsLetterSubRequest $request)
    {
        //   validation
        $data = $request->validated();
        try {
            $this->newsletter_sub_service->sendMailSubscriber($data);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
