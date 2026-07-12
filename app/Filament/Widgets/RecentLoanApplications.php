<?php

namespace App\Filament\Widgets;

use App\Models\LoanApplication;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentLoanApplications extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int $defaultPaginationPageOption = 5;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                LoanApplication::with('cibilReport')
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('loan_type')
                    ->badge(),

                Tables\Columns\TextColumn::make('amount')
                    ->money('INR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('cibilReport.cibil_score')
                    ->label('CIBIL')
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        $state >= 750 => 'success',
                        $state >= 650 => 'warning',
                        default => 'danger',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Applied On')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (LoanApplication $record): bool => $record->status === 'pending')
                    ->action(fn (LoanApplication $record) => $record->update(['status' => 'approved'])),

                Tables\Actions\Action::make('reject')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (LoanApplication $record): bool => $record->status === 'pending')
                    ->action(fn (LoanApplication $record) => $record->update(['status' => 'rejected'])),

                Tables\Actions\ViewAction::make(),
            ])
            ->paginated(false);
    }
} 