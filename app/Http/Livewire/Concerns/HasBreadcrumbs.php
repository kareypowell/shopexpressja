<?php

namespace App\Http\Livewire\Concerns;

trait HasBreadcrumbs
{
    public $breadcrumbs = [];

    /**
     * Set the breadcrumbs for the current page
     *
     * @param array $breadcrumbs
     * @return void
     */
    protected function setBreadcrumbs(array $breadcrumbs)
    {
        $this->breadcrumbs = $breadcrumbs;
    }

    /**
     * Get the home breadcrumb item
     *
     * @return array
     */
    protected function getHomeBreadcrumb(): array
    {
        return [
            'title' => 'Dashboard',
            'url' => route('home'),
            'icon' => '<path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>'
        ];
    }

    /**
     * Get the customers index breadcrumb item
     *
     * @return array
     */
    protected function getCustomersIndexBreadcrumb(): array
    {
        return [
            'title' => 'Customers',
            'url' => route('admin.customers.index')
        ];
    }

    /**
     * Get a customer-specific breadcrumb item
     *
     * @param \App\Models\User $customer
     * @param string|null $url
     * @return array
     */
    protected function getCustomerBreadcrumb(\App\Models\User $customer, ?string $url = null): array
    {
        $name = 'Customer';
        if ($customer->profile) {
            $name = $customer->profile->first_name . ' ' . $customer->profile->last_name;
        } elseif ($customer->first_name && $customer->last_name) {
            $name = $customer->first_name . ' ' . $customer->last_name;
        } elseif ($customer->email) {
            $name = $customer->email;
        }
        
        return [
            'title' => $name,
            'url' => $url
        ];
    }

    /**
     * Generate breadcrumbs for customer index page
     *
     * @return void
     */
    protected function setCustomerIndexBreadcrumbs()
    {
        $this->setBreadcrumbs([
            $this->getHomeBreadcrumb(),
            [
                'title' => 'Customers',
                'url' => null
            ]
        ]);
    }

    /**
     * Generate breadcrumbs for customer create page
     *
     * @return void
     */
    protected function setCustomerCreateBreadcrumbs()
    {
        $this->setBreadcrumbs([
            $this->getHomeBreadcrumb(),
            $this->getCustomersIndexBreadcrumb(),
            [
                'title' => 'Create Customer',
                'url' => null
            ]
        ]);
    }

    /**
     * Generate breadcrumbs for customer profile page
     *
     * @param \App\Models\User $customer
     * @return void
     */
    protected function setCustomerProfileBreadcrumbs(\App\Models\User $customer)
    {
        $name = 'Customer';
        if ($customer->profile) {
            $name = $customer->profile->first_name . ' ' . $customer->profile->last_name;
        } elseif ($customer->first_name && $customer->last_name) {
            $name = $customer->first_name . ' ' . $customer->last_name;
        } elseif ($customer->email) {
            $name = $customer->email;
        }
        
        $this->setBreadcrumbs([
            $this->getHomeBreadcrumb(),
            $this->getCustomersIndexBreadcrumb(),
            [
                'title' => $name,
                'url' => null
            ]
        ]);
    }

    /**
     * Generate breadcrumbs for customer edit page
     *
     * @param \App\Models\User $customer
     * @return void
     */
    protected function setCustomerEditBreadcrumbs(\App\Models\User $customer)
    {
        $this->setBreadcrumbs([
            $this->getHomeBreadcrumb(),
            $this->getCustomersIndexBreadcrumb(),
            $this->getCustomerBreadcrumb($customer, route('admin.customers.show', $customer)),
            [
                'title' => 'Edit',
                'url' => null
            ]
        ]);
    }

    /**
     * Get the users index breadcrumb item
     *
     * @return array
     */
    protected function getUsersIndexBreadcrumb(): array
    {
        return [
            'title' => 'Users',
            'url' => \Route::has('admin.users.index') ? route('admin.users.index') : null
        ];
    }

    /**
     * Generate breadcrumbs for user index page
     *
     * @return void
     */
    protected function setUserIndexBreadcrumbs()
    {
        $this->setBreadcrumbs([
            $this->getHomeBreadcrumb(),
            [
                'title' => 'Users',
                'url' => null
            ]
        ]);
    }

    /**
     * Generate breadcrumbs for user create page
     *
     * @return void
     */
    protected function setUserCreateBreadcrumbs()
    {
        $this->setBreadcrumbs([
            $this->getHomeBreadcrumb(),
            $this->getUsersIndexBreadcrumb(),
            [
                'title' => 'Create User',
                'url' => null
            ]
        ]);
    }

    /**
     * Get a user-specific breadcrumb item
     *
     * @param \App\Models\User $user
     * @param string|null $url
     * @return array
     */
    protected function getUserBreadcrumb(\App\Models\User $user, ?string $url = null): array
    {
        $name = 'User';
        if ($user->first_name && $user->last_name) {
            $name = $user->first_name . ' ' . $user->last_name;
        } elseif ($user->email) {
            $name = $user->email;
        }
        
        return [
            'title' => $name,
            'url' => $url
        ];
    }

    /**
     * Generate breadcrumbs for user profile page
     *
     * @param \App\Models\User $user
     * @return void
     */
    protected function setUserProfileBreadcrumbs(\App\Models\User $user)
    {
        $this->setBreadcrumbs([
            $this->getHomeBreadcrumb(),
            $this->getUsersIndexBreadcrumb(),
            $this->getUserBreadcrumb($user, null)
        ]);
    }

    /**
     * Generate breadcrumbs for user edit page
     *
     * @param \App\Models\User $user
     * @return void
     */
    protected function setUserEditBreadcrumbs(\App\Models\User $user)
    {
        $this->setBreadcrumbs([
            $this->getHomeBreadcrumb(),
            $this->getUsersIndexBreadcrumb(),
            $this->getUserBreadcrumb($user, route('admin.users.show', $user)),
            [
                'title' => 'Edit',
                'url' => null
            ]
        ]);
    }
}