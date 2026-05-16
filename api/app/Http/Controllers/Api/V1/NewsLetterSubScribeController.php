<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Customer\NewsLetterSubRequest;
use App\Services\Api\V1\Queue\NewsletterSubscribeService;
use OpenApi\Attributes as OA;

class NewsLetterSubScribeController extends Controller
{
    private $newsletter_sub_service;

    public function __construct(NewsletterSubscribeService $newsletterSubService)
    {
        $this->newsletter_sub_service = $newsletterSubService;

    }
    #[OA\Post(
        path: '/api/v1/newsletters/subscribe',
        tags: ['Newsletter'],
        summary: 'Subscribe newsletter by email',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'mail_sent_date', type: 'string', format: 'date', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Subscribed'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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
