<div>
    <form wire:submit.prevent="register">
        {{-- Error Alert --}}
        @if ($errors->any())
        <div class="alert alert-danger shadow-sm border-0">
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-circle fa-lg me-2"></i>
                <strong>Error Occurred:</strong>&nbsp;Please check the form for errors and try again.
            </div>
        </div>
        @endif

        {{-- Interactive Progress Bar with Step Indicators at the TOP --}}
        <div class="mb-5 mt-4">
            <div class="progress position-relative progress-bar-container">
                <div class="progress-bar"
                    :style="{ width: '{{ ($currentStep/$totalSteps) * 100 }}%' }">
                </div>

                {{-- Step Indicators --}}
                @for ($i = 1; $i <= $totalSteps; $i++)
                    @php
                    $stepLabels=[ 'Choose Path' , 'Personal Info' , 'Contact Info' , 'Guardian Info' , 'Education' , 'Documents'
                    ];
                    $stepIcons=[ 'road' , 'user' , 'map-marker-alt' , 'users' , 'graduation-cap' , 'upload'
                    ];
                    $completed=$currentStep> $i;
                    $active = $currentStep == $i;
                    $disabled = $currentStep < $i;
                        $btnSize=54; // Increased size
                        @endphp

                        <div class="position-absolute d-flex flex-column align-items-center justify-content-center step-indicator"
                        style="left: {{ (($i-1)/$totalSteps) * 100 }}%;">
                        <button type="button"
                            wire:click="{{ !$disabled ? 'currentStep = '.$i : '' }}"
                            class="rounded-circle step-button {{ $completed ? 'bg-success' : ($active ? 'bg-primary' : 'bg-secondary') }} {{ $active ? 'active' : '' }} {{ $disabled ? 'disabled' : '' }}"
                            data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            title="{{ $stepLabels[$i-1] }}">

                            @if($completed)
                            {{-- Success animation for completed steps --}}
                            <div class="position-absolute animation-container">
                                <div class="position-absolute bg-white opacity-25 pulse-animation">
                                </div>
                            </div>
                            <i class="fas fa-check"></i>
                            @elseif($active)
                            {{-- Pulse animation for active step --}}
                            <div class="position-absolute pulse-border"></div>
                            <i class="fas fa-{{ $stepIcons[$i-1] }} fa-bounce"></i>
                            @else
                            <i class="fas fa-{{ $stepIcons[$i-1] }}"></i>
                            @endif
                        </button>
                        <span class="mt-3 fw-bold text-center step-label {{ $active ? 'text-primary' : ($completed ? 'text-success' : 'text-secondary') }}">
                            {{ $stepLabels[$i-1] }}
                        </span>
            </div>
            @endfor

            {{-- Progress Line Connectors --}}
            <div class="position-absolute progress-connectors">
                @for ($i = 1; $i < $totalSteps; $i++)
                    @php
                    $segmentStart=(($i-1)/$totalSteps) * 100;
                    $segmentEnd=($i/$totalSteps) * 100;
                    $completed=$currentStep> $i;
                    @endphp
                    <div class="position-absolute progress-segment {{ $completed ? 'completed' : '' }}"
                        :style="{ left: '{{ $segmentStart }}%', width: '{{ $segmentEnd - $segmentStart }}%' }">
                    </div>
                    @endfor
            </div>
        </div>
</div>

<style>
    /* Base Progress Bar Styles */
    .progress-bar-container {
        height: 30px;
        margin-top: 30px;
        margin-bottom: 35px;
        border-radius: 30px;
        background-color: #f0f0f0;
        box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .progress-bar {
        background: linear-gradient(45deg, #2196F3, #00BCD4);
        border-radius: 30px;
        transition: width 0.6s ease-in-out;
    }

    /* Step Indicators */
    .step-indicator {
        top: -27px;
        width: calc(100% / 6);
    }

    .step-button {
        width: 54px;
        height: 54px;
        color: white;
        border: 4px solid white !important;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
        z-index: 2;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 0;
        position: relative;
        font-size: 1.4rem;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .step-button.active {
        transform: scale(1.2);
    }

    .step-button.disabled {
        cursor: not-allowed;
    }

    .step-button:hover:not(.disabled) {
        transform: scale(1.1);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }

    .step-button.active:hover {
        transform: scale(1.3);
    }

    .step-label {
        font-size: 0.9rem;
        max-width: 95%;
    }

    /* Progress Connectors */
    .progress-connectors {
        top: 14px;
        left: 0;
        right: 0;
        height: 2px;
        z-index: 1;
    }

    .progress-segment {
        height: 100%;
        background-color: #dee2e6;
        transition: background-color 0.3s ease;
    }

    .progress-segment.completed {
        background-color: #28a745;
    }

    /* Animation containers */
    .animation-container {
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        border-radius: 50%;
        pointer-events: none;
        overflow: hidden;
    }

    .pulse-animation {
        width: 100%;
        height: 100%;
        transform: scale(0);
        border-radius: 50%;
        animation: pulse-animation 1.5s ease-out;
    }

    .pulse-border {
        top: -4px;
        left: -4px;
        right: -4px;
        bottom: -4px;
        border: 2px solid rgba(0, 123, 255, 0.5);
        border-radius: 50%;
        animation: pulse-border 2s infinite;
        pointer-events: none;
    }

    /* Form styling */
    .form-control,
    .input-group-text,
    .form-select {
        border-radius: 0.375rem;
        padding: 0.75rem 1rem;
        font-size: 1rem;
    }

    .input-group>.input-group-text {
        background-color: #f8f9fa;
        border-right: none;
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }

    .input-group>.form-control,
    .input-group>.form-select {
        border-left: none;
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }

    .input-group:focus-within .input-group-text {
        border-color: #86b7fe;
    }

    .hover-bg-light {
        cursor: pointer;
        transition: background-color 0.2s ease;
    }

    .hover-bg-light:hover {
        background-color: #f8f9fa;
    }

    /* Animations */
    @keyframes pulse-animation {
        0% {
            opacity: 0.8;
            transform: scale(0);
        }

        100% {
            opacity: 0;
            transform: scale(2);
        }
    }

    @keyframes pulse-border {
        0% {
            transform: scale(1);
            opacity: 1;
        }

        50% {
            transform: scale(1.1);
            opacity: 0.7;
        }

        100% {
            transform: scale(1);
            opacity: 1;
        }
    }

    @keyframes bounce-icon {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-5px);
        }
    }

    .fa-bounce {
        animation: bounce-icon 1s infinite;
    }

    /* Step 3 specific styles */
    .step-three .card-header {
        background-color: #007bff !important;
    }

    .alert-danger {
        display: flex;
        align-items: center;
        background-color: #fff;
        color: #dc3545;
        border-left: 3px solid #dc3545;
        padding: 0.75rem 1.25rem;
        margin-bottom: 1.5rem;
        border-radius: 0.25rem;
    }

    .alert-danger i {
        margin-right: 10px;
    }
</style>

{{-- STEP 1 --}}
@if ($currentStep == 1)
<div class="step-one">
    <div class="card shadow-lg border-0 rounded-3">
        <div class="card-header bg-primary text-white text-center fw-bold py-3">
            <h5 class="mb-0"><i class="fas fa-road"></i> STEP 1/6 - Choose Your Path</h5>
        </div>
        <div class="card-body">
            <p class="text-danger fw-semibold"><i class="fas fa-exclamation-circle"></i> All fields are required unless noted.</p>

            <div class="row">
                {{-- Campus Selection --}}
                <div class="col-md-12">
                    <div class="form-floating mb-3">
                        <select id="campus_id" wire:model.live="campus_id" class="form-select @error('campus_id') is-invalid @enderror">
                            <option value="">Select a Campus</option>
                            @foreach($campuses as $id => $campus_name)
                            <option value="{{ $id }}">{{ $campus_name }}</option>
                            @endforeach
                        </select>
                        <label for="campus_id"><i class="fas fa-university"></i> Campus <span class="text-danger">*</span></label>
                        @error('campus_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Program Category --}}
                <div class="col-md-12">
                    <div class="form-floating mb-3">
                        <select id="program_category" class="form-select @error('program_category') is-invalid @enderror" wire:model.live="program_category" @if(empty($programCategories)) disabled @endif>
                            <option value="" selected>Choose Category</option>
                            @if(!empty($programCategories))
                            @foreach($programCategories as $id => $category)
                            <option value="{{ $id }}">{{ $category }}</option>
                            @endforeach
                            @endif
                        </select>
                        <label for="program_category"><i class="fas fa-layer-group"></i> Program Category <span class="text-danger">*</span></label>
                        @error('program_category') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @if(empty($programCategories) && !empty($campus_id))
                        <div class="form-text text-warning">No program categories available for selected campus.</div>
                        @elseif(empty($programCategories))
                        <div class="form-text text-muted">Please select a campus first.</div>
                        @endif
                    </div>
                </div>

                {{-- Program --}}
                <div class="col-md-12">
                    <div class="form-floating mb-3">
                        <select id="desired_program" class="form-select @error('desired_program') is-invalid @enderror" wire:model.live="desired_program" @if(empty($filteredPrograms)) disabled @endif>
                            <option value="" selected>Choose program</option>
                            @if(!empty($filteredPrograms))
                            @foreach($filteredPrograms as $id => $name)
                            <option value="{{ $name }}">{{ $name }}</option>
                            @endforeach
                            @endif
                        </select>
                        <label for="desired_program"><i class="fas fa-graduation-cap"></i> Program <span class="text-danger">*</span></label>
                        @error('desired_program') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @if(empty($filteredPrograms) && !empty($program_category))
                        <div class="form-text text-warning">No programs available for selected category.</div>
                        @elseif(empty($filteredPrograms))
                        <div class="form-text text-muted">Please select a program category first.</div>
                        @endif
                    </div>
                </div>

                {{-- Transferee Selection --}}
                <div class="col-md-12">
                    <div class="form-floating mb-3">
                        <select id="transferee" class="form-select @error('transferee') is-invalid @enderror" wire:model="transferee">
                            <option value="" selected>Select an option</option>
                            <option value="yes">Yes</option>
                            <option value="no">No</option>
                        </select>
                        <label for="transferee"><i class="fas fa-user-tag"></i> Transferee <span class="text-danger">*</span></label>
                        @error('transferee') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Hidden User ID field --}}
                <input type="hidden" id="user_id" wire:model="user_id">
                {{-- Hidden Applicant Number field --}}
                <input type="hidden" id="applicant_number" wire:model="applicant_number">

            </div> {{-- Closing row --}}
        </div> {{-- Closing card-body --}}
    </div> {{-- Closing card --}}
</div> {{-- Closing step-one --}}
@endif

{{-- STEP 2 --}}
@if ($currentStep == 2)
<div class="step-two">
    <div class="card shadow-lg border-0 rounded-3">
        <div class="card-header bg-primary text-white text-center fw-bold py-3">
            <h5 class="mb-0"><i class="fas fa-user"></i> STEP 2/6 - Personal Information</h5>
        </div>
        <div class="card-body">
            <p class="text-danger fw-semibold"><i class="fas fa-exclamation-circle"></i> All fields are required unless noted.</p>

            <div class="row">
                {{-- First Name --}}
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="text" id="first_name" class="form-control @error('first_name') is-invalid @enderror" wire:model="first_name">
                        <label for="first_name"><i class="fas fa-user"></i> First Name <span class="text-danger">*</span></label>
                        @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Last Name --}}
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="text" id="last_name" class="form-control @error('last_name') is-invalid @enderror" wire:model="last_name">
                        <label for="last_name"><i class="fas fa-user"></i> Last Name <span class="text-danger">*</span></label>
                        @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Middle Name --}}
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="text" id="middle_name" class="form-control @error('middle_name') is-invalid @enderror" wire:model="middle_name">
                        <label for="middle_name"><i class="fas fa-user"></i> Middle Name</label>
                        @error('middle_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Suffix --}}
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="text" id="suffix" class="form-control @error('suffix') is-invalid @enderror" wire:model="suffix">
                        <label for="suffix"><i class="fas fa-user-tag"></i> Suffix</label>
                        @error('suffix') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Date of Birth --}}
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="date" id="dateofbirth" class="form-control @error('dateofbirth') is-invalid @enderror"
                            min="{{ now()->subYears(100)->format('Y-m-d') }}"
                            max="{{ now()->subYears(17)->format('Y-m-d') }}"
                            wire:model="dateofbirth">
                        <label for="dateofbirth"><i class="fas fa-calendar-alt"></i> Date of Birth <span class="text-danger">*</span></label>
                        @error('dateofbirth') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Sex --}}
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <select id="sex" class="form-select @error('sex') is-invalid @enderror" wire:model="sex">
                            <option value="">Select Sex</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                        <label for="sex"><i class="fas fa-venus-mars"></i> Sex <span class="text-danger">*</span></label>
                        @error('sex') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- STEP 3 --}}
@if ($currentStep == 3)
<div class="step-three">
    <div class="card shadow-lg border-0 rounded-3">
        <div class="card-header bg-primary text-white text-center fw-bold py-3">
            <h5 class="mb-0"><i class="fas fa-map-marker-alt"></i> STEP 3/6 - Address & Contact Information</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> All fields are required unless noted.
            </div>

            <div class="row g-3">
                {{-- Address --}}
                <div class="col-12">
                    <div class="form-floating mb-3">
                        <input type="text" id="address" class="form-control @error('address') is-invalid @enderror" wire:model="address">
                        <label for="address"><i class="fas fa-map-marker-alt"></i> Address <span class="text-danger">*</span></label>
                        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Province --}}
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <select id="province_id" wire:model.live="province_id" wire:change="onProvinceChange" class="form-select @error('province_id') is-invalid @enderror">
                            <option value="">Select Province</option>
                            @foreach($provinces as $province)
                            <option value="{{ $province->id }}">{{ $province->name }}</option>
                            @endforeach
                        </select>
                        <label for="province_id"><i class="fas fa-map"></i> Province <span class="text-danger">*</span></label>
                        @error('province_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- City --}}
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <select id="city_id" wire:model.live="city_id" wire:change="onCityChange" class="form-select @error('city_id') is-invalid @enderror" @if(!$province_id) disabled @endif>
                            <option value="">Select City</option>
                            @foreach($cities as $city)
                            <option value="{{ $city->id }}">{{ $city->name }}</option>
                            @endforeach
                        </select>
                        <label for="city_id"><i class="fas fa-city"></i> City <span class="text-danger">*</span></label>
                        @error('city_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Barangay --}}
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <select id="barangay_id" wire:model.live="barangay_id" wire:change="onBarangayChange" class="form-select @error('barangay_id') is-invalid @enderror" @if(!$city_id) disabled @endif>
                            <option value="">Select Barangay</option>
                            @foreach($barangays as $barangay)
                            <option value="{{ $barangay->id }}">{{ $barangay->name }}</option>
                            @endforeach
                        </select>
                        <label for="barangay_id"><i class="fas fa-home"></i> Barangay <span class="text-danger">*</span></label>
                        @error('barangay_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Zip Code --}}
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="text" id="zip" class="form-control @error('zip') is-invalid @enderror" wire:model="zip" readonly>
                        <label for="zip"><i class="fas fa-envelope"></i> Zip / Postal Code <span class="text-danger">*</span></label>
                        @error('zip') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-text {{ empty($zip) ? 'text-muted' : 'text-success' }} mb-3 small">
                        Will be auto-generated based on location
                    </div>
                </div>

                {{-- Mobile Number --}}
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="text" id="mobile" class="form-control @error('mobile') is-invalid @enderror" wire:model="mobile">
                        <label for="mobile"><i class="fas fa-mobile-alt"></i> Mobile Number</label>
                        @error('mobile') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Landline --}}
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="text" id="landline" class="form-control" wire:model="landline">
                        <label for="landline"><i class="fas fa-phone"></i> Landline</label>
                    </div>
                </div>

                {{-- Email --}}
                <div class="col-12">
                    <div class="form-floating mb-3">
                        <input type="email" id="email" class="form-control @error('email') is-invalid @enderror" wire:model="email">
                        <label for="email"><i class="fas fa-envelope"></i> Email <span class="text-danger">*</span></label>
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- STEP 4 --}}
@if ($currentStep == 4)
<div class="step-four">
    <div class="card shadow-lg border-0 rounded-3">
        <div class="card-header bg-primary text-white text-center fw-bold py-3">
            <h5 class="mb-0"><i class="fas fa-users"></i> STEP 4/6 - Parent & Guardian Information</h5>
        </div>
        <div class="card-body">
            <p class="text-danger fw-semibold"><i class="fas fa-exclamation-circle"></i> Guardian fields are required; parent information is optional.</p>

            <div class="row">
                {{-- Father Name --}}
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="text" id="father_name" class="form-control  @error('father_name') is-invalid @enderror"
                            placeholder="Enter Father name" wire:model="father_name">
                        <label for="father_name"><i class="fas fa-user"></i> Father Name</label>
                        @error('father_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Father Mobile Number --}}
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="text" id="father_mobile" class="form-control @error('father_mobile') is-invalid @enderror"
                            placeholder="Enter Mobile number" wire:model="father_mobile">
                        <label for="father_mobile"><i class="fas fa-mobile-alt"></i> Father Mobile Number</label>
                        @error('father_mobile') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Mother Name --}}
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="text" id="mother_name" class="form-control @error('mother_name') is-invalid @enderror"
                            placeholder="Enter Mother name" wire:model="mother_name">
                        <label for="mother_name"><i class="fas fa-user"></i> Mother Name</label>
                        @error('mother_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Mother Mobile Number --}}
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="text" id="mother_mobile" class="form-control @error('mother_mobile') is-invalid @enderror"
                            placeholder="Enter Mobile number" wire:model="mother_mobile">
                        <label for="mother_mobile"><i class="fas fa-mobile-alt"></i> Mother Mobile Number</label>
                        @error('mother_mobile') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="col-12">
                    <hr class="my-3">
                    <h6 class="fw-bold"><i class="fas fa-user-shield"></i> Guardian Information <span class="text-danger">*</span></h6>
                </div>

                {{-- Guardian Name --}}
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="text" id="guardian_name" class="form-control @error('guardian_name') is-invalid @enderror"
                            placeholder="Enter Guardian name" wire:model="guardian_name">
                        <label for="guardian_name"><i class="fas fa-user-shield"></i> Guardian Name <span class="text-danger">*</span></label>
                        @error('guardian_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Guardian Mobile Number --}}
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="text" id="guardian_mobile" class="form-control @error('guardian_mobile') is-invalid @enderror"
                            placeholder="Enter Mobile number" wire:model="guardian_mobile">
                        <label for="guardian_mobile"><i class="fas fa-mobile-alt"></i> Guardian Mobile Number <span class="text-danger">*</span></label>
                        @error('guardian_mobile') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Guardian Address --}}
                <div class="col-md-12">
                    <div class="form-floating mb-3">
                        <input type="text" id="guardian_address" class="form-control @error('guardian_address') is-invalid @enderror"
                            placeholder="Enter Guardian address" wire:model="guardian_address">
                        <label for="guardian_address"><i class="fas fa-map-marker-alt"></i> Guardian Address <span class="text-danger">*</span></label>
                        @error('guardian_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endif

{{-- STEP 5 --}}
@if ($currentStep == 5)
<div class="step-five">
    <div class="card shadow-lg border-0 rounded-3">
        <div class="card-header bg-primary text-white text-center fw-bold py-3">
            <h5 class="mb-0"><i class="fas fa-graduation-cap"></i> STEP 5/6 - Educational Background</h5>
        </div>
        <div class="card-body">
            <p class="text-danger fw-semibold"><i class="fas fa-exclamation-circle"></i> All fields are required unless noted.</p>

            <div class="row">
                {{-- School Year --}}
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="number" id="school_year"
                            class="form-control @error('school_year') is-invalid @enderror"
                            wire:model="school_year"
                            min="{{ date('Y')-10 }}"
                            max="{{ date('Y') }}"
                            placeholder="Enter last school year attended">
                        <label for="school_year"><i class="fas fa-calendar-alt"></i> Last School Year Attended <span class="text-danger">*</span></label>
                        @error('school_year') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- School Type --}}
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <select id="school_type" class="form-select @error('school_type') is-invalid @enderror" wire:model="school_type">
                            <option value="">Select School Type</option>
                            <option value="Public">Public</option>
                            <option value="Private">Private</option>
                        </select>
                        <label for="school_type"><i class="fas fa-school"></i> School Type <span class="text-danger">*</span></label>
                        @error('school_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- School Name --}}
                <div class="col-md-12">
                    <div class="form-floating mb-3">
                        <input type="text" id="school_name" class="form-control @error('school_name') is-invalid @enderror" wire:model="school_name">
                        <label for="school_name"><i class="fas fa-school"></i> School Name <span class="text-danger">*</span></label>
                        @error('school_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- School Address --}}
                <div class="col-md-12">
                    <div class="form-floating mb-3">
                        <input type="text" id="school_address" class="form-control @error('school_address') is-invalid @enderror" wire:model="school_address">
                        <label for="school_address"><i class="fas fa-map-marker-alt"></i> School Address <span class="text-danger">*</span></label>
                        @error('school_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Strand --}}
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <select id="strand" class="form-select @error('strand') is-invalid @enderror" wire:model="strand">
                            <option value="" selected>Choose Strand</option>
                            <option value="ABM">Accountancy, Business, and Management (ABM)</option>
                            <option value="STEM">Science, Technology, Engineering, and Mathematics (STEM)</option>
                            <option value="HUMSS">Humanities and Social Sciences (HUMSS)</option>
                            <option value="GAS">General Academic Strand (GAS)</option>
                            <option value="TVL">Technical Vocational Livelihood (TVL)</option>
                            <option value="ICT">Information and Communication Technology (ICT)</option>
                        </select>
                        <label for="strand"><i class="fas fa-book-open"></i> Strand <span class="text-danger">*</span></label>
                        @error('strand') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Grade / Ratings --}}
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="text" id="grade" class="form-control @error('grade') is-invalid @enderror" wire:model="grade">
                        <label for="grade"><i class="fas fa-graduation-cap"></i> Grade / Ratings <span class="text-danger">*</span></label>
                        @error('grade') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Application Status (Hidden, defaults to 'pending') --}}
                <input type="hidden" id="status" wire:model="status" value="pending">

            </div>
        </div>
    </div>
</div>
@endif

{{-- STEP 6 --}}
@if ($currentStep == 6)
<div class="step-six">
    <div class="card shadow-lg border-0 rounded-3">
        <div class="card-header bg-primary text-white text-center fw-bold py-3">
            <h5 class="mb-0"><i class="fas fa-upload"></i> STEP 6/6 - Upload Required Documents</h5>
        </div>
        <div class="card-body">
            <p class="text-danger fw-semibold"><i class="fas fa-exclamation-circle"></i> All documents are required and must be in image or PDF format.</p>

            <!-- Terms & Conditions Section -->
            <div class="form-group border p-3 rounded bg-light mb-4">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-file-contract fa-lg me-2 text-primary"></i>
                    <h6 class="fw-bold mb-0">Terms & Conditions</h6>
                </div>
                <p class="text-muted mb-0 small">
                    By submitting this form, you agree that all the information provided will be used solely for educational purposes within our school management system. Your data is kept secure and confidential. We do not share your personal information with third parties without explicit consent, except as required by law.
                </p>
            </div>

            <!-- File Upload Fields -->
            <div class="row">
                <!-- 2x2 Formal Picture -->
                <div class="col-md-12">
                    <div class="mb-4">
                        <label for="doc1" class="form-label fw-semibold">
                            <i class="fas fa-id-card text-primary"></i> <span class="text-danger">*</span> 2x2 Formal Picture
                        </label>
                        <input type="file" name="doc1" id="doc1" wire:model="doc1"
                            class="form-control @error('doc1') is-invalid @enderror">
                        <div class="form-text">Acceptable formats: JPEG, PNG, JPG, GIF, SVG, PDF. Max size: 6MB</div>
                        @error('doc1')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Report Card (Front & Back) -->
                <div class="col-md-12">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-file-alt text-primary"></i> <span class="text-danger">*</span> Report Card / TOR / COG
                        </label>
                    </div>
                </div>

                <!-- Report Card - Front -->
                <div class="col-md-6">
                    <div class="mb-4">
                        <label for="doc2_front" class="form-label">
                            <i class="fas fa-file-image me-1"></i> Front Page
                        </label>
                        <input type="file" name="doc2[0]" id="doc2_front" wire:model="doc2.0"
                            class="form-control @error('doc2.0') is-invalid @enderror">
                        <div class="form-text">Acceptable formats: JPEG, PNG, JPG, GIF, SVG, PDF. Max size: 6MB</div>
                        @error('doc2.0')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Report Card - Back -->
                <div class="col-md-6">
                    <div class="mb-4">
                        <label for="doc2_back" class="form-label">
                            <i class="fas fa-file-image me-1"></i> Back Page
                        </label>
                        <input type="file" name="doc2[1]" id="doc2_back" wire:model="doc2.1"
                            class="form-control @error('doc2.1') is-invalid @enderror">
                        <div class="form-text">Acceptable formats: JPEG, PNG, JPG, GIF, SVG, PDF. Max size: 6MB</div>
                        @error('doc2.1')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- PSA Certificate -->
                <div class="col-md-12">
                    <div class="mb-4">
                        <label for="doc3" class="form-label fw-semibold">
                            <i class="fas fa-file-pdf text-primary"></i> <span class="text-danger">*</span> PSA Certificate of Live Birth
                        </label>
                        <input type="file" name="doc3" id="doc3" wire:model="doc3"
                            class="form-control @error('doc3') is-invalid @enderror">
                        <div class="form-text">Acceptable formats: JPEG, PNG, JPG, GIF, SVG, PDF. Max size: 6MB</div>
                        @error('doc3')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Terms & Conditions Checkbox -->
            <div class="form-group border p-3 rounded bg-light mt-2">
                <div class="form-check">
                    <input type="checkbox" name="terms" id="terms" wire:model.live="terms" class="form-check-input @error('terms') is-invalid @enderror">
                    <label for="terms" class="form-check-label">
                        I have read and agree to the <a href="#" class="text-primary fw-bold">Terms and Conditions</a>
                    </label>
                    @error('terms')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Navigation Buttons --}}
<div class="d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm">
    {{-- Back Button --}}
    @if ($currentStep > 1)
    <button type="button" class="btn btn-secondary px-4 fw-bold" wire:click="decreaseStep()">
        <i class="fas fa-arrow-left"></i> Back
    </button>
    @else
    <div></div>
    @endif

    {{-- Next & Submit Buttons --}}
    @if ($currentStep < 6)
        <button type="button" class="btn btn-success px-4 fw-bold" wire:click="increaseStep()">
        Next <i class="fas fa-arrow-right"></i>
        </button>
        @elseif ($currentStep == 6)
        <button type="submit" class="btn btn-primary px-4 fw-bold" @if(!$terms) disabled @endif>
            <i class="fas fa-check-circle"></i> Submit Application
        </button>
        @endif
</div>
</form>

<script>
    document.addEventListener('livewire:load', function() {
        // Initialize Bootstrap tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })

        // Reinitialize tooltips after Livewire updates
        window.addEventListener('livewire:update', function() {
            tooltipList.forEach(tooltip => tooltip.dispose());
            tooltipList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
                .map(function(tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl)
                });
        });
        
        // Handle terms checkbox changes
        document.getElementById('terms').addEventListener('change', function() {
            const submitBtn = document.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = !this.checked;
            }
        });
    });
</script>
</div>