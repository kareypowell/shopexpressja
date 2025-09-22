<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReportDataFilterService
{
    /**
     * Apply role-based data filtering to query builders
     *
     * @param Builder $query
     * @param User $user
     * @param string $dataType
     * @return Builder
     */
    public function applyRoleBasedFiltering(Builder $query, User $user, string $dataType): Builder
    {
        // Superadmins have access to all data
        if ($user->isSuperAdmin()) {
            return $query;
        }

        // Apply filtering based on data type and user role
        switch ($dataType) {
            case 'customer_data':
                return $this->filterCustomerData($query, $user);
            case 'financial_data':
                return $this->filterFinancialData($query, $user);
            case 'manifest_data':
                return $this->filterManifestData($query, $user);
            case 'package_data':
                return $this->filterPackageData($query, $user);
            default:
                return $query;
        }
    }

    /**
     * Filter customer data based on user permissions
     *
     * @param Builder $query
     * @param User $user
     * @return Builder
     */
    protected function filterCustomerData(Builder $query, User $user): Builder
    {
        // Admins can see customer data but may be restricted by office in future
        if ($user->isAdmin()) {
            // Future: Add office-based filtering
            // $query->whereHas('profile', function($q) use ($user) {
            //     $q->where('office_id', $user->office_id);
            // });
            return $query;
        }

        // Customers can only see their own data
        if ($user->isCustomer()) {
            return $query->where('id', $user->id);
        }

        // Default: no access
        return $query->whereRaw('1 = 0');
    }

    /**
     * Filter financial data based on user permissions
     *
     * @param Builder $query
     * @param User $user
     * @return Builder
     */
    protected function filterFinancialData(Builder $query, User $user): Builder
    {
        // Admins have limited financial access
        if ($user->isAdmin()) {
            // Future: May restrict sensitive financial details
            return $query;
        }

        // Customers can only see their own financial data
        if ($user->isCustomer()) {
            return $query->where('user_id', $user->id);
        }

        // Default: no access
        return $query->whereRaw('1 = 0');
    }

    /**
     * Filter manifest data based on user permissions
     *
     * @param Builder $query
     * @param User $user
     * @return Builder
     */
    protected function filterManifestData(Builder $query, User $user): Builder
    {
        // Admins can see manifest data
        if ($user->isAdmin()) {
            // Future: Add office-based filtering
            return $query;
        }

        // Customers can only see manifests containing their packages
        if ($user->isCustomer()) {
            return $query->whereHas('packages', function($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        // Default: no access
        return $query->whereRaw('1 = 0');
    }

    /**
     * Filter package data based on user permissions
     *
     * @param Builder $query
     * @param User $user
     * @return Builder
     */
    protected function filterPackageData(Builder $query, User $user): Builder
    {
        // Admins can see all package data
        if ($user->isAdmin()) {
            return $query;
        }

        // Customers can only see their own packages
        if ($user->isCustomer()) {
            return $query->where('user_id', $user->id);
        }

        // Default: no access
        return $query->whereRaw('1 = 0');
    }

    /**
     * Apply office-based filtering (future implementation)
     *
     * @param Builder $query
     * @param User $user
     * @param string $officeField
     * @return Builder
     */
    public function applyOfficeFiltering(Builder $query, User $user, string $officeField = 'office_id'): Builder
    {
        // Future implementation: Filter by user's office
        // if ($user->office_id && !$user->isSuperAdmin()) {
        //     return $query->where($officeField, $user->office_id);
        // }

        return $query;
    }

    /**
     * Mask sensitive data in collections
     *
     * @param Collection $data
     * @param User $user
     * @param array $sensitiveFields
     * @return Collection
     */
    public function maskSensitiveData(Collection $data, User $user, array $sensitiveFields = []): Collection
    {
        // Superadmins see all data unmasked
        if ($user->isSuperAdmin()) {
            return $data;
        }

        $defaultSensitiveFields = [
            'email',
            'phone',
            'address',
            'account_balance',
            'credit_balance',
        ];

        $fieldsToMask = array_merge($defaultSensitiveFields, $sensitiveFields);

        return $data->map(function ($item) use ($fieldsToMask, $user) {
            if (is_array($item)) {
                return $this->maskArrayData($item, $fieldsToMask, $user);
            } elseif (is_object($item)) {
                return $this->maskObjectData($item, $fieldsToMask, $user);
            }
            return $item;
        });
    }

    /**
     * Mask sensitive data in array format
     *
     * @param array $data
     * @param array $fieldsToMask
     * @param User $user
     * @return array
     */
    protected function maskArrayData(array $data, array $fieldsToMask, User $user): array
    {
        foreach ($fieldsToMask as $field) {
            if (isset($data[$field]) && $this->shouldMaskField($field, $user)) {
                $data[$field] = $this->getMaskedValue($field, $data[$field]);
            }
        }

        return $data;
    }

    /**
     * Mask sensitive data in object format
     *
     * @param object $data
     * @param array $fieldsToMask
     * @param User $user
     * @return object
     */
    protected function maskObjectData(object $data, array $fieldsToMask, User $user): object
    {
        foreach ($fieldsToMask as $field) {
            if (isset($data->$field) && $this->shouldMaskField($field, $user)) {
                $data->$field = $this->getMaskedValue($field, $data->$field);
            }
        }

        return $data;
    }

    /**
     * Determine if a field should be masked for the user
     *
     * @param string $field
     * @param User $user
     * @return bool
     */
    protected function shouldMaskField(string $field, User $user): bool
    {
        // Admins have limited access to sensitive financial data
        if ($user->isAdmin()) {
            $restrictedFields = ['account_balance', 'credit_balance'];
            return in_array($field, $restrictedFields);
        }

        // Customers should not see other customers' sensitive data
        if ($user->isCustomer()) {
            return true;
        }

        return false;
    }

    /**
     * Get masked value for a field
     *
     * @param string $field
     * @param mixed $value
     * @return string
     */
    protected function getMaskedValue(string $field, $value): string
    {
        switch ($field) {
            case 'email':
                return $this->maskEmail($value);
            case 'phone':
                return $this->maskPhone($value);
            case 'address':
                return '[REDACTED]';
            case 'account_balance':
            case 'credit_balance':
                return '[RESTRICTED]';
            default:
                return '[MASKED]';
        }
    }

    /**
     * Mask email address
     *
     * @param string $email
     * @return string
     */
    protected function maskEmail(string $email): string
    {
        if (empty($email) || !str_contains($email, '@')) {
            return '[MASKED]';
        }

        [$username, $domain] = explode('@', $email, 2);
        
        if (strlen($username) <= 2) {
            $maskedUsername = str_repeat('*', strlen($username));
        } else {
            $maskedUsername = substr($username, 0, 1) . str_repeat('*', strlen($username) - 2) . substr($username, -1);
        }

        return $maskedUsername . '@' . $domain;
    }

    /**
     * Mask phone number
     *
     * @param string $phone
     * @return string
     */
    protected function maskPhone(string $phone): string
    {
        if (empty($phone)) {
            return '[MASKED]';
        }

        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        
        if (strlen($cleaned) < 4) {
            return str_repeat('*', strlen($cleaned));
        }

        return str_repeat('*', strlen($cleaned) - 4) . substr($cleaned, -4);
    }

    /**
     * Check if user can access specific customer's data
     *
     * @param User $user
     * @param User $customer
     * @return bool
     */
    public function canAccessCustomerData(User $user, User $customer): bool
    {
        // Superadmins can access any customer data
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Admins can access customer data (future: may be restricted by office)
        if ($user->isAdmin() && $customer->isCustomer()) {
            return true;
        }

        // Users can access their own data
        if ($user->id === $customer->id) {
            return true;
        }

        return false;
    }

    /**
     * Get allowed export fields based on user role
     *
     * @param User $user
     * @param string $reportType
     * @return array
     */
    public function getAllowedExportFields(User $user, string $reportType): array
    {
        $baseFields = $this->getBaseExportFields($reportType);

        // Superadmins get all fields
        if ($user->isSuperAdmin()) {
            return $baseFields;
        }

        // Remove sensitive fields for non-superadmins
        $restrictedFields = [
            'account_balance',
            'credit_balance',
            'email',
            'phone',
            'address',
        ];

        return array_diff($baseFields, $restrictedFields);
    }

    /**
     * Get base export fields for report type
     *
     * @param string $reportType
     * @return array
     */
    protected function getBaseExportFields(string $reportType): array
    {
        switch ($reportType) {
            case 'sales':
                return [
                    'manifest_id', 'manifest_type', 'total_packages', 'total_weight',
                    'total_volume', 'total_charges', 'total_collected', 'outstanding_balance',
                    'collection_rate', 'created_at'
                ];
            case 'customer':
                return [
                    'customer_id', 'first_name', 'last_name', 'email', 'phone',
                    'total_packages', 'total_spent', 'account_balance', 'credit_balance',
                    'last_package_date', 'created_at'
                ];
            case 'manifest':
                return [
                    'manifest_id', 'type', 'status', 'package_count', 'total_weight',
                    'total_volume', 'processing_time', 'created_at', 'completed_at'
                ];
            case 'financial':
                return [
                    'period', 'total_revenue', 'total_collected', 'outstanding_amount',
                    'collection_rate', 'average_package_value', 'total_packages'
                ];
            default:
                return [];
        }
    }
}