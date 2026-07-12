<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Filament\Resources\RoleResource\RelationManagers;
use App\Models\Role;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use BezhanSalleh\FilamentShield\Resources\RoleResource as BaseRoleResource;
use Illuminate\Support\Facades\Auth;
class RoleResource extends BaseRoleResource
{
    public static function getNavigationGroup(): ?string
    {
        return 'Team Management';
    }

    /*public static function canViewAny(): bool
    {
        return Auth::user()->can('view_any_role');
    }

    public static function canView($record): bool
    {
        return Auth::user()->can('view_role');
    }

    public static function canCreate(): bool
    {
        return Auth::user()->can('create_role');
    }

    public static function canUpdate($record): bool
    {
        return Auth::user()->can('update_role');
    }

    public static function canDelete($record): bool
    {
        return Auth::user()->can('delete_role');
    }

    public static function canDeleteAny(): bool
    {
        return Auth::user()->can('delete_any_role');
    }

    public static function canRestore($record): bool
    {
        return Auth::user()->can('restore_role');
    }

    public static function canRestoreAny(): bool
    {
        return Auth::user()->can('restore_any_role');
    }

    public static function canReplicate($record): bool
    {
        return Auth::user()->can('replicate_role');
    }

    public static function canReorder(): bool
    {
        return Auth::user()->can('reorder_role');
    }

    public static function canForceDelete($record): bool
    {
        return Auth::user()->can('force_delete_role');
    }

    public static function canForceDeleteAny(): bool
    {
        return Auth::user()->can('force_delete_any_role');
    }*/
}
