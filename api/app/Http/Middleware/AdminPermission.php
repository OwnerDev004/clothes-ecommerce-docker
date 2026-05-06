<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AdminPermission
{
    private const VIEW_METHODS = ['index', 'show', 'filters', 'detailSections', 'view'];
    private const ACTION_MAP = [
        'store' => 'create',
        'create' => 'create',
        'update' => 'edit',
        'modify' => 'edit',
        'updateorder' => 'edit',
        'updatestatus' => 'edit',
        'sendresetlink' => 'edit',
        'storereview' => 'create',
        'destroy' => 'delete',
        'delete' => 'delete',
        'remove' => 'delete',
        'cancel' => 'delete',
    ];

    private const MODULE_MAP = [
        'DashboardController' => 'dashboard',
        'AnalyticsController' => 'analytics',
        'ProductController' => 'products',
        'ProductVariantController' => 'product_variants',
        'OrderController' => 'orders',
        'StockPurchaseController' => 'purchases',
        'VoucherController' => 'promotions',
        'CategoryController' => 'categories',
        'CustomerController' => 'customers',
        'BrandController' => 'brands',
        'CollectionController' => 'collections',
        'RoleController' => 'roles',
        'ModuleController' => 'roles',
        'AdminController' => 'admins',
        'SettingController' => 'setting',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $admin = auth('admin')->user();

        if (!$admin) {
            abort(401, 'Unauthenticated.');
        }

        if ($admin->role === 'super_admin') {
            return $next($request);
        }

        $routeAction = (string) ($request->route()?->getActionName() ?? '');
        if ($routeAction === '' || !str_contains($routeAction, '@')) {
            return $next($request);
        }

        [$controllerClass, $method] = explode('@', $routeAction, 2);
        $controllerBase = class_basename($controllerClass);

        if ($controllerBase === 'AdminAuthController') {
            return $next($request);
        }

        $module = $this->resolveModuleSlug($controllerBase, $request);
        $action = $this->resolveAction($method);

        if ($module === null || $action === null) {
            return $next($request);
        }

        if ($this->can($admin->role, $module, $action)) {
            return $next($request);
        }

        abort(403, 'Forbidden.');
    }

    private function resolveModuleSlug(string $controllerBase, Request $request): ?string
    {
        if (isset(self::MODULE_MAP[$controllerBase])) {
            return self::MODULE_MAP[$controllerBase];
        }

        $segments = $request->segments();
        if (count($segments) < 2 || $segments[0] !== 'api' || $segments[1] !== 'v1') {
            return null;
        }

        $moduleSegment = $segments[3] ?? null;
        return is_string($moduleSegment) && $moduleSegment !== '' ? str_replace('-', '_', $moduleSegment) : null;
    }

    private function resolveAction(string $method): ?string
    {
        $normalized = Str::lower($method);

        if (in_array($normalized, self::VIEW_METHODS, true)) {
            return 'view';
        }

        foreach (self::ACTION_MAP as $needle => $action) {
            if (str_contains($normalized, $needle)) {
                return $action;
            }
        }

        return null;
    }

    private function can(string $roleSlug, string $moduleSlug, string $action): bool
    {
        $matrix = app(\App\Repositories\Admin\RoleRepository::class)->permissionMatrixForSlug($roleSlug);

        if (!is_array($matrix['permission_map'] ?? null)) {
            return false;
        }

        $actions = $matrix['permission_map'][$moduleSlug] ?? [];

        return in_array($action, $actions, true);
    }
}
