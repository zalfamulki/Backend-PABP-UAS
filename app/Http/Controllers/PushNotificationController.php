<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class PushNotificationController extends Controller
{
    public function subscribe(Request $request)
    {
        $request->validate([
            'endpoint' => 'required|string',
            'keys' => 'required|array',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
        ]);

        $user = Auth::user();

        PushSubscription::updateOrCreate(
            [
                'user_id' => $user->id,
                'endpoint' => $request->endpoint,
            ],
            [
                'p256dh' => $request->keys['p256dh'],
                'auth' => $request->keys['auth'],
            ]
        );

        return response()->json(['message' => 'Subscribed successfully']);
    }

    public function unsubscribe(Request $request)
    {
        $request->validate([
            'endpoint' => 'required|string',
        ]);

        PushSubscription::where('user_id', Auth::id())
            ->where('endpoint', $request->endpoint)
            ->delete();

        return response()->json(['message' => 'Unsubscribed successfully']);
    }

    public static function sendToUser(int $userId, string $title, string $body, array $data = []): void
    {
        try {
            $subscriptions = PushSubscription::where('user_id', $userId)->get();

            if ($subscriptions->isEmpty()) return;

            $auth = [
                'VAPID' => [
                    'subject' => config('web-push.vapid.subject'),
                    'publicKey' => config('web-push.vapid.public_key'),
                    'privateKey' => config('web-push.vapid.private_key'),
                ],
            ];

            $webPush = new WebPush($auth);

            $payload = json_encode([
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'icon' => '/icon-192.png',
                'badge' => '/icon-192.png',
            ]);

            foreach ($subscriptions as $sub) {
                $subscription = Subscription::create([
                    'endpoint' => $sub->endpoint,
                    'authToken' => $sub->auth,
                    'publicKey' => $sub->p256dh,
                ]);

                $webPush->queueNotification($subscription, $payload);
            }

            foreach ($webPush->flush() as $report) {
                if (!$report->isSuccess()) {
                    $endpoint = $report->getEndpoint();
                    PushSubscription::where('endpoint', $endpoint)->delete();
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Push notification failed: ' . $e->getMessage(), [
                'user_id' => $userId,
                'title' => $title,
            ]);
        }
    }
}
