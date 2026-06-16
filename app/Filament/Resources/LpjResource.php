<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LpjResource\Pages;
use App\Filament\Resources\LpjResource\RelationManagers;
use App\Models\Lpj;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class LpjResource extends Resource
{
    protected static ?string $model = Lpj::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function canCreate(): bool
    {
        // Hanya mahasiswa yang diizinkan membuat dokumen LPJ baru
        return auth()->check() && auth()->user()->role === 'mahasiswa';
    }

    /**
     * Membatasi Akses Menu Navigation / Hak Akses Resource Secara Global
     */
    public static function canViewAny(): bool
    {
        // Pastikan user sudah login
        if (!auth()->check()) {
            return false;
        }

        // Daftar role yang diizinkan melihat menu LPJ
        $allowedRoles = ['mahasiswa', 'kaprodi', 'wd3', 'wd2'];

        // Menu LPJ hanya akan muncul di sidebar jika role user terdaftar di atas
        return in_array(auth()->user()->role, $allowedRoles);
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Form Pelaporan LPJ')
                    ->schema([
                        // Tambahkan baris ini agar user_id otomatis terisi ID user yang login
                        Forms\Components\Hidden::make('user_id')
                            ->default(auth()->id())
                            ->required(),

                        Forms\Components\Select::make('proposal_id')
                            ->label('Pilih Proposal Kegiatan')
                            ->relationship('proposal', 'title', function ($query) {
                                return $query->where('status', 'completed')
                                    ->where('user_id', auth()->id())
                                    /* --- KUNCI FILTERNYA DI SINI --- */
                                    // Hanya tampilkan proposal yang BELUM memiliki hubungan (whereDoesntHave) di tabel lpjs
                                    ->whereDoesntHave('lpj');
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            /* Aturan ->unique() dan validationMessages boleh dihapus karena datanya sudah pasti tidak akan bisa dipilih lagi */
                            ->afterStateUpdated(
                                fn($state, callable $set) =>
                                $set('title', 'LPJ - ' . \App\Models\Proposal::find($state)?->title)
                            ),
                        Forms\Components\TextInput::make('title')
                            ->label('Judul Laporan Pertanggungjawaban')
                            ->required()
                            ->placeholder('Akan terisi otomatis setelah memilih proposal'),

                        Forms\Components\Textarea::make('description')
                            ->label('Evaluasi Singkat / Keterangan Kegiatan')
                            ->placeholder('Tuliskan ringkasan singkat pelaksanaan kegiatan...'),

                        Forms\Components\FileUpload::make('original_file')
                            ->label('Unggah Dokumen LPJ (Format PDF)')
                            ->directory('lpjs/originals')
                            ->disk('public')
                            ->visibility('public')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(5120) // 5 MB Max
                            ->getUploadedFileNameForStorageUsing(
                                fn(TemporaryUploadedFile $file): string => (string) str(Str::random(20) . '.' . $file->getClientOriginalExtension()),
                            )
                            ->required(),

                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Pengaju')->searchable(),
                Tables\Columns\TextColumn::make('proposal.title')->label('Proposal Terkait')->limit(25),
                Tables\Columns\TextColumn::make('title')->label('Judul LPJ')->limit(25),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'danger' => 'revision',
                        'success' => 'completed',
                    ]),
                Tables\Columns\TextColumn::make('current_step')
                    ->label('Posisi Dokumen')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => strtoupper($state))
                    ->color('gray'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn($record): string => static::getUrl('view', ['record' => $record])),

                // 1. TOMBOL EDIT (Hanya untuk Mahasiswa pemilik, dan status BUKAN completed)
                Tables\Actions\EditAction::make()
                    ->hidden(function ($record) {
                        return auth()->user()->role !== 'mahasiswa' ||
                            $record->user_id !== auth()->id() ||
                            $record->status === 'completed';
                    }),

                // 2. TOMBOL HAPUS (Hanya untuk Mahasiswa pemilik, dan status BUKAN completed)
                Tables\Actions\DeleteAction::make()
                    ->hidden(function ($record) {
                        return auth()->user()->role !== 'mahasiswa' ||
                            $record->user_id !== auth()->id() ||
                            $record->status === 'completed';
                    }),

                // TOMBOL APPROVE LPJ
                Tables\Actions\Action::make('approve')
                    ->label('Approve LPJ')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->hidden(function ($record) {
                        return auth()->user()->role === 'mahasiswa' ||
                            $record->status === 'completed' ||
                            auth()->user()->role !== $record->current_step;
                    })
                    ->form([
                        Forms\Components\Textarea::make('notes')->label('Catatan (Opsional)'),

                        Forms\Components\FileUpload::make('signed_file')
                            ->label('Upload LPJ Hasil TTD / Pengesahan')
                            ->directory('lpjs/signed')
                            ->disk('public')
                            ->acceptedFileTypes(['application/pdf'])
                            ->visible(fn() => in_array(auth()->user()->role, ['kaprodi']))
                            ->getUploadedFileNameForStorageUsing(
                                fn(TemporaryUploadedFile $file): string => (string) str(Str::random(20) . '.' . $file->getClientOriginalExtension()),
                            )
                            ->required(fn() => in_array(auth()->user()->role, ['kaprodi'])),
                    ])
                    ->action(function ($record, array $data) {
                        $nextStep = match ($record->current_step) {
                            'kaprodi' => 'wd3',
                            'wd3'     => 'wd2',
                            'wd2'     => 'selesai',
                            default   => 'selesai',
                        };

                        $record->update([
                            'current_step' => $nextStep,
                            'current_file' => $data['signed_file'] ?? $record->current_file,
                            'status'       => ($nextStep === 'wd2' || $nextStep === 'selesai') ? 'completed' : 'pending',
                        ]);

                        $record->Lpjlogs()->create([
                            'user_id'     => auth()->id(),
                            'action'      => 'approved',
                            'notes'       => $data['notes'] ?? 'Disetujui dan ditandatangani oleh ' . strtoupper(auth()->user()->role),
                            'file_result' => $data['signed_file'] ?? null,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('LPJ Berhasil Di-approve & Ditandatangani')
                            ->success()
                            ->send();
                    }),

                // TOMBOL REVISI LPJ
                Tables\Actions\Action::make('revisi')
                    ->label('Kembalikan / Revisi')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->hidden(function ($record) {
                        return auth()->user()->role === 'mahasiswa' ||
                            $record->status === 'completed' ||
                            auth()->user()->role !== $record->current_step;
                    })
                    ->form([
                        Forms\Components\Textarea::make('notes')->label('Alasan Pengembalian / Catatan Revisi')->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status'       => 'revision',
                            'current_step' => 'mahasiswa',
                        ]);

                        // PERBAIKAN: Ubah logs() menjadi Lpjlogs() agar konsisten dengan model Anda
                        $record->Lpjlogs()->create([
                            'user_id' => auth()->id(),
                            'action'  => 'revision',
                            'notes'   => $data['notes'],
                        ]);

                        \Filament\Notifications\Notification::make()->title('LPJ Dikembalikan ke Mahasiswa')->danger()->send();
                    }),
            ]);
    }


    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // SECTION 1: Informasi Utama LPJ
                Components\Section::make('Informasi Laporan Pertanggungjawaban (LPJ)')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('title')
                                    ->label('Judul LPJ')
                                    ->columnSpan(2)
                                    ->weight(\Filament\Support\Enums\FontWeight::Bold),

                                Components\TextEntry::make('status')
                                    ->label('Status Saat Ini')
                                    ->badge()
                                    ->color(fn(string $state): string => match ($state) {
                                        'pending' => 'warning',
                                        'revision' => 'danger',
                                        'completed' => 'success',
                                        default => 'gray',
                                    }),

                                Components\TextEntry::make('proposal.title')
                                    ->label('Proposal Kegiatan Terkait')
                                    ->columnSpan(2)
                                    ->color('gray'),

                                Components\TextEntry::make('user.name')
                                    ->label('Diajukan Oleh (Ormawa)')
                                    ->color('gray'),
                            ]),
                    ]),

                // SECTION 2: Preview PDF
                Components\Section::make('Preview Dokumen LPJ')
                    ->icon('heroicon-o-document-text')
                    ->collapsible()
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\ViewEntry::make('original_file')
                                    ->label('1. Dokumen LPJ Asli (Original Mahasiswa)')
                                    ->view('filament.components.pdf-viewer'),

                                Components\ViewEntry::make('current_file')
                                    ->label('2. Dokumen LPJ Resmi (Hasil TTD Pejabat)')
                                    ->view('filament.components.pdf-viewer')
                                    ->visible(fn($record) => $record->current_file !== null),
                            ]),
                    ]),

                // SECTION 3: Riwayat Perjalanan Dokumen (History Logs)
                Components\Section::make('Riwayat Perjalanan (History LPJ)')
                    ->description('Log aktivitas, catatan revisi, dan pengesahan dokumen LPJ.')
                    ->icon('heroicon-o-clock')
                    ->collapsible()
                    ->schema([
                        Components\RepeatableEntry::make('Lpjlogs')
                            ->label('')
                            ->schema([
                                Components\Grid::make(6)
                                    ->schema([
                                        Components\TextEntry::make('created_at')
                                            ->label('Waktu')
                                            ->dateTime('d/m/Y H:i')
                                            ->color('gray'),

                                        Components\TextEntry::make('user.name')
                                            ->label('Oleh')
                                            ->weight(\Filament\Support\Enums\FontWeight::Bold),

                                        Components\TextEntry::make('action')
                                            ->label('Aksi')
                                            ->badge()
                                            ->formatStateUsing(fn(string $state): string => strtoupper($state))
                                            ->color(fn(string $state): string => match ($state) {
                                                'submitted' => 'info',
                                                'approved' => 'success',
                                                'revision' => 'danger',
                                                default => 'gray',
                                            }),

                                        Components\IconEntry::make('file_result')
                                            ->label('Hasil TTD')
                                            ->icon('heroicon-o-arrow-down-tray')
                                            ->color('primary')
                                            ->visible(fn($state) => $state !== null)
                                            ->url(fn($state) => \Illuminate\Support\Facades\Storage::disk('public')->url($state), true),

                                        // Components\TextEntry::make('')
                                        //     ->columnSpan(2),

                                        Components\TextEntry::make('notes')
                                            ->label('Catatan Pemeriksa')
                                            ->columnSpan(6)
                                            ->color('gray')
                                            ->placeholder('Tidak ada catatan tambahan.'),
                                    ]),
                            ])
                            ->grid(1)
                            ->extraAttributes(['class' => 'space-y-4']),
                    ]),
            ]);
    }
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        // 1. Definisikan variabel $user di paling atas agar bisa dipakai di bawah
        $user = auth()->user();

        // Jika user tidak login, langsung kembalikan query kosong (safety guard)
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        // 2. Jika login sebagai mahasiswa: hanya bisa lihat LPJ dari proposal miliknya sendiri
        if ($user->role === 'mahasiswa') {
            return $query->whereHas('proposal', function (\Illuminate\Database\Eloquent\Builder $subQuery) use ($user) {
                $subQuery->where('user_id', $user->id);
            });
        }

        // 3. Jika Kaprodi: Lihat LPJ yang proposalnya diajukan oleh mahasiswa dengan department_id yang sama
        if ($user->role === 'kaprodi') {
            // Menyeberang dari LPJ -> Proposal -> User (Mahasiswa)
            return $query->whereHas('proposal.user', function (\Illuminate\Database\Eloquent\Builder $subQuery) use ($user) {
                $subQuery->where('department_id', $user->department_id);
            });
        }

        // 4. Pejabat fakultas lainnya (BEM, Dekan, WD3, WD2, TU) bisa melihat semua LPJ yang masuk
        return $query;
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLpjs::route('/'),
            'create' => Pages\CreateLpj::route('/create'),
            'view' => Pages\ViewLpj::route('/{record}'),
            'edit' => Pages\EditLpj::route('/{record}/edit'),
        ];
    }
}
