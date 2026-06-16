<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProposalResource\Pages;
use App\Models\Proposal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ProposalResource extends Resource
{
    protected static ?string $model = Proposal::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Pengajuan Proposal';

    public static function canViewAny(): bool
    {
        // Admin TIDAK BOLEH melihat menu/halaman proposal
        // Mahasiswa dan Role Pejabat BOLEH melihat
        return auth()->user()->role !== 'admin';
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->role === 'mahasiswa') {
            return $user->id === $record->user_id && (
                $record->status === 'revision' ||
                ($record->status === 'pending' && $record->current_step === 'bem')
            );
        }

        return false;
    }


    // Tambahkan fungsi ini di dalam class ProposalResource
    public static function canCreate(): bool
    {
        // Hanya izinkan jika role user adalah mahasiswa
        return auth()->user()->role === 'mahasiswa';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Data Proposal')
                    ->description('Isi detail proposal kegiatan Anda di bawah ini.')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Judul Kegiatan')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('original_file')
                            ->label('Dokumen Proposal (PDF)')
                            ->directory('proposals/originals')
                            ->acceptedFileTypes(['application/pdf'])
                            ->required()
                            ->preserveFilenames()
                            ->openable()
                            ->getUploadedFileNameForStorageUsing(
                                fn(TemporaryUploadedFile $file): string => (string) str(Str::random(20) . '.' . $file->getClientOriginalExtension()),
                            )
                            ->downloadable(),

                        // Hidden fields untuk logika sistem
                        Forms\Components\Hidden::make('user_id')
                            ->default(auth()->id()),

                        Forms\Components\Hidden::make('status')
                            ->default('pending'),

                        Forms\Components\Hidden::make('current_step')
                            ->default('bem'),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pengaju')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Judul')
                    ->limit(30)
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'danger' => 'revision',
                        'success' => 'completed',
                    ]),
                Tables\Columns\TextColumn::make('current_step')
                    ->label('Posisi Dokumen')
                    ->formatStateUsing(fn(string $state): string => strtoupper($state))
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Masuk')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                // Tambahkan filter jika diperlukan nanti
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                // TOMBOL APPROVE (Hanya muncul untuk Pejabat)
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->hidden(function ($record) {
                        return auth()->user()->role === 'mahasiswa' ||
                            $record->status === 'completed' ||
                            $record->status === 'revision' ||
                            auth()->user()->role !== $record->current_step;
                    })
                    ->form([
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan (Opsional)'),
                        Forms\Components\FileUpload::make('signed_file')
                            ->label('Upload File TTD (Khusus Kaprodi)')
                            ->directory('proposals/signed')
                            ->acceptedFileTypes(['application/pdf'])
                            ->getUploadedFileNameForStorageUsing(
                                fn(TemporaryUploadedFile $file): string => (string) str(Str::random(20) . '.' . $file->getClientOriginalExtension()),
                            )
                            ->visible(fn() => auth()->user()->role === 'kaprodi'),
                        // KHUSUS PAYMENT (Role tu)
                        Forms\Components\FileUpload::make('payment_proof')
                            ->label('Unggah Bukti Transfer/Pembayaran')
                            ->image()
                            ->imageEditor()
                            ->directory('proposals/payments')
                            ->disk('public')
                            ->visibility('public')
                            ->maxSize(2048)
                            ->getUploadedFileNameForStorageUsing(
                                fn(TemporaryUploadedFile $file): string => (string) str(Str::random(20) . '.' . $file->getClientOriginalExtension()),
                            )
                            ->visible(fn() => auth()->user()->role === 'tu')
                            ->required(fn() => auth()->user()->role === 'tu'),
                    ])
                    ->action(function ($record, array $data) {
                        $nextStep = match ($record->current_step) {
                            'bem'     => 'kaprodi',
                            'kaprodi' => 'dekan',
                            'dekan'   => 'wd3',
                            'wd3'     => 'wd2',
                            'wd2'     => 'tu',
                            'tu'      => 'selesai',
                            default   => 'selesai',
                        };

                        $record->update([
                            'current_step'  => $nextStep,
                            'current_file'  => $data['signed_file'] ?? $record->current_file,
                            'payment_proof' => $data['payment_proof'] ?? $record->payment_proof,
                            'status'        => ($nextStep === 'selesai') ? 'completed' : 'pending',
                        ]);

                        $record->logs()->create([
                            'user_id'       => auth()->id(),
                            'action'        => 'approved',
                            'notes'         => $data['notes'] ?? 'Disetujui oleh ' . strtoupper(auth()->user()->role),
                            'file_result'   => $data['signed_file'] ?? null,
                            'payment_proof' => $data['payment_proof'] ?? null,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Proposal Berhasil Di-approve')
                            ->success()
                            ->send();
                    }),

                // TOMBOL REVISI (Hanya muncul untuk Pejabat)
                Tables\Actions\Action::make('revisi')
                    ->label('Revisi')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->hidden(function ($record) {
                        return auth()->user()->role === 'mahasiswa' ||
                            $record->status === 'completed' ||
                            $record->status === 'revision' ||
                            auth()->user()->role !== $record->current_step;
                    })
                    ->form([
                        Forms\Components\Textarea::make('notes')
                            ->label('Alasan Revisi')
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'revision',
                            'current_step' => 'bem',
                        ]);

                        $record->logs()->create([
                            'user_id' => auth()->id(),
                            'action' => 'revision',
                            'notes' => $data['notes'],
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Permintaan Revisi Dikirim')
                            ->danger()
                            ->send();
                    }),

                // TOMBOL EDIT (Diperbaiki Keamanan Kepemilikan & Tipe Data untuk Hosting)
                Tables\Actions\EditAction::make(),


                // TOMBOL TARIK KEMBALI (Diperbaiki Keamanan Kepemilikan untuk Hosting)
                Tables\Actions\Action::make('tarikKeDraft')
                    ->label('Tarik Kembali')
                    ->color('warning')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->modalHeading('Tarik Pengajuan Proposal?')
                    ->modalDescription('Apakah Anda yakin ingin menarik kembali proposal ini? Status akan berubah menjadi revisi agar Anda bisa mengubah datanya kembali.')
                    ->visible(function ($record) {
                        return auth()->user()->role === 'mahasiswa' &&

                            $record->status === 'pending' &&
                            $record->current_step === 'bem';
                    })
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'revision',
                            'current_step' => 'bem',
                        ]);

                        $record->logs()->create([
                            'user_id' => auth()->id(),
                            'action' => 'revision',
                            'notes' => 'Proposal ditarik kembali secara mandiri oleh mahasiswa untuk diperbaiki.',
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Status Berhasil Diubah')
                            ->body('Proposal sekarang berstatus REVISI dan gembok edit telah terbuka.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // SECTION 1: Informasi Utama
                Infolists\Components\Section::make('Informasi Proposal')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('title')
                                    ->label('Judul Proposal')
                                    ->columnSpan(2)
                                    ->weight(\Filament\Support\Enums\FontWeight::Bold),

                                Infolists\Components\TextEntry::make('status')
                                    ->label('Status Saat Ini')
                                    ->badge()
                                    ->color(fn(string $state): string => match ($state) {
                                        'pending' => 'warning',
                                        'revision' => 'danger',
                                        'completed' => 'success',
                                        default => 'gray',
                                    }),
                            ]),
                    ]),

                // SECTION 2: Bukti Pembayaran (Preview Besar)
                Infolists\Components\Section::make('Bukti Pembayaran')
                    ->description('Bukti transfer atau nota pembayaran yang diunggah oleh Bendahara.')
                    ->icon('heroicon-o-credit-card')
                    ->collapsible()
                    ->visible(fn($record) => $record->payment_proof !== null)
                    ->schema([
                        Infolists\Components\ViewEntry::make('payment_proof')
                            ->label('')
                            ->view('filament.components.payment-preview')
                            ->columnSpanFull(),
                    ]),

                // SECTION 3: Preview PDF
                Infolists\Components\Section::make('Preview Dokumen')
                    ->icon('heroicon-o-document-text')
                    ->collapsible()
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\ViewEntry::make('original_file')
                                    ->label('1. Dokumen Asli (Original)')
                                    ->view('filament.components.pdf-viewer'),

                                Infolists\Components\ViewEntry::make('current_file')
                                    ->label('2. Dokumen Terbaru (Hasil Proses)')
                                    ->view('filament.components.pdf-viewer')
                                    ->visible(fn($record) => $record->current_file !== null),
                            ]),
                    ]),

                // SECTION 4: Riwayat (History)
                Infolists\Components\Section::make('Riwayat Perjalanan (History)')
                    ->description('Log aktivitas dan perubahan status proposal.')
                    ->icon('heroicon-o-clock')
                    ->collapsible()
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('logs')
                            ->label('')
                            ->schema([
                                Infolists\Components\Grid::make(6) // Ditambah kolomnya agar lebih lega
                                    ->schema([
                                        Infolists\Components\TextEntry::make('created_at')
                                            ->label('Waktu')
                                            ->dateTime('d/m/Y H:i')
                                            ->color('gray'),

                                        Infolists\Components\TextEntry::make('user.name')
                                            ->label('Oleh')
                                            ->weight(\Filament\Support\Enums\FontWeight::Bold),

                                        Infolists\Components\TextEntry::make('action')
                                            ->label('Aksi')
                                            ->badge()
                                            ->formatStateUsing(fn(string $state): string => strtoupper($state))
                                            ->color(fn(string $state): string => match ($state) {
                                                'submitted' => 'info',
                                                'approved' => 'success',
                                                'revision' => 'danger',
                                                default => 'gray',
                                            }),

                                        Infolists\Components\IconEntry::make('file_result')
                                            ->label('Hasil TTD')
                                            ->icon('heroicon-o-arrow-down-tray')
                                            ->color('primary')
                                            ->visible(fn($state) => $state !== null)
                                            ->url(fn($state) => \Illuminate\Support\Facades\Storage::disk('public')->url($state), true),

                                        Infolists\Components\IconEntry::make('payment_proof')
                                            ->label('Bukti Bayar')
                                            ->icon('heroicon-o-camera')
                                            ->color('success')
                                            ->visible(fn($state) => $state !== null)
                                            ->url(fn($state) => \Illuminate\Support\Facades\Storage::disk('public')->url($state), true),

                                        Infolists\Components\TextEntry::make('notes')
                                            ->label('Catatan')
                                            ->columnSpan(6) // Berikan span penuh agar catatan tidak sempit
                                            ->color('gray')

                                            ->placeholder('Tidak ada catatan tambahan.'),
                                    ]),
                            ])
                            ->grid(1)
                            ->extraAttributes(['class' => 'space-y-4']),
                    ]),
            ]);
    }
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user) {
            return $query;
        }

        if ($user->role === 'mahasiswa') {
            return $query->where('user_id', $user->id);
        }

        if ($user->role === 'kaprodi') {
            return $query->whereHas('user', function (Builder $subQuery) use ($user) {
                $subQuery->where('department_id', $user->department_id);
            });
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProposals::route('/'),
            'create' => Pages\CreateProposal::route('/create'),
            'view' => Pages\ViewProposal::route('/{record}'),
            'edit' => Pages\EditProposal::route('/{record}/edit'),
        ];
    }
}
