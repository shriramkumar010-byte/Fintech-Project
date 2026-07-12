<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EMIResource\Pages;
use App\Filament\Resources\EMIResource\RelationManagers;
use App\Models\EMI;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EMIResource extends Resource
{
    protected static ?string $model = EMI::class;
    protected static ?string $navigationLabel = 'EMI Calculate';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Grid::make(3)->schema([
                TextInput::make('principal')
                    ->label('Loan Amount (₹)')
                    ->numeric()
                    ->required(),

                TextInput::make('rate')
                    ->label('Interest Rate (%)')
                    ->numeric()
                    ->required(),

                TextInput::make('duration')
                    ->label('Duration (Months)')
                    ->numeric()
                    ->required(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\EMICalculator::route('/'),
        ];
    }
}
