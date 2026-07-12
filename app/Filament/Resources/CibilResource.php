<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CibilResource\Pages;
use App\Models\CibilReport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;

class CibilResource extends Resource
{
    protected static ?string $model = CibilReport::class;
    protected static ?string $navigationLabel = 'CIBIL Reports';
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationGroup = 'Loan Management';
    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return (string) CibilReport::query()->count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Personal Information')
                    ->schema([
                        Forms\Components\TextInput::make('customer_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('loan_type')
                            ->label('Loan Type')
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
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Clear PAN number when loan type changes
                                $set('pan_number', '');
                            }),
                    ])->columns(2),

                Forms\Components\Section::make('Identity Details')
                    ->schema([
                        Forms\Components\TextInput::make('pan_number')
                            ->label('PAN Number')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('ABCDE1234F')
                            ->helperText(function (callable $get) {
                                $loanType = $get('loan_type');
                                if ($loanType === 'business') {
                                    return 'Please enter a valid Business PAN.';
                                } elseif ($loanType === 'personal') {
                                    return 'Please enter a valid Personal PAN.';
                                }
                                return '';
                            })
                            ->formatStateUsing(fn ($state) => $state ? strtoupper($state) : null)
                            ->dehydrateStateUsing(fn ($state) => $state ? strtoupper($state) : null)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state) {
                                    $formattedPan = strtoupper($state);
                                    $set('pan_number', $formattedPan);

                                    // Validate PAN based on loan type
                                    $loanType = $get('loan_type');
                                    $firstChar = substr($formattedPan, 0, 1);
                                    $isValid = true;
                                    $errorMessage = '';

                                    if ($loanType === 'business' && !in_array($firstChar, ['A', 'B', 'C', 'F', 'G', 'L', 'T'])) {
                                        $isValid = false;
                                        $errorMessage = 'Invalid Business PAN Format:
• First letter must be one of: A, B, C, F, G, L, or T
• A (Proprietorship)
• B (Partnership/LLP)
• C (Company)
• F (Association/Trust)
• G (Government)
• L (Local Authority)
• T (Trust)';
                                    } elseif ($loanType === 'personal' && $firstChar !== 'P') {
                                        $isValid = false;
                                        $errorMessage = 'Personal PAN must start with P';
                                    }

                                    if (!$isValid) {
                                        Notification::make()
                                            ->title('Invalid PAN Number')
                                            ->body($errorMessage)
                                            ->danger()
                                            ->send();
                                        $set('pan_number', '');
                                        return;
                                    }

                                    self::fetchCIBILReport($formattedPan, $set);
                                }
                            })
                            ->rules(fn (Forms\Get $get): array => [
                                'required',
                                'string',
                                'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/',
                                function ($attribute, $value, $fail) use ($get) {
                                    $loanType = $get('loan_type');
                                    if (!$value) return;

                                    $firstChar = substr($value, 0, 1);

                                    if ($loanType === 'business' && !in_array($firstChar, ['A', 'B', 'C', 'F', 'G', 'L', 'T'])) {
                                        $fail('Invalid Business PAN Format:
• First letter must be one of: A, B, C, F, G, L, or T
• A (Proprietorship)
• B (Partnership/LLP)
• C (Company)
• F (Association/Trust)
• G (Government)
• L (Local Authority)
• T (Trust)');
                                    } elseif ($loanType === 'personal' && $firstChar !== 'P') {
                                        $fail('Personal PAN must start with P.');
                                    }
                                }
                            ])
                            ->validationAttribute('PAN number'),

                        Forms\Components\TextInput::make('aadhaar_number')
                            ->label('Aadhaar Number')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->numeric()
                            ->length(12)
                            ->placeholder('123456789012')
                            ->helperText('12 digit Aadhaar number'),

                        Forms\Components\Hidden::make('is_aadhaar_verified')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('CIBIL Information')
                    ->schema([
                        Forms\Components\TextInput::make('cibil_score')
                            ->label('CIBIL Score')
                            ->disabled()
                            ->numeric()
                            ->minValue(300)
                            ->maxValue(900)
                            ->dehydrated(true),

                        Forms\Components\Select::make('risk_category')
                            ->label('Risk Category')
                            ->options([
                                'low' => 'Low Risk (750-900)',
                                'medium' => 'Medium Risk (650-749)',
                                'high' => 'High Risk (300-649)',
                            ])
                            ->disabled()
                            ->dehydrated(true),

                        Forms\Components\Textarea::make('cibil_summary')
                            ->label('CIBIL Summary')
                            ->disabled()
                            ->dehydrated(true)
                            ->rows(3),

                        Forms\Components\Textarea::make('remarks')
                            ->label('Remarks')
                            ->rows(2),
                    ])->columns(2),

                Forms\Components\Section::make('Credit History')
                    ->schema([
                        Forms\Components\TextInput::make('active_loans')
                            ->label('Active Loans')
                            ->disabled()
                            ->numeric()
                            ->dehydrated(true),

                        Forms\Components\TextInput::make('total_loan_amount')
                            ->label('Total Loan Amount')
                            ->disabled()
                            ->numeric()
                            ->prefix('₹')
                            ->dehydrated(true),

                        Forms\Components\TextInput::make('emi_bounces')
                            ->label('EMI Bounces (Last 12 Months)')
                            ->disabled()
                            ->numeric()
                            ->dehydrated(true),

                        Forms\Components\TextInput::make('credit_utilization')
                            ->label('Credit Utilization (%)')
                            ->disabled()
                            ->numeric()
                            ->suffix('%')
                            ->dehydrated(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('loan_type')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pan_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cibil_score')
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        $state >= 750 => 'success',
                        $state >= 650 => 'warning',
                        default => 'danger',
                    }),
                Tables\Columns\BadgeColumn::make('risk_category')
                    ->colors([
                        'success' => 'low',
                        'warning' => 'medium',
                        'danger' => 'high',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Check Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->searchable()
            ->filters([
                Tables\Filters\Filter::make('global_search')
                    ->form([
                        Forms\Components\TextInput::make('q')
                            ->label('Search')
                            ->placeholder('Search name or PAN')
                            ->dehydrated(false),
                    ])
                    ->query(function ($query, $data) {
                        if (empty($data['q'])) {
                            return $query;
                        }

                        return $query->where(function ($q) use ($data) {
                            $q->where('customer_name', 'like', '%' . $data['q'] . '%')
                              ->orWhere('pan_number', 'like', '%' . $data['q'] . '%');
                        });
                    })
                    ->indicateUsing(fn ($data): ?string => $data['q'] ? "Search: {$data['q']}" : null),

                Tables\Filters\SelectFilter::make('risk_category')
                    ->options([
                        'low' => 'Low Risk',
                        'medium' => 'Medium Risk',
                        'high' => 'High Risk',
                    ]),
                Tables\Filters\SelectFilter::make('loan_type')
                    ->options([
                        'personal' => 'Personal Loan',
                        'home' => 'Home Loan',
                        'business' => 'Business Loan',
                        'education' => 'Education Loan',
                        'car' => 'Car Loan',
                        'gold' => 'Gold Loan',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                ]),
            ]);
    }

    private static function fetchCIBILReport($panNumber, callable $set)
    {
        try {
            // Mock data based on PAN number patterns
            // In a production environment, replace this with actual API call
            $mockData = self::getMockCibilData($panNumber);

            // Set CIBIL data
            $set('cibil_score', $mockData['score']);

            // Set risk category based on score
            $score = $mockData['score'];
            $riskCategory = match(true) {
                $score >= 750 => 'low',
                $score >= 650 => 'medium',
                default => 'high',
            };
            $set('risk_category', $riskCategory);

            // Set other credit information
            $set('active_loans', $mockData['active_loans']);
            $set('total_loan_amount', $mockData['total_loan_amount']);
            $set('emi_bounces', $mockData['emi_bounces']);
            $set('credit_utilization', $mockData['credit_utilization']);

            // Generate summary based on score
            $summary = match($riskCategory) {
                'low' => "Excellent credit history. Low risk borrower with consistent repayment record. CIBIL Score: {$score}",
                'medium' => "Good credit history with some minor issues. Moderate risk borrower. CIBIL Score: {$score}",
                'high' => "Poor credit history with multiple issues. High risk borrower. CIBIL Score: {$score}",
            };
            $set('cibil_summary', $summary);

            Notification::make()
                ->title('CIBIL Report Generated')
                ->body("Successfully generated CIBIL report for PAN: {$panNumber}")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Log::error('CIBIL Generation Error: ' . $e->getMessage());

            Notification::make()
                ->title('CIBIL Report Error')
                ->body('Failed to generate CIBIL report. Please try again.')
                ->danger()
                ->send();

            $set('cibil_score', 'N/A');
            $set('cibil_summary', 'Failed to generate report: ' . $e->getMessage());
        }
    }

    private static function getMockCibilData($panNumber): array
    {
        // Generate deterministic but seemingly random data based on PAN number
        $hash = crc32($panNumber);
        $seed = abs($hash);
        mt_srand($seed);

        // Default test PAN number case
        if ($panNumber === 'ABCDE1234F') {
            return [
                'score' => 750,
                'active_loans' => 2,
                'total_loan_amount' => 500000,
                'emi_bounces' => 0,
                'credit_utilization' => 45,
            ];
        }

        // Generate score based on first character of PAN
        $firstChar = substr($panNumber, 0, 1);
        $baseScore = match($firstChar) {
            'A' => 750, // Excellent
            'B' => 700, // Very Good
            'C' => 650, // Good
            'D' => 600, // Fair
            default => 550, // Poor
        };

        // Add some variation but keep within range
        $score = min(900, max(300, $baseScore + mt_rand(-50, 50)));

        return [
            'score' => $score,
            'active_loans' => mt_rand(0, 5),
            'total_loan_amount' => mt_rand(100000, 2000000),
            'emi_bounces' => mt_rand(0, 3),
            'credit_utilization' => mt_rand(20, 80),
        ];
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
            'index' => Pages\ListCibils::route('/'),
            'create' => Pages\CreateCibil::route('/create'),
            'edit' => Pages\EditCibil::route('/{record}/edit'),
        ];
    }
}
