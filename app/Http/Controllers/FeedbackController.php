<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\SendFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class FeedbackController extends Controller
{
    public function create(Request $request)
    {
        $name = $request->input('name') ? $request->input('name') : 'Not provided';
        $email = $request->input('email') ? $request->input('email') : 'Not provided';
        $feedback = $request->input('feedback');

        Mail::to('jeff@trainspotter.co')->send(new SendFeedback($name, $email, $feedback));

        if (Mail::failures() != 0) {
            return "Success! Your feedback has been sent.";
        }

        else {
            return "Failed! There was a problem sending your feedback.";
        }
    }
}