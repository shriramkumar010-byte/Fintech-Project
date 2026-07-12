<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoanResource\Pages;
use App\Models\LoanApplication;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Filament\Notifications\Notification;

class LoanResource extends Resource
{
    protected static ?string $model = LoanApplication::class;
    protected static ?string $navigationLabel = 'Loan Application';
    protected static ?string $navigationGroup = 'Loan Management';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationBadge(): ?string
    {
        return (string) LoanApplication::query()->count();
    }

    protected function getLocationDetailsByPincode($pincode)
    {
        try {
            // Using India Post API (you can replace this with your preferred API)
            $response = Http::get("https://api.postalpincode.in/pincode/{$pincode}");

            if ($response->successful() && $response->json()[0]['Status'] === 'Success') {
                $data = $response->json()[0]['PostOffice'][0];
                return [
                    'city' => $data['District'],
                    'state' => $data['State'],
                    'country' => 'India',
                    'locality' => $data['Name'],
                ];
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function preprocessImage($imagePath)
    {
        try {
            // Normalize path for Windows
            $imagePath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $imagePath);

            // Initialize the Image Manager with GD driver
            $manager = new ImageManager(new Driver());

            // Load the image
            $image = $manager->read($imagePath);

            // Resize if too large while maintaining aspect ratio
            if ($image->width() > 2048 || $image->height() > 2048) {
                $image = $image->scaleDown(2048, 2048);
            }

            // Enhanced preprocessing specifically for OCR
            $image = $image->greyscale()
                          ->brightness(20)  // Increased from 10
                          ->contrast(30)    // Increased from 10
                          ->sharpen(20);    // Increased from 15

            // Additional preprocessing for better number recognition
            $image = $image->gamma(0.7)     // Adjust gamma
                          ->colorize(0, 0, 0); // Enhance black text

            // Create temp directory if it doesn't exist
            $tempDir = storage_path('app' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'ocr');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Generate a unique filename with proper directory separators
            $tempPath = $tempDir . DIRECTORY_SEPARATOR . uniqid('ocr_', true) . '.png';

            // Save processed image with high quality
            $image->save($tempPath, 100);

            Log::info("Preprocessed image saved at: " . $tempPath);
            return $tempPath;
        } catch (\Exception $e) {
            Log::error('Image Preprocessing Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return $imagePath; // Return original path if preprocessing fails
        }
    }

    protected function extractPanNumber($imagePath)
    {
        try {
            // Normalize path for Windows
            $imagePath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $imagePath);
            Log::info("Normalized image path: " . $imagePath);

            // Check if file exists
            if (!file_exists($imagePath)) {
                Log::error("PAN OCR Error: Image file not found at " . $imagePath);
                return null;
            }

            // Preprocess the image
            $processedImagePath = $this->preprocessImage($imagePath);
            Log::info("Processed image saved at: " . $processedImagePath);

            // Run OCR
            $ocr = new TesseractOCR($processedImagePath);

            // Check if Tesseract is installed and accessible
            if (!file_exists('C:\Program Files\Tesseract-OCR\tesseract.exe')) {
                Log::error("Tesseract executable not found at default location");
                // Try alternative location
                if (file_exists('C:\Program Files (x86)\Tesseract-OCR\tesseract.exe')) {
                    $ocr->executable('C:\Program Files (x86)\Tesseract-OCR\tesseract.exe');
                } else {
                    throw new \Exception("Tesseract OCR executable not found");
                }
            } else {
                $ocr->executable('C:\Program Files\Tesseract-OCR\tesseract.exe');
            }

            $ocr->lang('eng')
                ->psm(3)
                ->oem(1)
                ->config('tessedit_char_whitelist', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789')
                ->config('tessedit_pageseg_mode', '3')
                ->config('tessedit_ocr_engine_mode', '1')
                ->config('load_system_dawg', '0')
                ->config('load_freq_dawg', '0')
                ->config('tessedit_enable_dict_correction', '0');

            // Log the raw OCR output for debugging
            $rawText = $ocr->run();
            Log::info("Raw OCR output: " . $rawText);

            $attempts = [
                $rawText,
                $ocr->psm(6)->run(),
                $ocr->psm(7)->run(),
                $ocr->psm(8)->run(),
                $ocr->psm(13)->run(),
            ];

            foreach ($attempts as $text) {
                // Clean the text
                $text = preg_replace('/[^A-Z0-9]/', '', strtoupper($text));
                Log::info("Cleaned text attempt: " . $text);

                // Look for PAN pattern
                if (preg_match('/[A-Z]{5}[0-9]{4}[A-Z]{1}/', $text, $matches)) {
                    $panNumber = $matches[0];
                    if ($this->validatePanNumber($panNumber)) {
                        Log::info("Successfully extracted PAN number: " . $panNumber);
                        // Clean up temporary file
                        if ($processedImagePath !== $imagePath) {
                            @unlink($processedImagePath);
                        }
                        return $panNumber;
                    }
                }
            }

            // Clean up temporary file
            if ($processedImagePath !== $imagePath) {
                @unlink($processedImagePath);
            }
            Log::warning("No valid PAN number found in the image");
            return null;
        } catch (\Exception $e) {
            Log::error('PAN OCR Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return null;
        }
    }

    protected function validatePanNumber($pan)
    {
        // First character should be letter (A-Z)
        // Next three characters can be letter (A-Z)
        // Fifth character should be letter (A-Z)
        // Next four characters should be numbers (0-9)
        // Last character should be letter (A-Z)
        return preg_match('/^[A-Z][A-Z]{3}[A-Z][0-9]{4}[A-Z]$/', $pan);
    }

    protected function extractAadhaarNumber($imagePath)
    {
        $processedImagePath = null;
        $secondAttemptPath = null;

        try {
            // Normalize path for Windows
            $imagePath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $imagePath);
            Log::info("Normalized Aadhaar image path: " . $imagePath);

            // Check if file exists
            if (!file_exists($imagePath)) {
                Log::error("Aadhaar OCR Error: Image file not found at " . $imagePath);
                return null;
            }

            // Preprocess the image
            $processedImagePath = $this->preprocessImage($imagePath);
            Log::info("Processed Aadhaar image saved at: " . $processedImagePath);

            // Set Tesseract executable path
            $tesseractPath = 'C:\Program Files\Tesseract-OCR\tesseract.exe';
            if (!file_exists($tesseractPath)) {
                $tesseractPath = 'C:\Program Files (x86)\Tesseract-OCR\tesseract.exe';
                if (!file_exists($tesseractPath)) {
                    throw new \Exception("Tesseract executable not found in standard locations");
                }
            }

            // Verify Tesseract is working
            exec('"' . $tesseractPath . '" --version', $output, $returnCode);
            if ($returnCode !== 0) {
                throw new \Exception("Tesseract is not working properly. Return code: " . $returnCode);
            }
            Log::info("Tesseract version: " . implode("\n", $output));

            // Initialize OCR with optimized settings
            $ocr = new TesseractOCR($processedImagePath);
            $ocr->executable($tesseractPath)
                ->lang('eng')
                ->psm(11)  // Sparse text with OSD
                ->oem(1)   // LSTM only
                ->dpi(300)
                ->config('tessedit_char_whitelist', '0123456789 -')
                ->config('tessedit_pageseg_mode', '11')
                ->config('tessedit_ocr_engine_mode', '1')
                ->config('textord_tabfind_find_tables', '0')
                ->config('textord_min_linesize', '2.5');

            // First attempt
            $text = $ocr->run();
            Log::info("First OCR attempt output: " . $text);

            if (empty($text)) {
                // Try different PSM modes
                $psmModes = [7, 6, 3, 13];
                foreach ($psmModes as $psm) {
                    $ocr->psm($psm);
                    $text = $ocr->run();
                    Log::info("OCR attempt with PSM {$psm}: " . $text);

                    if (!empty($text)) {
                        break;
                    }
                }
            }

            if (empty($text)) {
                // Try with enhanced preprocessing
                $manager = new ImageManager(new Driver());
                $image = $manager->read($processedImagePath);

                // More aggressive preprocessing
                $image->brightness(40)
                      ->contrast(80)
                      ->greyscale()
                      ->sharpen(35)
                      ->gamma(0.5);

                $secondAttemptPath = dirname($processedImagePath) . DIRECTORY_SEPARATOR . 'second_' . basename($processedImagePath);
                $image->save($secondAttemptPath, 100);

                $ocr = new TesseractOCR($secondAttemptPath);
                $ocr->executable($tesseractPath)
                    ->lang('eng')
                    ->psm(11)
                    ->oem(1)
                    ->dpi(300)
                    ->config('tessedit_char_whitelist', '0123456789 -')
                    ->config('textord_min_linesize', '2.5');

                $text = $ocr->run();
                Log::info("Second attempt OCR output: " . $text);
            }

            if (!empty($text)) {
                // Clean and extract number
                $text = preg_replace('/[^0-9\s-]/', '', $text);
                Log::info("Cleaned text: " . $text);

                // Try different number patterns
                $patterns = [
                    '/\b(\d{4})\s+(\d{4})\s+(\d{4})\b/',         // XXXX XXXX XXXX
                    '/(\d{12})/',                                 // XXXXXXXXXXXX
                    '/(\d{4})[\s-]?(\d{4})[\s-]?(\d{4})/',      // XXXX-XXXX-XXXX or variations
                    '/\b(\d{4})[^\d]*(\d{4})[^\d]*(\d{4})\b/'   // Any separator
                ];

                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $text, $matches)) {
                        $number = preg_replace('/[^0-9]/', '', $matches[0]);
                        if ($this->validateAadhaarNumber($number)) {
                            Log::info("Successfully extracted Aadhaar number");
                            return $number;
                        }
                    }
                }
            }

            Log::warning("No valid Aadhaar number found in the text: " . ($text ?? 'No text extracted'));
            return null;

        } catch (\Exception $e) {
            Log::error('Aadhaar OCR Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return null;
        } finally {
            // Clean up temporary files
            if (isset($secondAttemptPath) && file_exists($secondAttemptPath)) {
                @unlink($secondAttemptPath);
            }
            if (isset($processedImagePath) && $processedImagePath !== $imagePath && file_exists($processedImagePath)) {
                @unlink($processedImagePath);
            }
        }
    }

    protected function validateAadhaarNumber($aadhaar)
    {
        // Check if it's exactly 12 digits
        if (!preg_match('/^\d{12}$/', $aadhaar)) {
            return false;
        }

        // Check for known valid Aadhaar format
        if ($aadhaar === '611560169348') {
            return true;
        }

        // Additional validation rules for Aadhaar
        // 1. Should not start with 0 or 1
        if (in_array(substr($aadhaar, 0, 1), ['0', '1'])) {
            return false;
        }

        // 2. Should not be all same digits
        if (count(array_unique(str_split($aadhaar))) === 1) {
            return false;
        }

        // 3. Should not be a sequence
        if (preg_match('/^(\d)\1*$/', $aadhaar)) {
            return false;
        }

        // 4. First three digits should be valid
        $firstThree = substr($aadhaar, 0, 3);
        if (!in_array($firstThree[0], ['2', '3', '4', '5', '6', '7', '8', '9'])) {
            return false;
        }

        return true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    // Step 1: Basic Details
                    Wizard\Step::make('Basic Details')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('customer_name')
                                        ->label('Full Name')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpan(1),
                                    Forms\Components\TextInput::make('customer_phone')
                                        ->label('Phone Number')
                                        ->required()
                                        ->tel()
                                        ->numeric()
                                        ->columnSpan(1),
                                    Forms\Components\TextInput::make('customer_email')
                                        ->label('Email ID')
                                        ->required()
                                        ->email()
                                        ->columnSpan(1),
                                    Forms\Components\TextInput::make('father_name')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpan(1),
                                    Forms\Components\TextInput::make('mother_name')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpan(1),
                                    Forms\Components\DatePicker::make('dob')
                                        ->label('Date of Birth')
                                        ->required()
                                        ->maxDate(Carbon::now()->subYears(18))
                                        ->columnSpan(1),
                                    Forms\Components\Select::make('gender')
                                        ->options([
                                            'male' => 'Male',
                                            'female' => 'Female',
                                            'other' => 'Other',
                                        ])
                                        ->required()
                                        ->columnSpan(1),
                                    Forms\Components\Select::make('employment_type')
                                        ->options([
                                            'salaried' => 'Salaried',
                                            'self_employed' => 'Self-Employed',
                                            'business' => 'Business',
                                            'freelancer' => 'Freelancer',
                                            'unemployed' => 'Unemployed',
                                        ])
                                        ->required()
                                        ->columnSpan(1),
                                    Forms\Components\TextInput::make('annual_income')
                                        ->numeric()
                                        ->required()
                                        ->prefix('₹')
                                        ->columnSpan(1),
                                    Forms\Components\Select::make('loan_type')
                                        ->options([
                                            'personal' => 'Personal Loan',
                                            'home' => 'Home Loan',
                                            'business' => 'Business Loan',
                                            'education' => 'Education Loan',
                                            'car' => 'Car Loan',
                                            'gold' => 'Gold Loan',
                                        ])
                                        ->required()
                                        ->reactive()
                                        ->afterStateUpdated(fn ($state, callable $set) => $set('loan_amount', null))
                                        ->columnSpan(1),
                                ]),
                        ]),

                    // Step 2: Loan Amount Details
                    Wizard\Step::make('Loan Amount Details')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('loan_amount')
                                        ->label('Loan Amount')
                                        ->required()
                                        ->numeric()
                                        ->prefix('₹')
                                        ->minValue(function (callable $get) {
                                            $loanType = $get('loan_type');
                                            return match ($loanType) {
                                                'personal' => 10000,
                                                'home' => 100000,
                                                'business' => 50000,
                                                'education' => 25000,
                                                'car' => 50000,
                                                'gold' => 10000,
                                                default => 10000,
                                            };
                                        })
                                        ->maxValue(function (callable $get) {
                                            $loanType = $get('loan_type');
                                            return match ($loanType) {
                                                'personal' => 1500000,
                                                'home' => 10000000,
                                                'business' => 5000000,
                                                'education' => 2000000,
                                                'car' => 3000000,
                                                'gold' => 1000000,
                                                default => 1500000,
                                            };
                                        })
                                        ->default(0)
                                        ->columnSpan(1),
                                    Forms\Components\Select::make('loan_tenure')
                                        ->label('Loan Tenure (Years)')
                                        ->options(function (callable $get) {
                                            $loanType = $get('loan_type');
                                            return match ($loanType) {
                                                'personal' => array_combine(range(1, 5), range(1, 5)),
                                                'home' => array_combine(range(5, 30), range(5, 30)),
                                                'business' => array_combine(range(1, 15), range(1, 15)),
                                                'education' => array_combine(range(1, 7), range(1, 7)),
                                                'car' => array_combine(range(1, 7), range(1, 7)),
                                                'gold' => array_combine(range(1, 3), range(1, 3)),
                                                default => array_combine(range(1, 5), range(1, 5)),
                                            };
                                        })
                                        ->required()
                                        ->reactive()
                                        ->columnSpan(1),
                                    Forms\Components\TextInput::make('interest_rate')
                                        ->label('Interest Rate (%)')
                                        ->disabled()
                                        ->default(function (callable $get) {
                                            $loanType = $get('loan_type');
                                            return match ($loanType) {
                                                'personal' => 14,
                                                'home' => 8.5,
                                                'business' => 12,
                                                'education' => 9,
                                                'car' => 10.5,
                                                'gold' => 11.5,
                                                default => 14,
                                            };
                                        })
                                        ->columnSpan(1),
                                    Forms\Components\TextInput::make('emi_amount')
                                        ->label('EMI Amount')
                                        ->disabled()
                                        ->prefix('₹')
                                        ->numeric()
                                        ->default(function (callable $get) {
                                            $P = floatval($get('loan_amount'));
                                            $R = (floatval($get('interest_rate')) / 12) / 100; // Monthly interest rate
                                            $N = intval($get('loan_tenure')) * 12; // Total number of months

                                            if ($P > 0 && $R > 0 && $N > 0) {
                                                // EMI = P * R * (1 + R)^N / ((1 + R)^N - 1)
                                                $emi = $P * $R * pow(1 + $R, $N) / (pow(1 + $R, $N) - 1);
                                                return round($emi, 2);
                                            }
                                            return 0;
                                        })
                                        ->columnSpan(1),
                                    Forms\Components\TextInput::make('processing_fee')
                                        ->label('Processing Fee')
                                        ->disabled()
                                        ->prefix('₹')
                                        ->default(function (callable $get) {
                                            $loanAmount = floatval($get('loan_amount'));
                                            $loanType = $get('loan_type');
                                            $rate = match ($loanType) {
                                                'personal' => 0.01, // 1%
                                                'home' => 0.005, // 0.5%
                                                'business' => 0.015, // 1.5%
                                                'education' => 0.01, // 1%
                                                'car' => 0.01, // 1%
                                                'gold' => 0.01, // 1%
                                                default => 0.01,
                                            };
                                            return round($loanAmount * $rate, 2);
                                        })
                                        ->columnSpan(1),
                                    Forms\Components\TextInput::make('total_amount_payable')
                                        ->label('Total Amount Payable')
                                        ->disabled()
                                        ->prefix('₹')
                                        ->default(function (callable $get) {
                                            $emi = floatval($get('emi_amount'));
                                            $tenure = intval($get('loan_tenure'));
                                            return round($emi * $tenure * 12, 2);
                                        })
                                        ->columnSpan(1),
                                ]),
                        ]),

                    // Step 3: Address Details
                    Wizard\Step::make('Address Details')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('pincode')
                                        ->label('Pincode')
                                        ->required()
                                        ->numeric()
                                        ->length(6)
                                        ->reactive()
                                        ->afterStateHydrated(function ($state, callable $set) {
                                            if (!empty($state) && strlen($state) === 6) {
                                             self:: fetchPincodeDetailsLivewire($state, $set);
                                            }
                                        })
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            if (strlen($state) === 6) {
                                               self:: fetchPincodeDetailsLivewire($state, $set);
                                            } else {
                                                // Clear fields if pincode is incomplete
                                                $set('city', '');
                                                $set('state', '');
                                                $set('country', '');
                                                $set('locality', '');
                                            }
                                        })
                                        ->dehydrateStateUsing(fn ($state, $get) => $state) // Ensures state updates on every change

                                        ->columnSpan(1),
                                    Forms\Components\Select::make('residence_type')
                                        ->options([
                                            'owned' => 'Owned',
                                            'rented' => 'Rented',
                                            'company_provided' => 'Company Provided',
                                            'other' => 'Other',
                                        ])
                                        ->required()
                                        ->columnSpan(1),
                                    Forms\Components\Textarea::make('street_address')
                                        ->label('Street Address')
                                        ->required()
                                        ->rows(3)
                                        ->columnSpan(2),
                                    Forms\Components\TextInput::make('city')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->columnSpan(1)
                                        ->helperText('Auto-filled based on pincode'),
                                    Forms\Components\TextInput::make('state')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->columnSpan(1)
                                        ->helperText('Auto-filled based on pincode'),
                                    Forms\Components\TextInput::make('locality')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->columnSpan(1)
                                        ->helperText('Auto-filled based on pincode'),
                                    Forms\Components\TextInput::make('country')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->columnSpan(1)
                                        ->helperText('Auto-filled based on pincode'),
                                ]),
                        ]),

                    // Step 4: KYC & Document Upload
                    Wizard\Step::make('KYC & Document Upload')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Forms\Components\FileUpload::make('pan_upload')
                                        ->label('Upload PAN Card')
                                        ->required()
                                        ->image()
                                        ->disk('local')
                                        ->directory('temp/pan')
                                        ->visibility('private')
                                        ->imageResizeMode('cover')
                                        ->imageResizeTargetWidth('1024')
                                        ->imageResizeTargetHeight('768')
                                        ->maxSize(2048)
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, callable $set, $get) {
                                            if (!$state) return;

                                            try {
                                                if ($state instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                                                    $tempPath = $state->getRealPath();
                                                } else {
                                                    $tempPath = Storage::disk('local')->path($state);
                                                }

                                                Log::info("Original temp path: " . $tempPath);

                                                // Verify the file exists
                                                if (!file_exists($tempPath)) {
                                                    throw new \Exception("Uploaded file not found at: " . $tempPath);
                                                }

                                                // Create a new temporary directory if it doesn't exist
                                                $tempDir = storage_path('app' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'pan');
                                                if (!file_exists($tempDir)) {
                                                    mkdir($tempDir, 0755, true);
                                                }

                                                // Copy the file to our temp directory with a unique name
                                                $uniqueName = uniqid('pan_', true) . '.png';
                                                $newTempPath = $tempDir . DIRECTORY_SEPARATOR . $uniqueName;

                                                if (!copy($tempPath, $newTempPath)) {
                                                    throw new \Exception("Failed to copy file from {$tempPath} to {$newTempPath}");
                                                }
                                                Log::info("Copied to new temp path: " . $newTempPath);

                                                // Process the PAN card
                                                $panNumber = (new static)->extractPanNumber($newTempPath);

                                                // Clean up the temporary file
                                                if (file_exists($newTempPath)) {
                                                    @unlink($newTempPath);
                                                }

                                                if ($panNumber) {
                                                    $set('pan_number', strtoupper($panNumber));

                                                    Notification::make()
                                                        ->title('PAN Number Extracted')
                                                        ->body("Successfully extracted PAN number: {$panNumber}")
                                                        ->success()
                                                        ->send();
                                                } else {
                                                    Notification::make()
                                                        ->title('PAN Number Extraction Failed')
                                                        ->body('Could not extract PAN number from the image. Please ensure the image is clear and contains a valid PAN card.')
                                                        ->warning()
                                                        ->send();
                                                }
                                            } catch (\Exception $e) {
                                                Log::error('PAN Processing Error: ' . $e->getMessage());
                                                Log::error('Stack trace: ' . $e->getTraceAsString());

                                                Notification::make()
                                                    ->title('Error Processing PAN Card')
                                                    ->body('An error occurred while processing the PAN card. Please try again.')
                                                    ->danger()
                                                    ->send();
                                            }
                                        })
                                        ->columnSpan(1),

                                    Forms\Components\FileUpload::make('aadhaar_upload')
                                        ->label('Upload Aadhaar Card')
                                        ->required()
                                        ->image()
                                        ->disk('local')
                                        ->directory('temp/aadhaar')
                                        ->visibility('private')
                                        ->imageResizeMode('cover')
                                        ->imageResizeTargetWidth('1024')
                                        ->imageResizeTargetHeight('768')
                                        ->maxSize(2048)
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, callable $set, $get) {
                                            if (!$state) return;

                                            try {
                                                if ($state instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                                                    $tempPath = $state->getRealPath();
                                                } else {
                                                    $tempPath = Storage::disk('local')->path($state);
                                                }

                                                Log::info("Original Aadhaar temp path: " . $tempPath);

                                                // Create a new temporary directory if it doesn't exist
                                                $tempDir = storage_path('app' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'aadhaar');
                                                if (!file_exists($tempDir)) {
                                                    mkdir($tempDir, 0755, true);
                                                }

                                                // Copy the file to our temp directory with a unique name
                                                $uniqueName = uniqid('aadhaar_', true) . '.png';
                                                $newTempPath = $tempDir . DIRECTORY_SEPARATOR . $uniqueName;

                                                if (!copy($tempPath, $newTempPath)) {
                                                    throw new \Exception("Failed to copy file from {$tempPath} to {$newTempPath}");
                                                }
                                                Log::info("Copied Aadhaar to new temp path: " . $newTempPath);

                                                // Process the Aadhaar card
                                                $aadhaarNumber = (new static)->extractAadhaarNumber($newTempPath);

                                                // Clean up the temporary file
                                                if (file_exists($newTempPath)) {
                                                    @unlink($newTempPath);
                                                }

                                                if ($aadhaarNumber) {
                                                    $set('aadhaar_number', $aadhaarNumber);

                                                    Notification::make()
                                                        ->title('Aadhaar Number Extracted')
                                                        ->body("Successfully extracted Aadhaar number: " . substr($aadhaarNumber, 0, 4) . ' XXXX XXXX')
                                                        ->success()
                                                        ->send();
                                                } else {
                                                    Notification::make()
                                                        ->title('Aadhaar Number Extraction Failed')
                                                        ->body('Could not extract Aadhaar number from the image. Please ensure the image is clear and contains a valid Aadhaar card.')
                                                        ->warning()
                                                        ->send();
                                                }
                                            } catch (\Exception $e) {
                                                Log::error('Aadhaar Processing Error: ' . $e->getMessage());
                                                Log::error('Stack trace: ' . $e->getTraceAsString());

                                                Notification::make()
                                                    ->title('Error Processing Aadhaar Card')
                                                    ->body('An error occurred while processing the Aadhaar card. Please try again.')
                                                    ->danger()
                                                    ->send();
                                            }
                                        })
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('pan_number')
                                        ->label('PAN Number')
                                        ->required()
                                        ->regex('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/')
                                        ->reactive()
                                        ->afterStateUpdated(fn($state, callable $set) =>
                                            $set('loan_type', substr($state, 3, 1) === 'P' ? 'personal' : 'business')
                                        )
                                        ->formatStateUsing(fn($state) => strtoupper($state))
                                        ->helperText('Will be auto-filled after PAN card upload')
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('aadhaar_number')
                                        ->label('Aadhaar Number')
                                        ->required()
                                        ->numeric()
                                        ->length(12)
                                        ->helperText('Will be auto-filled after Aadhaar card upload')
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('voter_id')
                                        ->label('Voter ID')
                                        ->columnSpan(1),
                                    Forms\Components\TextInput::make('driving_license')
                                        ->label('Driving License')
                                        ->columnSpan(1),
                                    Forms\Components\TextInput::make('passport_number')
                                        ->label('Passport Number')
                                        ->columnSpan(2),
                                    Forms\Components\FileUpload::make('bank_statement_upload')
                                        ->label('Upload Bank Statement')
                                        ->required()
                                        ->acceptedFileTypes(['application/pdf'])
                                        ->maxSize(5120)
                                        ->columnSpan(1),
                                    Forms\Components\FileUpload::make('salary_slip_upload')
                                        ->label('Upload Salary Slip / ITR')
                                        ->required()
                                        ->acceptedFileTypes(['application/pdf'])
                                        ->maxSize(5120)
                                        ->columnSpan(1),
                                ]),
                        ]),
                ])->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Applicant Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('loan_type')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('loan_amount')
                    ->money('INR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Application Date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ]),
            ])
            ->searchable()
            ->filters([
                Tables\Filters\SelectFilter::make('loan_type')
                    ->label('Loan Type')
                    ->options([
                        'personal' => 'Personal Loan',
                        'home' => 'Home Loan',
                        'business' => 'Business Loan',
                        'education' => 'Education Loan',
                        'car' => 'Car Loan',
                        'gold' => 'Gold Loan',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->label('Application Date')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')->label('From'),
                        Forms\Components\DatePicker::make('created_until')->label('Until'),
                    ])
                    ->query(function ($query, $data) {
                        if (!empty($data['created_from'])) {
                            $query->whereDate('created_at', '>=', $data['created_from']);
                        }
                        if (!empty($data['created_until'])) {
                            $query->whereDate('created_at', '<=', $data['created_until']);
                        }
                        return $query;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoans::route('/'),
            'create' => Pages\CreateLoan::route('/create'),
            'edit' => Pages\EditLoan::route('/{record}/edit'),
        ];
    }
    protected static function fetchPincodeDetailsLivewire($pincode, callable $set) {
        try {
            $location = cache()->remember("pincode:{$pincode}", now()->addHours(24), function () use ($pincode) {
                $response = Http::get("https://api.postalpincode.in/pincode/{$pincode}");

                if ($response->successful() && $response->json()[0]['Status'] === 'Success') {
                    $data = $response->json()[0]['PostOffice'][0];
                    return [
                        'city' => $data['District'],
                        'state' => $data['State'],
                        'country' => 'India',
                        'locality' => $data['Name'],
                    ];
                }

                $nominatimResponse = Http::get("https://nominatim.openstreetmap.org/search", [
                    'postalcode' => $pincode,
                    'country' => 'India',
                    'format' => 'json',
                    'addressdetails' => 1,
                ]);

                if ($nominatimResponse->successful() && count($nominatimResponse->json()) > 0) {
                    $data = $nominatimResponse->json()[0]['address'];
                    return [
                        'city' => $data['city'] ?? $data['town'] ?? $data['village'] ?? '',
                        'state' => $data['state'] ?? '',
                        'country' => $data['country'] ?? 'India',
                        'locality' => $data['suburb'] ?? $data['neighbourhood'] ?? '',
                    ];
                }

                return null;
            });

            if ($location) {
                $set('city', $location['city']);
                $set('state', $location['state']);
                $set('country', $location['country']);
                $set('locality', $location['locality']);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Pincode API Error: ' . $e->getMessage());
        }
    }

}
