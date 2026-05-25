<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
// PENTING: Tambahkan import di bawah ini agar Hash::make bisa jalan
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users'; // Icon lebih cocok untuk User

    protected static ?string $navigationLabel = 'Manajemen User';

    public static function canAccess(): bool
    {
        // Memastikan hanya admin yang bisa masuk ke area ini
        return auth()->user()->role === 'admin';
    }

    // TAMBAHKAN INI agar tombol "New User" / "Tambah" muncul
    public static function canCreate(): bool
    {
        return auth()->user()->role === 'admin';
    }

    // Opsional: Pastikan admin bisa edit & hapus secara eksplisit
    public static function canEdit($record): bool
    {
        return auth()->user()->role === 'admin';
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->role === 'admin';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Akun')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required(),

                        Forms\Components\TextInput::make('email')
                            ->label('Alamat Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            // Mengenkripsi password secara otomatis sebelum disimpan
                            ->dehydrateStateUsing(fn($state) => Hash::make($state))
                            // Password wajib diisi hanya saat "Create"
                            ->required(fn(string $context): bool => $context === 'create')
                            // Field password disembunyikan saat "Edit" agar tidak tertimpa kecuali ingin diganti
                            ->visible(fn(string $context): bool => $context === 'create'),

                        Forms\Components\Select::make('role')
                            ->label('Role/Jabatan')
                            ->options([
                                'mahasiswa' => 'Mahasiswa',
                                'bem' => 'BEM',
                                'kaprodi' => 'Kaprodi',
                                'dekan' => 'Dekan',
                                'wd3' => 'Wakil Dekan III',
                                'wd2' => 'Wakil Dekan II',
                                'tu' => 'Tata Usaha',
                                'admin' => 'Admin',
                            ])
                            ->required(),

                        Forms\Components\Select::make('department_id')
                            ->label('Departemen/Prodi')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Kosongkan jika role adalah Admin atau BEM (Lingkup Fakultas)'),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'admin' => 'danger',
                        'mahasiswa' => 'info',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('department.name')
                    ->label('Departemen')
                    ->placeholder('Semua Departemen'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'mahasiswa' => 'Mahasiswa',
                        'bem' => 'BEM',
                        'kaprodi' => 'Kaprodi',
                        'dekan' => 'Dekan',
                        'wd3' => 'Wakil Dekan III',
                        'wd2' => 'Wakil Dekan II',
                        'tu' => 'Tata Usaha',
                        'admin' => 'Admin',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
