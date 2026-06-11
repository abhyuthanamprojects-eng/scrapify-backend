<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\ContactMessage;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PageController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get page details by slug.
     */
    public function show($slug)
    {
        $page = Page::where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (!$page) {
            return $this->errorResponse('page.not_found', 404);
        }

        return $this->successResponse('page.fetched', $page);
    }

    /**
     * Submit contact message.
     */
    public function submitContact(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|regex:/^[6-9]\d{9}$/',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $contact = ContactMessage::create($request->all());

            // Send notification email to support team
            try {
                \Illuminate\Support\Facades\Mail::raw(
                    "New Contact Message Received:\n\n" .
                    "Name: {$contact->name}\n" .
                    "Email: {$contact->email}\n" .
                    "Phone: " . ($contact->phone ?? 'N/A') . "\n" .
                    "Subject: " . ($contact->subject ?? 'No Subject') . "\n\n" .
                    "Message:\n{$contact->message}",
                    function ($message) use ($contact) {
                        $message->to('support.team@scrapi5.com')
                            ->subject('New Contact Inquiry: ' . ($contact->subject ?? 'No Subject'));
                    }
                );
            } catch (\Exception $mailEx) {
                \Illuminate\Support\Facades\Log::error('Failed to send contact inquiry email: ' . $mailEx->getMessage());
            }

            return $this->successResponse('contact.submitted', $contact, 201);
        } catch (\Exception $e) {
            return $this->errorResponse('server.error', 500, $e->getMessage());
        }
    }
}
