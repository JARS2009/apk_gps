<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class PushNotificationController extends Controller
{
    /**
     * Store a push subscription for the authenticated user.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint'   => 'required|url',
            'keys.auth'  => 'required|string',
            'keys.p256dh'=> 'required|string',
        ]);

        PushSubscription::updateOrCreate(
            [
                'user_id'  => $request->user()->id,
                'endpoint' => $request->input('endpoint'),
            ],
            [
                'public_key' => $request->input('keys.p256dh'),
                'auth_token' => $request->input('keys.auth'),
                'user_agent' => $request->userAgent(),
            ]
        );

        return response()->json(['status' => 'subscribed']);
    }

    /**
     * Remove a push subscription.
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        $request->validate(['endpoint' => 'required|url']);

        PushSubscription::where('user_id', $request->user()->id)
            ->where('endpoint', $request->input('endpoint'))
            ->delete();

        return response()->json(['status' => 'unsubscribed']);
    }

    /**
     * Send a test push notification to the authenticated user.
     */
    public function sendTest(Request $request): JsonResponse
    {
        $subscriptions = PushSubscription::where('user_id', $request->user()->id)->get();

        if ($subscriptions->isEmpty()) {
            return response()->json(['error' => 'No subscriptions found'], 404);
        }

        $auth = [
            'VAPID' => [
                'subject'    => config('app.url'),
                'publicKey'  => config('services.vapid.public_key'),
                'privateKey' => config('services.vapid.private_key'),
            ],
        ];

        $webPush = new WebPush($auth);

        $payload = json_encode([
            'title' => 'Agro-Rastreo',
            'body'  => '¡Las notificaciones push están funcionando! 🎉',
            'icon'  => '/icons/icon-192x192.png',
            'url'   => '/',
        ]);

        foreach ($subscriptions as $sub) {
            $webPush->queueNotification(
                Subscription::create([
                    'endpoint'        => $sub->endpoint,
                    'keys'            => ['auth' => $sub->auth_token, 'p256dh' => $sub->public_key],
                    'contentEncoding' => 'aesgcm',
                ]),
                $payload
            );
        }

        foreach ($webPush->flush() as $report) {
            if (!$report->isSuccess()) {
                PushSubscription::where('endpoint', $report->getRequest()->getUri()->__toString())->delete();
            }
        }

        return response()->json(['status' => 'sent']);
    }

    /**
     * Broadcast a push notification to all subscriptions of a user.
     * Called internally by the application when alerts are triggered.
     */
    public static function notifyUser(int $userId, string $title, string $body, string $url = '/'): void
    {
        $subscriptions = PushSubscription::where('user_id', $userId)->get();
        if ($subscriptions->isEmpty()) return;

        $auth = [
            'VAPID' => [
                'subject'    => config('app.url'),
                'publicKey'  => config('services.vapid.public_key'),
                'privateKey' => config('services.vapid.private_key'),
            ],
        ];

        $webPush = new WebPush($auth);
        $payload = json_encode(compact('title', 'body', 'url'));

        foreach ($subscriptions as $sub) {
            $webPush->queueNotification(
                Subscription::create([
                    'endpoint'        => $sub->endpoint,
                    'keys'            => ['auth' => $sub->auth_token, 'p256dh' => $sub->public_key],
                    'contentEncoding' => 'aesgcm',
                ]),
                $payload
            );
        }

        foreach ($webPush->flush() as $report) {
            if (!$report->isSuccess()) {
                PushSubscription::where('endpoint', $report->getRequest()->getUri()->__toString())->delete();
            }
        }
    }
}
