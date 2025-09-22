<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReportPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any reports.
     * Only admins and superadmins can access the reporting system
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Determine whether the user can view reports.
     * Alias for viewAny for consistency with Gate calls
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewReports(User $user)
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can view sales and collections reports.
     * Superadmin: Full access to all sales data
     * Admin: Access to sales data (may be filtered by office in future)
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewSalesReports(User $user)
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Determine whether the user can view manifest performance reports.
     * Superadmin: Full access to all manifest data
     * Admin: Access to manifest data (may be filtered by office in future)
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewManifestReports(User $user)
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Determine whether the user can view customer-specific reports.
     * Superadmin: Full access to all customer data
     * Admin: Access to customer data (may be filtered by office in future)
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewCustomerReports(User $user)
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Determine whether the user can view financial analytics reports.
     * Superadmin: Full access to all financial data
     * Admin: Limited access to financial data (no sensitive financial details)
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewFinancialReports(User $user)
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Determine whether the user can export reports.
     * Superadmin: Can export all report types
     * Admin: Can export reports with potential restrictions
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function exportReports(User $user)
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Determine whether the user can export reports with sensitive data.
     * Only superadmins can export reports containing sensitive financial or personal data
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function exportSensitiveData(User $user)
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can view all customer data across offices.
     * Superadmin: Can view data from all offices
     * Admin: May be restricted to their office (future implementation)
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAllCustomerData(User $user)
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can manage report templates.
     * Superadmin: Can create, edit, and delete report templates
     * Admin: Can use existing templates but not modify them
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function manageReportTemplates(User $user)
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can view report templates.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewReportTemplates(User $user)
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Determine whether the user can create report templates.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function createReportTemplates(User $user)
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can update report templates.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function updateReportTemplates(User $user)
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can delete report templates.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function deleteReportTemplates(User $user)
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can create and manage saved filters.
     * Both admins and superadmins can create and manage their own saved filters
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function manageSavedFilters(User $user)
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Determine whether the user can share saved filters with other users.
     * Only superadmins can share filters with other users
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function shareSavedFilters(User $user)
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can access report administration features.
     * Only superadmins can access administrative features like cache management
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function administerReports(User $user)
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can view specific customer's data.
     * Used for customer-specific report access control
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $customer
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewCustomerData(User $user, User $customer)
    {
        // Superadmin can view any customer's data
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Admin can view customer data (future: may be restricted by office)
        if ($user->isAdmin()) {
            return $customer->isCustomer();
        }

        return false;
    }

    /**
     * Determine whether the user can access real-time report data.
     * Controls access to live data vs cached data
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function accessRealTimeData(User $user)
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Determine whether the user can view detailed financial breakdowns.
     * Controls access to detailed financial information
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewDetailedFinancials(User $user)
    {
        return $user->isSuperAdmin();
    }
}