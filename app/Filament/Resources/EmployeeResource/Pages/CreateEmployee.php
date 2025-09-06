<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use App\Models\User;
use App\Events\EmployeeCreated;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    // Disable the default "Created" notification
    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }
    
    // Override the create method to handle user creation first
    protected function handleRecordCreation(array $data): Model
    {
        // Generate employee number based on campus
        $campusId = $data['campus_id'];
        $campus = \App\Models\Campus::find($campusId);
        
        if (!$campus) {
            throw new \Exception('Campus not found');
        }
        
        // Get campus code
        $campusCode = $campus->code;
        
        // Find the latest employee with this campus code pattern
        $latestEmployee = \App\Models\Employee::where('employee_number', 'like', $campusCode . '%')
            ->orderBy('employee_number', 'desc')
            ->first();
        
        // Generate new sequential number
        if ($latestEmployee) {
            // Extract the numeric part from the latest employee number
            $numericPart = substr($latestEmployee->employee_number, strlen($campusCode));
            // Increment and pad with zeros to maintain format
            $nextNumber = str_pad((int)$numericPart + 1, 5, '0', STR_PAD_LEFT);
        } else {
            // First employee for this campus
            $nextNumber = '00001';
        }
        
        // Create the new employee number
        $data['employee_number'] = $campusCode . $nextNumber;
        
        // Create the user first
        $user = $this->createUser($data);
        
        // Set the user_id in the data
        $data['user_id'] = $user->id;
        
        // Force employee_status to be active
        $data['employee_status'] = 'active';
        
        // Create the employee with the user_id
        $employee = static::getModel()::create($data);
        
        return $employee;
    }
    
    // Method to create a user
    protected function createUser(array $data): User
    {
        // Find the latest user to generate the next ID
        $latestUser = User::latest('id')->first();
        $nextId = $latestUser ? $latestUser->id + 1 : 1;
        
        // Create the user
        $user = new User();
        $user->id = $nextId;
        $user->username = $data['employee_number'];
        $user->email = $data['email'] ?? 'employee' . $nextId . '@example.com';
        $user->user_type = 'employee';
        
        // Generate a random 8-character password
        $password = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 8);
        $user->password = bcrypt($password);
        $user->temp_password = $password;
        
        $user->save();
        
        // Store password temporarily for the success message
        session()->flash('generated_password', $password);
        
        return $user;
    }
    
    protected function afterCreate(): void
    {
        // Get the employee with its relations
        $employee = $this->record->fresh(['user']);
        $user = $employee->user;
        
        // Verify the user was created properly
        if (!$user) {
            \Illuminate\Support\Facades\Log::error('Error: User not found for employee', [
                'employee_id' => $employee->id,
                'employee_number' => $employee->employee_number
            ]);
            
            Notification::make()
                ->title('Warning')
                ->body('Employee created but user account was not properly linked. Please check logs.')
                ->danger()
                ->send();
            
            return;
        }
        
        // Show the generated credentials to the admin if available
        if (session()->has('generated_password')) {
            $password = session('generated_password');
            
            // Dispatch the event with fresh data
            try {
                \Illuminate\Support\Facades\Log::info('Dispatching EmployeeCreated event', [
                    'employee_id' => $employee->id,
                    'email' => $user->email
                ]);
                
                event(new EmployeeCreated($employee, $password));
                
                \Illuminate\Support\Facades\Log::info('EmployeeCreated event dispatched successfully');
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to dispatch EmployeeCreated event', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
            
            Notification::make()
                ->title('Employee Created')
                ->body('Employee created successfully. An email with credentials has been sent to the employee.')
                ->success()
                ->send();
            
            // Clear the password from the session
            session()->forget('generated_password');
        }
    }
} 