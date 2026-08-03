<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AiController;
use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AutomationController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\CampaignController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\FollowUpController;
use App\Http\Controllers\Api\V1\IntegrationController;
use App\Http\Controllers\Api\V1\LeadController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Controllers\Api\V1\UnitController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\VisitController;
use App\Http\Controllers\Api\V1\Webhooks\FacebookWebhookController;
use App\Http\Controllers\Api\V1\Webhooks\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::middleware('throttle:api')->group(function (): void {
        Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:login');

        // Meta webhooks (no auth — verified via hub.verify_token / app secret)
        Route::get('webhooks/facebook', [FacebookWebhookController::class, 'verify']);
        Route::post('webhooks/facebook', [FacebookWebhookController::class, 'handle'])->middleware('throttle:300,1');
        Route::get('webhooks/whatsapp', [WhatsAppWebhookController::class, 'verify']);
        Route::post('webhooks/whatsapp', [WhatsAppWebhookController::class, 'handle'])->middleware('throttle:300,1');

        Route::middleware(['auth:sanctum', 'tenant'])->group(function (): void {
            Route::post('auth/logout', [AuthController::class, 'logout']);
            Route::get('auth/me', [AuthController::class, 'me']);

            Route::get('dashboard', [DashboardController::class, 'index']);
            Route::get('search', [SearchController::class, 'index']);

            Route::get('leads/board', [LeadController::class, 'board']);
            Route::post('leads/import', [LeadController::class, 'import']);
            Route::post('leads/{lead}/status', [LeadController::class, 'updateStatus']);
            Route::post('leads/{lead}/assign', [LeadController::class, 'assign']);
            Route::post('leads/{lead}/convert', [LeadController::class, 'convert']);
            Route::apiResource('leads', LeadController::class);

            Route::post('customers/{customer}/family', [CustomerController::class, 'addFamilyMember']);
            Route::put('customers/{customer}/family/{familyMember}', [CustomerController::class, 'updateFamilyMember']);
            Route::delete('customers/{customer}/family/{familyMember}', [CustomerController::class, 'removeFamilyMember']);
            Route::apiResource('customers', CustomerController::class);

            Route::apiResource('projects', ProjectController::class);
            Route::apiResource('units', UnitController::class)->except(['destroy']);

            Route::post('visits/{visit}/check-in', [VisitController::class, 'checkIn']);
            Route::post('visits/{visit}/complete', [VisitController::class, 'complete']);
            Route::apiResource('visits', VisitController::class)->except(['update', 'destroy']);

            Route::post('follow-ups/{follow_up}/complete', [FollowUpController::class, 'complete']);
            Route::apiResource('follow-ups', FollowUpController::class)->except(['update', 'destroy']);

            Route::post('tasks/{task}/complete', [TaskController::class, 'complete']);
            Route::post('tasks/{task}/comments', [TaskController::class, 'addComment']);
            Route::apiResource('tasks', TaskController::class)->except(['update', 'destroy']);

            Route::post('bookings/{booking}/reserve', [BookingController::class, 'reserve']);
            Route::post('bookings/{booking}/confirm', [BookingController::class, 'confirm']);
            Route::post('bookings/{booking}/cancel', [BookingController::class, 'cancel']);
            Route::apiResource('bookings', BookingController::class)->except(['update', 'destroy']);

            Route::get('payments/outstanding', [PaymentController::class, 'outstanding']);
            Route::post('payments/{payment}/refund', [PaymentController::class, 'refund']);
            Route::apiResource('payments', PaymentController::class)->except(['update', 'destroy']);

            Route::apiResource('documents', DocumentController::class)->except(['update']);
            Route::get('campaigns/{campaign}/roi', [CampaignController::class, 'roi']);
            Route::apiResource('campaigns', CampaignController::class)->except(['destroy']);

            Route::apiResource('users', UserController::class)->except(['destroy']);
            Route::apiResource('organizations', OrganizationController::class)->except(['destroy']);

            Route::post('ai/leads/{lead}/score', [AiController::class, 'scoreLead']);

            Route::post('automations/{automation_rule}/run/{lead}', [AutomationController::class, 'run']);
            Route::apiResource('automations', AutomationController::class)->only(['index', 'store']);

            Route::get('reports/sales', [ReportController::class, 'sales']);
            Route::get('reports/leads', [ReportController::class, 'leads']);
            Route::get('reports/overview', [ReportController::class, 'overview']);

            Route::get('audit-logs', [AuditLogController::class, 'index']);
            Route::get('audit-logs/{audit_log}', [AuditLogController::class, 'show']);

            Route::get('notifications', [NotificationController::class, 'index']);
            Route::post('notifications/{id}/read', [NotificationController::class, 'markAsRead']);
            Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead']);

            Route::get('integrations', [IntegrationController::class, 'index']);
            Route::put('integrations/{provider}', [IntegrationController::class, 'upsert']);
            Route::post('integrations/facebook/sync-form', [IntegrationController::class, 'syncFacebookForm']);
            Route::get('leads/{lead}/messages', [IntegrationController::class, 'leadMessages']);
            Route::post('leads/{lead}/whatsapp', [IntegrationController::class, 'sendWhatsApp']);
        });
    });
});
