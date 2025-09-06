<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Applicant;
use App\Models\Program;
use App\Models\Course;
use App\Models\Campus;
use App\Models\AcademicYear;
use App\Models\AdmissionDocument;
use App\Models\ProgramCategory;
use App\Models\Province;
use App\Models\City;
use App\Models\Barangay;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Events\ApplicationStatusChanged;

class MultiStepForm extends Component
{
    use WithFileUploads;

    // Applicant model fields based on fillable attributes
    public
        $user_id,
        $campus_id,
        $academic_year_id,
        $academic_year,
        $applicant_number,
        $first_name,
        $last_name,
        $middle_name,
        $suffix,
        $dateofbirth,
        $sex,
        $address,
        // Location fields (old string-based)
        $province,
        $city,
        $barangay,
        // New location fields (ID-based)
        $province_id,
        $city_id,
        $barangay_id,
        $zip,
        $mobile,
        $landline,
        $email,
        $father_name,
        $father_mobile,
        $mother_name,
        $mother_mobile,
        $guardian_name,
        $guardian_address,
        $guardian_mobile,
        $school_year,
        $school_type,
        $school_name,
        $school_address,
        $strand,
        $grade,
        $program_category,
        $desired_program,
        $transferee,
        $status,
        // Form-specific fields (not in model)
        $doc1 = null, // For 2x2 picture
        $doc2 = [], // For report cards
        $doc3 = null, // For birth certificate
        $terms,
        $totalSteps = 6,
        $currentStep = 1;

    // Form state collections
    public $campuses = [];
    public $academicYears = [];
    public $programCategories = [];
    
    // Program selections
    public $filteredPrograms = []; 
    
    // Location dropdowns
    public $provinces = [];
    public $cities = [];
    public $barangays = [];
    
    // Zip code mapping (dummy data - replace with actual data or API)
    protected $zipCodes = [
        'BUL-MAL' => '3000', // Malolos
        'BUL-MEY' => '3020', // Meycauayan
        'BUL-SJDM' => '3023', // San Jose del Monte
        'BUL-BAL' => '3016', // Balagtas
        'BUL-BAL2' => '3006', // Baliuag
        'BUL-BOC' => '3018', // Bocaue
        'BUL-BLK' => '3017', // Bulakan
        'BUL-BUS' => '3007', // Bustos
        'BUL-CAL' => '3003', // Calumpit
        'BUL-DRT' => '3009', // DRT
        'BUL-GUI' => '3015', // Guiguinto
        'BUL-HAG' => '3002', // Hagonoy
        'BUL-MAR' => '3019', // Marilao
        'BUL-NOR' => '3013', // Norzagaray
        'BUL-OBA' => '3021', // Obando
        'BUL-PAN' => '3014', // Pandi
        'BUL-PAO' => '3001', // Paombong
        'BUL-PLA' => '3004', // Plaridel
        'BUL-PUL' => '3005', // Pulilan
        'BUL-SIL' => '3010', // San Ildefonso
        'BUL-SMG' => '3011', // San Miguel
        'BUL-SRA' => '3008', // San Rafael
        'BUL-SMA' => '3022', // Santa Maria
    ];

    // Search properties
    public $provinceSearch = '';
    public $citySearch = '';
    public $barangaySearch = '';
    
    // Results collections
    public $provinceResults = [];
    public $cityResults = [];
    public $barangayResults = [];
    
    // Dropdown visibility flags
    public $showProvinceDropdown = false;
    public $showCityDropdown = false;
    public $showBarangayDropdown = false;

    // Mount method to load campus names and academic years
    public function mount()
    {
        $this->campuses = Campus::pluck('name', 'id')->toArray();
        $this->academicYears = AcademicYear::where('is_active', true)->pluck('name', 'id')->toArray();
        
        // Initialize empty arrays for dependent dropdowns
        $this->programCategories = [];
        $this->filteredPrograms = [];
        
        // Load all provinces for the location dropdown
        $this->provinces = Province::all();
        $this->cities = collect();
        $this->barangays = collect();
        
        // Set default academic year if available
        if (!empty($this->academicYears)) {
            $activeYear = AcademicYear::where('is_active', true)->first();
            if ($activeYear) {
                $this->academic_year_id = $activeYear->id;
                $this->academic_year = $activeYear->name;
            }
        }
        
        // Set default status for new applicants
        $this->status = 'pending';
    }

    // Fetch programs related to the selected campus
    public function updatedCampusId()
    {
        if (!$this->campus_id) {
            $this->reset(['programCategories', 'program_category', 'filteredPrograms', 'desired_program']);
            return;
        }

        // Get program categories for this campus using the pivot relationship
        $this->programCategories = ProgramCategory::whereHas('programs.campuses', function ($query) {
            $query->where('campus_id', $this->campus_id);
        })->pluck('name', 'id')->toArray();
        
        // Reset dependent fields
        $this->reset(['program_category', 'filteredPrograms', 'desired_program']);
    }

    // Update courses when program category changes
    public function updatedProgramCategory()
    {
        if (!$this->program_category || !$this->campus_id) {
            $this->reset(['filteredPrograms', 'desired_program']);
            return;
        }

        // Filter programs by the selected category and campus using the pivot relationship
        $this->filteredPrograms = Program::where('program_category_id', $this->program_category)
            ->whereHas('campuses', function ($query) {
                $query->where('campus_id', $this->campus_id);
            })
            ->pluck('name', 'id')
            ->toArray();
        
        // Reset program selection
        $this->reset(['desired_program']);
    }

    // Location-related methods
    
    // Update cities when province is selected
    public function updatedProvinceId()
    {
        $this->cities = $this->province_id ? City::where('province_id', $this->province_id)->get() : collect();
        $this->reset(['city_id', 'barangay_id', 'zip']);
        $this->barangays = collect();
        
        // Store province name
        if ($this->province_id) {
            $province = Province::find($this->province_id);
            $this->province = $province ? $province->name : '';
        } else {
            $this->province = '';
        }
    }
    
    // Update barangays when city is selected
    public function updatedCityId()
    {
        $this->barangays = $this->city_id ? Barangay::where('city_id', $this->city_id)->get() : collect();
        $this->reset(['barangay_id', 'zip']);
        
        // Generate zip code based on city selection
        if ($this->city_id) {
            $city = City::find($this->city_id);
            if ($city) {
                $this->city = $city->name;
                
                // Get zip code from the mapping
                if (isset($this->zipCodes[$city->code])) {
                    $this->zip = $this->zipCodes[$city->code];
                }
            }
        } else {
            $this->city = '';
        }
    }
    
    // Update the string barangay field when barangay_id changes
    public function updatedBarangayId()
    {
        if ($this->barangay_id) {
            $barangay = Barangay::find($this->barangay_id);
            $this->barangay = $barangay ? $barangay->name : '';
        } else {
            $this->barangay = '';
        }
    }

    // Update logic when program is directly selected
    public function updatedDesiredProgram()
    {
        // Store the selected program directly from the dropdown
        // No additional processing needed as we're now using the program name directly
    }

    public function increaseStep()
    {
        $this->resetErrorBag();
        $this->validateData();
        $this->currentStep++;
        if ($this->currentStep > $this->totalSteps) {
            $this->currentStep = $this->totalSteps;
        }
    }

    public function decreaseStep()
    {
        $this->resetErrorBag();
        $this->currentStep--;
        if ($this->currentStep < 1) {
            $this->currentStep = 1;
        }
    }

    public function validateData()
    {
        switch ($this->currentStep) {
            case 1:
                $this->validate([
                    'campus_id' => 'required|exists:campuses,id',
                    'program_category' => 'required|exists:program_categories,id',
                    'desired_program' => 'required|string',
                    'transferee' => 'required|in:yes,no',
                ], [
                    'campus_id.required' => 'Campus selection is required.',
                    'campus_id.exists' => 'Selected campus does not exist.',
                    'program_category.required' => 'Program category is required.',
                    'program_category.exists' => 'Selected program category does not exist.',
                    'desired_program.required' => 'Program is required.',
                    'transferee.required' => 'Please specify if you are a transferee.',
                ]);
                break;

            case 2:
                $this->validate([
                    'first_name' => 'required|string|max:255|regex:/^[\pL\s\-]+$/u',
                    'last_name' => 'required|string|max:255|regex:/^[\pL\s\-]+$/u',
                    'middle_name' => 'nullable|string|max:255|regex:/^[\pL\s\-]+$/u',
                    'suffix' => 'nullable|string|max:10',
                    'sex' => 'required|in:male,female',
                    'dateofbirth' => 'required|date|before:today|after:1950-01-01',
                ], [
                    'first_name.required' => 'First name is required.',
                    'first_name.regex' => 'First name must contain only letters, spaces, and hyphens.',
                    'last_name.required' => 'Last name is required.',
                    'last_name.regex' => 'Last name must contain only letters, spaces, and hyphens.',
                    'middle_name.regex' => 'Middle name must contain only letters, spaces, and hyphens.',
                    'sex.required' => 'Gender selection is required.',
                    'sex.in' => 'Gender must be either Male or Female.',
                    'dateofbirth.required' => 'Date of birth is required.',
                    'dateofbirth.date' => 'Date of birth must be a valid date.',
                    'dateofbirth.before' => 'Date of birth cannot be in the future.',
                    'dateofbirth.after' => 'Date of birth is too far in the past.',
                ]);
                break;

            case 3:
                $this->validate([
                    'address' => 'required|string|max:255',
                    'province_id' => 'required|exists:provinces,id',
                    'city_id' => 'required|exists:cities,id',
                    'barangay_id' => 'required|exists:barangays,id',
                    'zip' => 'required|numeric|digits_between:4,6',
                    'landline' => ['nullable', 'string', 'regex:/^(\d{7,8})$/'],
                    'email' => 'required|email:rfc,dns|max:255|unique:applicants,email',
                ], [
                    'address.required' => 'Address is required.',
                    'province_id.required' => 'Province is required.',
                    'province_id.exists' => 'Selected province does not exist.',
                    'city_id.required' => 'City is required.',
                    'city_id.exists' => 'Selected city does not exist.',
                    'barangay_id.required' => 'Barangay is required.',
                    'barangay_id.exists' => 'Selected barangay does not exist.',
                    'zip.required' => 'Zip code is required.',
                    'zip.numeric' => 'Zip code must contain only numbers.',
                    'zip.digits_between' => 'Zip code must be between 4 to 6 digits.',
                    'landline.regex' => 'Invalid landline number format. It must be 7-8 digits long.',
                    'email.required' => 'Email address is required.',
                    'email.email' => 'Enter a valid email address.',
                    'email.unique' => 'This email is already registered.',
                ]);
                break;

            case 4:
                $this->validate([
                    'father_name' => 'nullable|string|max:255|regex:/^[\pL\s\-]+$/u',
                    'father_mobile' => ['nullable', 'string', 'regex:/^(09\d{9})$/'],
                    'mother_name' => 'nullable|string|max:255|regex:/^[\pL\s\-]+$/u',
                    'mother_mobile' => ['nullable', 'string', 'regex:/^(09\d{9})$/'],
                    'guardian_name' => 'required|string|max:255|regex:/^[\pL\s\-]+$/u',
                    'guardian_address' => 'required|string|max:255',
                    'guardian_mobile' => ['required', 'string', 'regex:/^(09\d{9})$/'],
                ], [
                    'father_name.regex' => 'Father\'s name must contain only letters, spaces, and hyphens.',
                    'father_mobile.regex' => 'Invalid father\'s mobile number format. It should start with 09 and contain 11 digits.',
                    'mother_name.regex' => 'Mother\'s name must contain only letters, spaces, and hyphens.',
                    'mother_mobile.regex' => 'Invalid mother\'s mobile number format. It should start with 09 and contain 11 digits.',
                    'guardian_name.required' => 'Guardian\'s name is required.',
                    'guardian_name.regex' => 'Guardian\'s name must contain only letters, spaces, and hyphens.',
                    'guardian_address.required' => 'Guardian\'s address is required.',
                    'guardian_mobile.required' => 'Guardian\'s mobile number is required.',
                    'guardian_mobile.regex' => 'Invalid guardian\'s mobile number format. It should start with 09 and contain 11 digits.',
                ]);
                break;

            case 5:
                $this->validate([
                    'school_year' => ['required', 'integer', 'min:2000', 'max:2050'],
                    'school_type' => 'required|in:Public,Private',
                    'school_name' => 'required|string|max:255',
                    'school_address' => 'required|string|max:255',
                    'strand' => 'required|string|max:255',
                    'grade' => 'required|numeric|between:75,100',
                ], [
                    'school_year.required' => 'School year is required.',
                    'school_year.integer' => 'School year must be a valid year.',
                    'school_year.min' => 'School year must be 2000 or later.',
                    'school_year.max' => 'School year must be 2050 or earlier.',
                    'school_type.required' => 'School type is required.',
                    'school_type.in' => 'School type must be either Public or Private.',
                    'school_name.required' => 'School name is required.',
                    'school_address.required' => 'School address is required.',
                    'strand.required' => 'Strand is required.',
                    'grade.required' => 'Grade is required.',
                    'grade.numeric' => 'Grade must be a number.',
                    'grade.between' => 'Grade must be between 75 and 100.',
                ]);
                break;
        }
    }

    public function register()
    {
        $this->resetErrorBag();

        if ($this->currentStep == 6) {
            $this->validate([
                'doc1' => 'required|file|mimes:jpeg,png,jpg,gif,svg,pdf|max:6144',
                'doc2.0' => 'required|file|mimes:jpeg,png,jpg,gif,svg,pdf|max:6144',
                'doc2.1' => 'required|file|mimes:jpeg,png,jpg,gif,svg,pdf|max:6144',
                'doc3' => 'required|file|mimes:jpeg,png,jpg,gif,svg,pdf|max:6144',
                'terms' => 'accepted',
            ], [
                'doc1.required' => 'The 2x2 Formal Picture is required.',
                'doc1.file' => 'The 2x2 Formal Picture must be a valid file.',
                'doc1.mimes' => 'The 2x2 Formal Picture must be in jpeg, png, jpg, gif, svg, or pdf format.',
                'doc1.max' => 'The 2x2 Formal Picture must not exceed 2MB.',
                'doc2.0.required' => 'The Report Card Front Page is required.',
                'doc2.0.file' => 'The Report Card Front Page must be a valid file.',
                'doc2.0.mimes' => 'The Report Card Front Page must be in jpeg, png, jpg, gif, svg, or pdf format.',
                'doc2.0.max' => 'The Report Card Front Page must not exceed 2MB.',
                'doc2.1.required' => 'The Report Card Back Page is required.',
                'doc2.1.file' => 'The Report Card Back Page must be a valid file.',
                'doc2.1.mimes' => 'The Report Card Back Page must be in jpeg, png, jpg, gif, svg, or pdf format.',
                'doc2.1.max' => 'The Report Card Back Page must not exceed 2MB.',
                'doc3.required' => 'The PSA Certificate of Live Birth is required.',
                'doc3.file' => 'The PSA Certificate of Live Birth must be a valid file.',
                'doc3.mimes' => 'The PSA Certificate of Live Birth must be in jpeg, png, jpg, gif, svg, or pdf format.',
                'doc3.max' => 'The PSA Certificate of Live Birth must not exceed 2MB.',
                'terms.accepted' => 'You must accept the Terms and Conditions to proceed.',
            ]);

            // Generate applicant number
            $applicantCode = 'AP-' . strtoupper(substr($this->last_name, 0, 1) . substr($this->first_name, 0, 1)) . '-' . date('Y') . '-' . Str::random(4);
            $this->applicant_number = $applicantCode;

            // Process transferee value (convert to boolean for database)
            $isTransferee = ($this->transferee === 'yes');

            // Create applicant record
            $applicant = Applicant::create([
                'user_id' => Auth::id() ?? 1, // Use authenticated user ID or default to 1
                'campus_id' => $this->campus_id,
                'academic_year_id' => $this->academic_year_id,
                'academic_year' => $this->academic_year,
                'applicant_number' => $this->applicant_number,
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'middle_name' => $this->middle_name,
                'suffix' => $this->suffix,
                'dateofbirth' => $this->dateofbirth,
                'sex' => strtolower($this->sex),
                'address' => $this->address,
                'province' => $this->province,
                'city' => $this->city,
                'barangay' => $this->barangay,
                'zip' => $this->zip,
                'mobile' => $this->mobile,
                'landline' => $this->landline,
                'email' => $this->email,
                'father_name' => $this->father_name,
                'father_mobile' => $this->father_mobile,
                'mother_name' => $this->mother_name,
                'mother_mobile' => $this->mother_mobile,
                'guardian_name' => $this->guardian_name,
                'guardian_address' => $this->guardian_address,
                'guardian_mobile' => $this->guardian_mobile,
                'school_year' => $this->school_year,
                'school_type' => $this->school_type,
                'school_name' => $this->school_name,
                'school_address' => $this->school_address,
                'strand' => $this->strand,
                'grade' => $this->grade,
                'program_category' => $this->program_category,
                'desired_program' => $this->desired_program,
                'transferee' => $isTransferee,
                'status' => $this->status,
                // Add reference fields to the location models
                'province_id' => $this->province_id,
                'city_id' => $this->city_id,
                'barangay_id' => $this->barangay_id,
            ]);

            // Store documents
            $this->storeDocument($applicant->id, $this->doc1, 'formal_picture', 'Formal Picture (2x2)');
            
            // Store multiple report card files
            $this->storeDocument($applicant->id, $this->doc2[0], 'report_card_front', 'Report Card Front');
            $this->storeDocument($applicant->id, $this->doc2[1], 'report_card_back', 'Report Card Back');
            
            $this->storeDocument($applicant->id, $this->doc3, 'birth_certificate', 'PSA Birth Certificate');

            // Store name and email in session
            session([
                'name' => $this->first_name . ' ' . $this->last_name,
                'email' => $this->email,
                'applicant_number' => $this->applicant_number,
            ]);

            // Reset form
            $this->reset();
            $this->currentStep = 1; 

            // Trigger application status changed event for the new applicant
            event(new ApplicationStatusChanged($applicant, '', 'pending'));

            // Redirect to the success page
            return redirect()->route('registration.success');
        }
    }

    // Helper method to store document files
    private function storeDocument($applicantId, $file, $docType, $docName)
    {
        $timestamp = now()->format('YmdHis');
        $fileName = "{$timestamp}_{$docType}." . $file->getClientOriginalExtension();
        
        // Store the file directly in the public/assets/documents/admission_documents directory
        $path = $file->storeAs('', $fileName, 'admissions');
        
        // Full URL for the file
        $fileUrl = config('app.url') . '/assets/documents/admission_documents/' . $fileName;
        
        AdmissionDocument::create([
            'applicant_id' => $applicantId,
            'document_type' => $docType,
            'status' => 'submitted',
            'remarks' => $docName,
            'submitted_at' => now(),
            'file_path' => $path,
        ]);
    }

    // Close dropdowns when clicking outside
    public function hideDropdowns()
    {
        $this->showProvinceDropdown = false;
        $this->showCityDropdown = false;
        $this->showBarangayDropdown = false;
    }

    // Public methods for dropdown search that can be called directly from the template
    public function loadProvinces()
    {
        if (empty($this->provinceSearch)) {
            // Show some default provinces when empty
            $this->provinceResults = Province::limit(10)->get();
        } else {
            $this->provinceResults = Province::where('name', 'like', '%' . $this->provinceSearch . '%')
                ->limit(15)
                ->get();
        }
        
        $this->showProvinceDropdown = true;
    }
    
    public function loadCities()
    {
        $query = City::query();
        
        // If province is selected, filter cities by province
        if ($this->province_id) {
            $query->where('province_id', $this->province_id);
        }
        
        if (empty($this->citySearch)) {
            // Show cities from selected province or some defaults
            if ($this->province_id) {
                $this->cityResults = $query->limit(15)->get();
            } else {
                $this->cityResults = City::limit(10)->get();
            }
        } else {
            $this->cityResults = $query->where('name', 'like', '%' . $this->citySearch . '%')
                ->limit(15)
                ->get();
        }
        
        $this->showCityDropdown = true;
    }
    
    public function loadBarangays()
    {
        $query = Barangay::query();
        
        // If city is selected, filter barangays by city
        if ($this->city_id) {
            $query->where('city_id', $this->city_id);
        }
        
        if (empty($this->barangaySearch)) {
            // Show barangays from selected city or some defaults
            if ($this->city_id) {
                $this->barangayResults = $query->limit(15)->get();
            } else {
                $this->barangayResults = Barangay::limit(10)->get();
            }
        } else {
            $this->barangayResults = $query->where('name', 'like', '%' . $this->barangaySearch . '%')
                ->limit(15)
                ->get();
        }
        
        $this->showBarangayDropdown = true;
    }

    // Selection methods
    public function selectProvince($id, $name)
    {
        $this->province_id = $id;
        $this->province = $name;
        $this->provinceSearch = $name;
        $this->showProvinceDropdown = false;
        
        // Reset city and barangay when province changes
        $this->reset(['city_id', 'city', 'citySearch', 'barangay_id', 'barangay', 'barangaySearch', 'zip']);
        
        // Get the postal code based on province if needed
        $province = Province::find($id);
        if ($province && isset($this->zipCodes[$province->code])) {
            $this->zip = $this->zipCodes[$province->code];
        }
    }
    
    public function selectCity($id, $name)
    {
        $this->city_id = $id;
        $this->city = $name;
        $this->citySearch = $name;
        $this->showCityDropdown = false;
        
        // Reset barangay when city changes
        $this->reset(['barangay_id', 'barangay', 'barangaySearch']);
        
        // Get city data for postal code
        $city = City::find($id);
        if ($city) {
            // Update province if it doesn't match
            if ($this->province_id != $city->province_id) {
                $province = Province::find($city->province_id);
                if ($province) {
                    $this->province_id = $province->id;
                    $this->province = $province->name;
                    $this->provinceSearch = $province->name;
                }
            }
            
            // Get zip code from mapping
            if (isset($this->zipCodes[$city->code])) {
                $this->zip = $this->zipCodes[$city->code];
            }
        }
    }
    
    public function selectBarangay($id, $name)
    {
        $this->barangay_id = $id;
        $this->barangay = $name;
        $this->barangaySearch = $name;
        $this->showBarangayDropdown = false;
        
        // Get barangay data and update parent entities if needed
        $barangay = Barangay::with('city.province')->find($id);
        if ($barangay && $barangay->city) {
            // Update city if it doesn't match
            if ($this->city_id != $barangay->city_id) {
                $this->city_id = $barangay->city_id;
                $this->city = $barangay->city->name;
                $this->citySearch = $barangay->city->name;
                
                // Update province if it doesn't match
                if ($barangay->city->province && $this->province_id != $barangay->city->province->id) {
                    $this->province_id = $barangay->city->province->id;
                    $this->province = $barangay->city->province->name;
                    $this->provinceSearch = $barangay->city->province->name;
                }
                
                // Get zip code from city
                if (isset($this->zipCodes[$barangay->city->code])) {
                    $this->zip = $this->zipCodes[$barangay->city->code];
                }
            }
        }
    }

    // Lifecycle methods to trigger searches when input changes
    public function updatedProvinceSearch()
    {
        if (strlen($this->provinceSearch) >= 2 || empty($this->provinceSearch)) {
            $this->loadProvinces();
        } else if (strlen($this->provinceSearch) < 2 && !empty($this->provinceSearch)) {
            $this->showProvinceDropdown = true;
        }
    }
    
    public function updatedCitySearch()
    {
        if (strlen($this->citySearch) >= 2 || empty($this->citySearch)) {
            $this->loadCities();
        } else if (strlen($this->citySearch) < 2 && !empty($this->citySearch)) {
            $this->showCityDropdown = true;
        }
    }
    
    public function updatedBarangaySearch()
    {
        if (strlen($this->barangaySearch) >= 2 || empty($this->barangaySearch)) {
            $this->loadBarangays();
        } else if (strlen($this->barangaySearch) < 2 && !empty($this->barangaySearch)) {
            $this->showBarangayDropdown = true;
        }
    }

    // Public methods for dropdown changes
    public function onProvinceChange()
    {
        // This will refresh the cities dropdown based on selected province
        $this->cities = $this->province_id ? City::where('province_id', $this->province_id)->get() : collect();
        $this->reset(['city_id', 'barangay_id', 'zip']);
        $this->barangays = collect();
        
        // Store province name
        if ($this->province_id) {
            $province = Province::find($this->province_id);
            $this->province = $province ? $province->name : '';
            
            // Get the postal code based on province if needed
            if ($province && isset($this->zipCodes[$province->code])) {
                $this->zip = $this->zipCodes[$province->code];
            }
        } else {
            $this->province = '';
        }
    }
    
    public function onCityChange()
    {
        // This will refresh the barangays dropdown based on selected city
        $this->barangays = $this->city_id ? Barangay::where('city_id', $this->city_id)->get() : collect();
        $this->reset(['barangay_id', 'zip']);
        
        // Generate zip code based on city selection
        if ($this->city_id) {
            $city = City::find($this->city_id);
            if ($city) {
                $this->city = $city->name;
                
                // Update province if it doesn't match
                if ($this->province_id != $city->province_id) {
                    $province = Province::find($city->province_id);
                    if ($province) {
                        $this->province_id = $province->id;
                        $this->province = $province->name;
                    }
                }
                
                // Get zip code from the mapping
                if (isset($this->zipCodes[$city->code])) {
                    $this->zip = $this->zipCodes[$city->code];
                }
            }
        } else {
            $this->city = '';
        }
    }
    
    public function onBarangayChange()
    {
        // Update the string barangay field when barangay_id changes
        if ($this->barangay_id) {
            $barangay = Barangay::with('city.province')->find($this->barangay_id);
            if ($barangay) {
                $this->barangay = $barangay->name;
                
                // Update city if it doesn't match
                if ($barangay->city && $this->city_id != $barangay->city_id) {
                    $this->city_id = $barangay->city_id;
                    $this->city = $barangay->city->name;
                    
                    // Update province if it doesn't match
                    if ($barangay->city->province && $this->province_id != $barangay->city->province->id) {
                        $this->province_id = $barangay->city->province->id;
                        $this->province = $barangay->city->province->name;
                    }
                    
                    // Get zip code from city
                    if ($barangay->city && isset($this->zipCodes[$barangay->city->code])) {
                        $this->zip = $this->zipCodes[$barangay->city->code];
                    }
                }
            }
        } else {
            $this->barangay = '';
        }
    }

    public function render()
    {
        return view('livewire.multi-step-form');
    }
}
