<?php

namespace App\Http\Livewire\Users;

use App\Http\Livewire\Concerns\HasBreadcrumbs;
use App\Models\User;
use App\Models\Role;
use App\Models\Profile;
use App\Models\Office;
use App\Services\AccountNumberService;
use App\Services\CustomerEmailService;
use App\Mail\WelcomeUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Events\Registered;
use Livewire\Component;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class UserCreate extends Component
{
    use AuthorizesRequests, HasBreadcrumbs;
    
    // User basic information
    public $firstName = '';
    public $lastName = '';
    public $email = '';
    public $password = '';
    public $passwordConfirmation = '';
    public $selectedRole = 'customer';
    
    // Profile information (for customers)
    public $telephoneNumber = '';
    public $taxNumber = '';
    public $streetAddress = '';
    public $cityTown = '';
    public $parish = '';
    public $country = 'Jamaica';
    public $pickupLocation = '';
    
    // Options
    public $sendWelcomeEmail = true;
    public $generatePassword = true;
    public $queueEmail = true;
    
    // UI state
    public $isCreating = false;
    public $emailStatus = null;
    public $emailMessage = null;
    public $emailDeliveryId = null;
    public $emailRetryCount = 0;
    public $showEmailDetails = false;
    public $showCustomerFields = true;

    protected function rules()
    {
        $rules = [
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required_if:generatePassword,false|min:8|same:passwordConfirmation',
            'selectedRole' => 'required|exists:roles,name',
        ];

        // Add customer-specific validation rules if customer role is selected
        if ($this->selectedRole === 'customer') {
            $rules = array_merge($rules, [
                'telephoneNumber' => 'required|string|max:20',
                'taxNumber' => 'nullable|string|max:20',
                'streetAddress' => 'required|string|max:500',
                'cityTown' => 'required|string|max:100',
                'parish' => 'required|string|max:50',
                'country' => 'required|string|max:50',
                'pickupLocation' => 'required|integer|exists:offices,id',
            ]);
        }

        return $rules;
    }

    protected $validationAttributes = [
        'firstName' => 'first name',
        'lastName' => 'last name',
        'telephoneNumber' => 'telephone number',
        'taxNumber' => 'tax number',
        'streetAddress' => 'street address',
        'cityTown' => 'city/town',
        'pickupLocation' => 'pickup location',
        'passwordConfirmation' => 'password confirmation',
        'selectedRole' => 'role',
    ];

    public function mount()
    {
        // Check if user can create users
        $this->authorize('user.create');
        
        $this->setUserCreateBreadcrumbs();
        
        // Generate a random password by default
        if ($this->generatePassword) {
            $this->password = $this->generateRandomPassword();
            $this->passwordConfirmation = $this->password;
        }
        
        // Set initial field visibility
        $this->updateFieldVisibility();
    }

    public function updatedGeneratePassword()
    {
        if ($this->generatePassword) {
            $this->password = $this->generateRandomPassword();
            $this->passwordConfirmation = $this->password;
        } else {
            $this->password = '';
            $this->passwordConfirmation = '';
        }
    }

    public function updatedSelectedRole()
    {
        $this->updateFieldVisibility();
        $this->resetValidation();
    }

    private function updateFieldVisibility()
    {
        $this->showCustomerFields = $this->selectedRole === 'customer';
        
        // Clear customer fields if not customer role
        if (!$this->showCustomerFields) {
            $this->telephoneNumber = '';
            $this->taxNumber = '';
            $this->streetAddress = '';
            $this->cityTown = '';
            $this->parish = '';
            $this->pickupLocation = '';
        }
    }

    public function create()
    {
        // Re-check authorization before creating
        $this->authorize('user.create');
        
        $this->isCreating = true;

        try {
            // Validate the form data
            $this->validate();

            DB::beginTransaction();

            // Get selected role
            $role = Role::where('name', $this->selectedRole)->first();
            if (!$role) {
                throw new \Exception("Role '{$this->selectedRole}' not found in the system.");
            }

            // Create the user
            $user = User::create([
                'first_name' => $this->firstName,
                'last_name' => $this->lastName,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'role_id' => $role->id,
            ]);

            // Create profile for customers
            if ($this->selectedRole === 'customer') {
                // Generate account number
                $accountNumberService = new AccountNumberService();
                $accountNumber = $accountNumberService->generate();

                // Create the profile
                $user->profile()->create([
                    'account_number' => $accountNumber,
                    'tax_number' => $this->taxNumber ?: '',
                    'telephone_number' => $this->telephoneNumber,
                    'street_address' => $this->streetAddress,
                    'city_town' => $this->cityTown,
                    'parish' => $this->parish,
                    'country' => $this->country,
                    'pickup_location' => $this->pickupLocation,
                ]);
            }

            // Send welcome email if requested
            if ($this->sendWelcomeEmail) {
                $emailResult = $this->sendWelcomeEmailToUser($user);
                $this->emailStatus = $emailResult['status'];
                $this->emailMessage = $emailResult['message'];
            }

            // Fire registered event
            event(new Registered($user));

            DB::commit();

            // Show success message
            $roleDisplayName = ucfirst($this->selectedRole);
            if ($this->selectedRole === 'customer' && isset($accountNumber)) {
                session()->flash('success', "{$roleDisplayName} {$user->full_name} has been created successfully with account number {$accountNumber}.");
            } else {
                session()->flash('success', "{$roleDisplayName} {$user->full_name} has been created successfully.");
            }

            // Redirect based on role
            if ($this->selectedRole === 'customer') {
                return redirect()->route('admin.customers.show', $user);
            } else {
                // Fallback to customers index if users index route doesn't exist
                if (\Route::has('admin.users.index')) {
                    return redirect()->route('admin.users.index');
                } else {
                    return redirect()->route('admin.customers.index');
                }
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            $this->isCreating = false;
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->isCreating = false;
            session()->flash('error', 'Failed to create user: ' . $e->getMessage());
        }
    }

    public function cancel()
    {
        // Fallback to customers index if users index route doesn't exist
        if (\Route::has('admin.users.index')) {
            return redirect()->route('admin.users.index');
        } else {
            return redirect()->route('admin.customers.index');
        }
    }

    /**
     * Retry sending welcome email for a user.
     *
     * @param int $userId
     * @return void
     */
    public function retryWelcomeEmail($userId)
    {
        try {
            $user = User::findOrFail($userId);
            $emailService = app(CustomerEmailService::class);
            
            // Check if we've exceeded maximum retry attempts
            if ($this->emailRetryCount >= 3) {
                session()->flash('error', 'Maximum retry attempts exceeded. Please contact system administrator.');
                return;
            }
            
            // Increment retry count
            $this->emailRetryCount++;
            
            $temporaryPassword = $this->generatePassword ? $this->password : null;
            
            $result = $emailService->retryFailedEmail($user, 'welcome', [
                'temporaryPassword' => $temporaryPassword,
                'queue' => $this->queueEmail,
                'retry_count' => $this->emailRetryCount,
                'previous_delivery_id' => $this->emailDeliveryId,
            ]);
            
            // Update tracking information
            if (isset($result['delivery_id'])) {
                $this->emailDeliveryId = $result['delivery_id'];
            }
            
            if ($result['success']) {
                $this->emailStatus = $result['status'];
                $this->emailMessage = $result['message'];
                session()->flash('success', "Welcome email retry #{$this->emailRetryCount} successful: " . $result['message']);
                
                // Log successful retry
                \Log::info('Welcome email retry successful', [
                    'user_id' => $userId,
                    'retry_count' => $this->emailRetryCount,
                    'status' => $result['status'],
                    'delivery_id' => $this->emailDeliveryId,
                ]);
            } else {
                session()->flash('error', "Welcome email retry #{$this->emailRetryCount} failed: " . $result['message']);
                
                // Log failed retry
                \Log::warning('Welcome email retry failed', [
                    'user_id' => $userId,
                    'retry_count' => $this->emailRetryCount,
                    'error' => $result['error'] ?? 'Unknown error',
                ]);
            }
            
        } catch (\Exception $e) {
            \Log::error('Failed to retry welcome email', [
                'user_id' => $userId,
                'retry_count' => $this->emailRetryCount,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            session()->flash('error', 'Failed to retry welcome email: ' . $e->getMessage());
        }
    }

    /**
     * Send welcome email to the newly created user.
     *
     * @param User $user
     * @return array
     */
    private function sendWelcomeEmailToUser(User $user): array
    {
        try {
            // For customers, use the existing customer email service
            if ($user->isCustomer()) {
                $emailService = app(CustomerEmailService::class);
                
                // Send the temporary password if one was generated
                $temporaryPassword = $this->generatePassword ? $this->password : null;
                
                $result = $emailService->sendWelcomeEmail($user, $temporaryPassword, $this->queueEmail);
            } else {
                // For non-customers, send a basic welcome email
                $temporaryPassword = $this->generatePassword ? $this->password : null;
                
                if ($this->queueEmail) {
                    Mail::queue(new WelcomeUser($user, $temporaryPassword));
                } else {
                    Mail::send(new WelcomeUser($user, $temporaryPassword));
                }
                
                $result = [
                    'success' => true,
                    'status' => $this->queueEmail ? 'queued' : 'sent',
                    'message' => $this->queueEmail ? 'Welcome email queued for delivery' : 'Welcome email sent successfully',
                ];
            }
            
            // Track email delivery details
            if (isset($result['delivery_id'])) {
                $this->emailDeliveryId = $result['delivery_id'];
            }
            
            if (!$result['success']) {
                // Log the error but don't fail the user creation
                \Log::warning('Welcome email failed but user creation succeeded', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'role' => $user->role->name,
                    'error' => $result['error'] ?? 'Unknown error',
                    'delivery_id' => $this->emailDeliveryId,
                ]);
                
                session()->flash('warning', 'User created successfully, but welcome email could not be sent: ' . $result['message']);
            } else {
                $statusMessage = $result['status'] === 'queued' ? 'queued for delivery' : 'sent successfully';
                session()->flash('email_info', "Welcome email has been {$statusMessage}.");
                
                // Log successful email processing
                \Log::info('Welcome email processed successfully', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'role' => $user->role->name,
                    'status' => $result['status'],
                    'delivery_id' => $this->emailDeliveryId,
                    'queued' => $this->queueEmail,
                ]);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            // Log the error but don't fail the user creation
            \Log::error('Failed to send welcome email to user: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role->name,
                'trace' => $e->getTraceAsString(),
            ]);
            
            session()->flash('warning', 'User created successfully, but welcome email could not be sent.');
            
            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'Email service error: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Toggle email details display.
     *
     * @return void
     */
    public function toggleEmailDetails()
    {
        $this->showEmailDetails = !$this->showEmailDetails;
    }

    /**
     * Check email delivery status from queue.
     *
     * @return void
     */
    public function checkEmailDeliveryStatus()
    {
        if (!$this->emailDeliveryId) {
            session()->flash('info', 'No email delivery ID available to check status.');
            return;
        }

        try {
            $emailService = app(CustomerEmailService::class);
            $status = $emailService->checkDeliveryStatus($this->emailDeliveryId);
            
            if ($status['found']) {
                $this->emailStatus = $status['status'];
                $this->emailMessage = $status['message'];
                session()->flash('info', 'Email delivery status updated: ' . $status['message']);
            } else {
                session()->flash('warning', 'Email delivery status not found. It may have been processed already.');
            }
            
        } catch (\Exception $e) {
            \Log::error('Failed to check email delivery status', [
                'delivery_id' => $this->emailDeliveryId,
                'error' => $e->getMessage(),
            ]);
            
            session()->flash('error', 'Failed to check email delivery status: ' . $e->getMessage());
        }
    }

    /**
     * Generate a random password for the user.
     *
     * @return string
     */
    private function generateRandomPassword(): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        
        for ($i = 0; $i < 12; $i++) {
            $password .= $characters[random_int(0, strlen($characters) - 1)];
        }
        
        return $password;
    }

    /**
     * Get the list of available roles for user creation.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAvailableRolesProperty()
    {
        return Role::orderBy('name')->get();
    }

    /**
     * Get the list of available parishes in Jamaica.
     *
     * @return array
     */
    public function getParishesProperty()
    {
        return [
            'Kingston',
            'St. Andrew',
            'St. Thomas',
            'Portland',
            'St. Mary',
            'St. Ann',
            'Trelawny',
            'St. James',
            'Hanover',
            'Westmoreland',
            'St. Elizabeth',
            'Manchester',
            'Clarendon',
            'St. Catherine',
        ];
    }

    /**
     * Get the list of available pickup locations.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPickupLocationsProperty()
    {
        return Office::orderBy('name')->get();
    }

    /**
     * Set breadcrumbs for user creation page.
     *
     * @return void
     */
    protected function setUserCreateBreadcrumbs()
    {
        $this->setBreadcrumbs([
            $this->getHomeBreadcrumb(),
            [
                'title' => 'Users',
                'url' => \Route::has('admin.users.index') ? route('admin.users.index') : null
            ],
            [
                'title' => 'Create User',
                'url' => null
            ]
        ]);
    }

    public function render()
    {
        return view('livewire.users.user-create')
            ->extends('layouts.app')
            ->section('content');
    }
}