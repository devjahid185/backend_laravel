<?php

namespace App\Services;

use App\Jobs\SendTelegramAdminAlert;
use App\Models\TelegramAdminSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TelegramAdminEventService
{
    private const FIELD_LABELS = [
        'order_no' => 'Order',
        'id' => 'Reference ID',
        'name' => 'Name',
        'receiver_name' => 'Customer',
        'customer_name' => 'Customer',
        'phone' => 'Phone',
        'receiver_phone' => 'Phone',
        'mobile' => 'Phone',
        'restaurant_name' => 'Restaurant',
        'status' => 'Status',
        'payment_method' => 'Payment',
        'payment_status' => 'Payment Status',
        'grand_total' => 'Grand Total',
        'total' => 'Total',
        'delivery_fee' => 'Delivery Charge',
        'discount' => 'Discount',
        'coupon_code' => 'Coupon',
        'address' => 'Address',
        'delivery_address' => 'Address',
        'receiver_address' => 'Address',
        'subject' => 'Subject',
        'title' => 'Title',
        'message' => 'Message',
        'note' => 'Note',
        'order_note' => 'Order Note',
        'rating' => 'Rating',
    ];

    public function notifyForRequest(Request $request, Response $response): void
    {
        $settings = TelegramAdminSetting::current();
        if (! $settings->is_enabled || ! $settings->hasToken()) {
            return;
        }

        $event = $this->classify($request);
        if (! $event || ! $settings->eventEnabled($event['type'])) {
            return;
        }

        $responseData = $this->responseData($response);
        $event = $this->refineEvent($request, $responseData, $event);
        $message = $this->formatMessage($request, $responseData, $event, $settings->include_details);
        $portalUrl = rtrim($settings->portal_url ?: 'https://portal.bholavashi.site', '/').$event['route'];

        SendTelegramAdminAlert::dispatch($message, $portalUrl, $event['button']);
    }

    private function refineEvent(Request $request, array $responseData, array $event): array
    {
        if (($event['path'] ?? null) !== 'food/owner/items') {
            return $event;
        }

        $item = $responseData['item'] ?? null;
        $itemId = is_array($item) ? ($item['id'] ?? null) : null;
        if ($request->filled('id')) {
            $event['title'] = 'Restaurant Item Updated';
            $event['button'] = $itemId ? "View Updated Item #{$itemId}" : 'View Updated Item';
        } else {
            $event['title'] = 'New Restaurant Item Submitted';
            $event['button'] = $itemId ? "Review New Item #{$itemId}" : 'Review New Item';
        }

        return $event;
    }

    private function classify(Request $request): ?array
    {
        $path = preg_replace('#^api/#', '', trim($request->path(), '/')) ?: '';
        $method = strtoupper($request->method());
        if (! in_array($method, ['POST', 'PUT', 'PATCH'], true) || str_starts_with($path, 'admin/')) {
            return null;
        }

        $rules = [
            ['#^food/checkout$#', 'orders', 'New Food Order', '/admin/food-orders', 'View Food Orders'],
            ['#^medicine/checkout$#', 'orders', 'New Medicine Order', '/admin/medicine-orders', 'View Medicine Orders'],
            ['#^food/orders/\d+/payment-proof$#', 'orders', 'Food Payment Proof Updated', '/admin/food-orders', 'Review Payment'],
            ['#^medicine/orders/\d+/payment-proof$#', 'orders', 'Medicine Payment Proof Updated', '/admin/medicine-orders', 'Review Payment'],
            ['#^food/orders/\d+/cancel$#', 'orders', 'Food Order Cancelled', '/admin/food-orders', 'View Order'],
            ['#^food/orders/\d+/support-tickets$#', 'support', 'New Food Support Ticket', '/admin/support-tickets', 'Open Support'],
            ['#^riders/support-tickets$#', 'support', 'New Rider Support Ticket', '/admin/support-tickets', 'Open Support'],
            ['#^support(?:/|$)#', 'support', 'New Support Request', '/admin/support-tickets', 'Open Support'],
            ['#^riders/register$#', 'registrations', 'New Rider Registration', '/admin/riders', 'Review Rider'],
            ['#^(restaurants|hospitals|hotels|education|couriers)/register$#', 'registrations', 'New Service Registration', '/admin/restaurants', 'Review Registration'],
            ['#^worker/apply$#', 'registrations', 'New Worker Application', '/admin/workers', 'Review Worker'],
            ['#^(register|register-otp)$#', 'registrations', 'New User Registration', '/admin/users', 'View Users'],
            ['#^food/owner/restaurants$#', 'registrations', 'Restaurant Owner Submission', '/admin/restaurants', 'Review Restaurant'],
            ['#^food/owner/items$#', 'submissions', 'Restaurant Item Submission', '/admin/food-items', 'Review Food Items'],
            ['#^book-service$#', 'submissions', 'New Service Booking', '/admin/workers', 'View Booking'],
            ['#^doctor-appointments$#', 'submissions', 'New Doctor Appointment', '/admin/doctors', 'View Appointment'],
            ['#^business/add$#', 'submissions', 'New Business Submission', '/admin/businesses', 'Review Business'],
            ['#^items/add$#', 'submissions', 'New Marketplace Item', '/admin/marketplace', 'Review Item'],
            ['#^items/report$#', 'submissions', 'Marketplace Report', '/admin/reports', 'Review Report'],
            ['#^jobs/post$#', 'submissions', 'New Job Post', '/admin/jobs', 'Review Job'],
            ['#^jobs/apply$#', 'submissions', 'New Job Application', '/admin/jobs', 'View Application'],
            ['#^(teacher|student)-requests$#', 'submissions', 'New Education Request', '/admin/education?tab=requests', 'Review Request'],
            ['#^blood-requests$#', 'submissions', 'New Blood Request', '/admin/blood', 'Review Request'],
            ['#^blood-donor/register$#', 'registrations', 'New Blood Donor', '/admin/blood', 'View Donor'],
            ['#^car-rentals$#', 'submissions', 'New Car Rental Submission', '/admin/car-rental', 'Review Submission'],
            ['#^send-message$#', 'messages_reviews', 'New Customer Message', '/admin/messages', 'Open Messages'],
            ['#^(food/)?reviews$#', 'messages_reviews', 'New Review', '/admin/food-reviews', 'Review Feedback'],
            ['#^reviews/#', 'messages_reviews', 'New Review', '/admin/reviews', 'Review Feedback'],
        ];

        foreach ($rules as [$pattern, $type, $title, $route, $button]) {
            if (preg_match($pattern, $path)) {
                return compact('type', 'title', 'route', 'button') + ['path' => $path];
            }
        }

        return null;
    }

    private function formatMessage(Request $request, array $responseData, array $event, bool $includeDetails): string
    {
        $lines = [
            '<b>BHOLAVASHI ADMIN ALERT</b>',
            '',
            '<b>'.$this->escape($event['title']).'</b>',
            '<b>Time:</b> '.$this->escape(now()->timezone('Asia/Dhaka')->format('d M Y, h:i:s A')),
        ];

        $user = $request->user();
        if ($user) {
            $actor = trim((string) ($user->name ?? ''));
            $phone = trim((string) ($user->phone ?? ''));
            if ($actor !== '') {
                $lines[] = '<b>Account:</b> '.$this->escape($actor);
            }
            if ($phone !== '') {
                $lines[] = '<b>Account Phone:</b> '.$this->escape($phone);
            }
        }

        if ($includeDetails) {
            $details = $this->safeDetails($request, $responseData);
            foreach ($details as $label => $value) {
                $lines[] = '<b>'.$this->escape($label).':</b> '.$this->escape($value);
            }

            $items = $this->extractItems($responseData);
            if ($items) {
                $lines[] = '';
                $lines[] = '<b>Items</b>';
                foreach ($items as $item) {
                    $lines[] = '• '.$this->escape($item);
                }
            }
        }

        $lines[] = '';
        $lines[] = '<i>Source: Bholavashi API · '.$this->escape($event['path']).'</i>';

        return implode("\n", $lines);
    }

    private function safeDetails(Request $request, array $responseData): array
    {
        $candidates = array_merge(
            $this->flattenPreferred($this->primaryPayload($responseData)),
            $this->flattenPreferred($request->only(array_keys(self::FIELD_LABELS))),
        );

        $details = [];
        foreach (self::FIELD_LABELS as $field => $label) {
            if (! array_key_exists($field, $candidates) || blank($candidates[$field])) {
                continue;
            }
            if (array_key_exists($label, $details)) {
                continue;
            }

            $value = $candidates[$field];
            if (is_numeric($value) && in_array($field, ['grand_total', 'total', 'delivery_fee', 'discount'], true)) {
                $value = 'BDT '.number_format((float) $value, 2);
            }
            $details[$label] = Str::limit((string) $value, 500);
        }

        return array_slice($details, 0, 14, true);
    }

    private function primaryPayload(array $data): array
    {
        foreach (['order', 'ticket', 'booking', 'appointment', 'application', 'business', 'restaurant', 'item', 'review', 'data'] as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                return $data[$key];
            }
        }

        return $data;
    }

    private function flattenPreferred(array $data): array
    {
        $flat = [];
        foreach (Arr::dot($data) as $key => $value) {
            if (is_array($value) || is_object($value)) {
                continue;
            }
            $field = Str::afterLast((string) $key, '.');
            if (array_key_exists($field, self::FIELD_LABELS) && ! array_key_exists($field, $flat)) {
                $flat[$field] = $value;
            }
        }

        return $flat;
    }

    private function extractItems(array $responseData): array
    {
        $payload = $this->primaryPayload($responseData);
        $items = $payload['items'] ?? [];
        if (! is_array($items)) {
            return [];
        }

        return collect($items)->take(10)->map(function ($item): ?string {
            if (! is_array($item)) {
                return null;
            }
            $name = data_get($item, 'food_item.name') ?: data_get($item, 'medicine_item.name') ?: ($item['name'] ?? $item['title'] ?? null);
            if (! $name) {
                return null;
            }
            $quantity = $item['quantity'] ?? $item['qty'] ?? 1;
            $price = $item['line_total'] ?? $item['total'] ?? $item['price'] ?? null;

            return trim($name.' × '.$quantity.($price !== null ? ' — BDT '.number_format((float) $price, 2) : ''));
        })->filter()->values()->all();
    }

    private function responseData(Response $response): array
    {
        $content = method_exists($response, 'getContent') ? $response->getContent() : null;
        if (! is_string($content) || $content === '') {
            return [];
        }

        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function escape(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
