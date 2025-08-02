<?php

namespace App\Http\Livewire\Customers;

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

class CustomerCreate extends Component
{
    use AuthorizesRequests, HasBreadcrumbs;
    // Customer basic information
    public $firstName = '';
    public $lastName = '';
    public $email = '';
    public $password = '';
    public $passwordConfirmation = '';
    
    // Profile information
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

    protected $rules = [
        'firstName' => 'required|string|max:255',
        'lastName' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required_if:generatePassword,false|min:8|same:passwordConfirmation',
        'telephoneNumber' => 'required|string|max:20',
        'taxNumber' => 'nullable|string|max:20',
        'streetAddress' => 'required|string|max:500',
        'cityTown' => 'required|string|max:100',
        'parish' => 'required|string|max:50',
        'country' => 'required|string|max:50',
        'pickupLocation' => 'required|integer|exists:offices,id',
    ];

    protected $validationAttributes = [
        'firstName' => 'first name',
        'lastName' => 'last name',
        'telephoneNumber' => 'telephone number',
        'taxNumber' => 'tax number',
        'streetAddress' => 'street address',
        'cityTown' => 'city/town',
        'pickupLocation' => 'pickup location',
        'passwordConfirmation' => 'password confirmation',
    ];

    public function mount()
    {
        // Check if user can create customers
        $this->authorize('customer.create');
        
        $this->setCustomerCreateBreadcrumbs();
        
        // Generate a random password by default
        if ($this->generatePassword) {
            $this->password = $this->generateRandomPassword();
            $this->passwordConfirmation = $this->password;
        }
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

    public function create()
    {
        // Re-check authorization before creating
        $this->authorize('customer.create');
        
        $this->isCreating = true;

        try {
            // Validate the form data
            $this->validate();

            DB::beginTransaction();

            // Get customer role
            $customerRole = Role::where('name', 'customer')->first();
            if (!$customerRole) {
                throw new \Exception('Customer role not found in the system.');
            }

            // Create the user
            $user = User::create([
                'first_name' => $this->firstName,
                'last_name' => $this->lastName,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'role_id' => $customerRole->id,
            ]);

            // Generate account number
            $accountNumberService = new AccountNumberService();
            $accountNumber = $accountNumberService->generate();

            // Create the profile
            $user->profile()->create([
                'account_number' => $accountNumber,
                'tax_number' => $this->taxNumber,
                'telephone_number' => $this->telephoneNumber,
                'street_address' => $this->streetAddress,
                'city_town' => $this->cityTown,
                'parish' => $this->parish,
                'country' => $this->country,
                'pickup_location' => $this->pickupLocation,
            ]);

            // Send welcome email if requested
            if ($this->sendWelcomeEmail) {
                $emailResult = $this->sendWelcomeEmailToCustomer($user);
                $this->emailStatus = $emailResult['status'];
                $this->emailMessage = $emailResult['message'];
            }

            // Fire registered event
            event(new Registered($user));

            DB::commit();

            // Show success message
            session()->flash('success', "Customer {$user->full_name} has been created successfully with account number {$accountNumber}.");

            // Redirect to customer profile
            return redirect()->route('admin.customers.show', $user);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            $this->isCreating = false;
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->isCreating = false;
            session()->flash('error', 'Failed to create customer: ' . $e->getMessage());
        }
    }

    public function cancel()
    {
        return redirect()->route('admin.customers.index');
    }

    /**
     * Retry sending welcome email for a customer.
     *
     * @param int $customerId
     * @return void
     */
    public function retryWelcomeEmail($customerId)
    {
        try {
            $customer = User::findOrFail($customerId);
            $emailService = app(CustomerEmailService::class);
            
            // Increment retry count
            $this->emailRetryCount++;
            
            // Check if we've exceeded maximum retry attempts
            if ($this->emailRetryCount > 3) {
                session()->flash('error', 'Maximum retry attempts exceeded. Please contact system administrator.');
                return;
            }
            
            $temporaryPassword = $this->generatePassword ? $this->password : null;
            
            $result = $emailService->retryFailedEmail($customer, 'welcome', [
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
                    'customer_id' => $customerId,
                    'retry_count' => $this->emailRetryCount,
                    'status' => $result['status'],
                    'delivery_id' => $this->emailDeliveryId,
                ]);
            } else {
                session()->flash('error', "Welcome email retry #{$this->emailRetryCount} failed: " . $result['message']);
                
                // Log failed retry
                \Log::warning('Welcome email retry failed', [
                    'customer_id' => $customerId,
                    'retry_count' => $this->emailRetryCount,
                    'error' => $result['error'] ?? 'Unknown error',
                ]);
            }
            
        } catch (\Exception $e) {
            \Log::error('Failed to retry welcome email', [
                'customer_id' => $customerId,
                'retry_count' => $this->emailRetryCount,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            session()->flash('error', 'Failed to retry welcome email: ' . $e->getMessage());
        }
    }

    /**
     * Send welcome email to the newly created customer.
     *
     * @param User $user
     * @return array
     */
    private function sendWelcomeEmailToCustomer(User $user): array
    {
        try {
            $emailService = app(CustomerEmailService::class);
            
            // Send the temporary password if one was generated
            $temporaryPassword = $this->generatePassword ? $this->password : null;
            
            $result = $emailService->sendWelcomeEmail($user, $temporaryPassword, $this->queueEmail);
            
            // Track email delivery details
            if (isset($result['delivery_id'])) {
                $this->emailDeliveryId = $result['delivery_id'];
            }
            
            if (!$result['success']) {
                // Log the error but don't fail the customer creation
                \Log::warning('Welcome email failed but customer creation succeeded', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $result['error'] ?? 'Unknown error',
                    'delivery_id' => $this->emailDeliveryId,
                ]);
                
                session()->flash('warning', 'Customer created successfully, but welcome email could not be sent: ' . $result['message']);
            } else {
                $statusMessage = $result['status'] === 'queued' ? 'queued for delivery' : 'sent successfully';
                session()->flash('email_info', "Welcome email has been {$statusMessage}.");
                
                // Log successful email processing
                \Log::info('Welcome email processed successfully', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'status' => $result['status'],
                    'delivery_id' => $this->emailDeliveryId,
                    'queued' => $this->queueEmail,
                ]);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            // Log the error but don't fail the customer creation
            \Log::error('Failed to send welcome email to customer: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'email' => $user->email,
                'trace' => $e->getTraceAsString(),
            ]);
            
            session()->flash('warning', 'Customer created successfully, but welcome email could not be sent.');
            
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
     * Generate a random password for the customer.
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

    public function render()
    {
        return view('livewire.customers.customer-create')
            ->extends('layouts.app')
            ->section('content');
    }
}