<?php

namespace App\Filament\Resources\BankSoals\RelationManagers;

use App\Filament\Resources\BankSoals\Schemas\SoalForm;
use App\Models\Soal;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SoalsRelationManager extends RelationManager
{
    protected static string $relationship = 'soals';

    protected static ?string $title = 'Butir Soal';

    protected static ?string $modelLabel = 'Butir Soal';

    protected static ?string $pluralModelLabel = 'Butir Soal';

    protected static string | \BackedEnum | null $icon = Heroicon::OutlinedListBullet;

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) $ownerRecord->soals()->count();
    }

    protected function nextNomorUrut(): int
    {
        return ((int) $this->getOwnerRecord()->soals()->max('nomor_urut')) + 1;
    }

    public function form(Schema $schema): Schema
    {
        return SoalForm::configure($schema, fn () => $this->nextNomorUrut());
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('pertanyaan')
            ->defaultSort('nomor_urut', 'asc')
            ->reorderable('nomor_urut')
            ->paginated([10, 25, 50, 100, 'all'])
            ->defaultPaginationPageOption(25)
            ->recordAction('edit')
            ->columns([
                TextColumn::make('nomor_urut')
                    ->label('No.')
                    ->alignCenter()
                    ->width('60px')
                    ->sortable(),

                TextColumn::make('pertanyaan')
                    ->label('Pertanyaan')
                    ->formatStateUsing(fn ($state) => Str::limit(trim(html_entity_decode(strip_tags((string) $state))), 120))
                    ->description(fn (Soal $record) => $record->tipe_soal_label)
                    ->wrap()
                    ->searchable(),

                TextColumn::make('kunci')
                    ->label('Kunci')
                    ->state(fn (Soal $record): string => match ($record->tipe_soal) {
                        'isian' => Str::limit((string) $record->kunci_isian, 20) ?: '-',
                        'essay' => 'Rubrik',
                        'menjodohkan' => $record->opsiJawabans->count() . ' pasang',
                        default => $record->opsiJawabans->where('is_jawaban_benar', true)->pluck('label')->filter()->join(', ') ?: '⚠ Belum ada',
                    })
                    ->badge()
                    ->color(fn (string $state) => str_starts_with($state, '⚠') ? 'danger' : 'success'),

                TextColumn::make('media_type')
                    ->label('Media')
                    ->badge()
                    ->color('gray')
                    ->icon(fn (?string $state) => match ($state) {
                        'audio' => Heroicon::OutlinedSpeakerWave,
                        'image' => Heroicon::OutlinedPhoto,
                        'video' => Heroicon::OutlinedVideoCamera,
                        default => null,
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'audio' => 'Audio',
                        'image' => 'Gambar',
                        'video' => 'Video',
                        default => '-',
                    })
                    ->toggleable(),

                TextColumn::make('bobot_nilai')
                    ->label('Bobot')
                    ->alignEnd()
                    ->formatStateUsing(fn ($state) => rtrim(rtrim(number_format((float) $state, 2, ',', ''), '0'), ',') . ' poin')
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()->label('Total'))
                    ->sortable(),
            ])
            ->modifyQueryUsing(fn ($query) => $query->with('opsiJawabans'))
            ->filters([
                SelectFilter::make('tipe_soal')
                    ->label('Tipe Soal')
                    ->options(SoalForm::TIPE_SOAL),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Soal')
                    ->icon(Heroicon::OutlinedPlus)
                    ->modalHeading('Tambah Butir Soal')
                    ->modalWidth(Width::SevenExtraLarge)
                    ->stickyModalHeader()
                    ->stickyModalFooter()
                    ->closeModalByClickingAway(false)
                    ->createAnotherAction(fn ($action) => $action->label('Simpan & Tambah Lagi')),
            ])
            ->recordActions([
                EditAction::make()
                    ->iconButton()
                    ->tooltip('Edit')
                    ->modalHeading(fn (Soal $record) => "Edit Soal No. {$record->nomor_urut}")
                    ->modalWidth(Width::SevenExtraLarge)
                    ->stickyModalHeader()
                    ->stickyModalFooter()
                    ->closeModalByClickingAway(false),

                ActionGroup::make([
                    ReplicateAction::make()
                        ->label('Duplikat')
                        ->icon(Heroicon::OutlinedDocumentDuplicate)
                        ->excludeAttributes(['opsi_jawabans_count'])
                        ->beforeReplicaSaved(function (Soal $replica): void {
                            $replica->nomor_urut = $this->nextNomorUrut();
                        })
                        ->after(function (Soal $replica, Soal $record): void {
                            foreach ($record->opsiJawabans as $opsi) {
                                $replica->opsiJawabans()->create($opsi->only([
                                    'label', 'teks_opsi', 'gambar_opsi', 'is_jawaban_benar', 'pasangan_matching', 'nomor_urut',
                                ]));
                            }
                        }),

                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada butir soal')
            ->emptyStateDescription('Klik "Tambah Soal" untuk mulai membuat soal.')
            ->emptyStateIcon(Heroicon::OutlinedDocumentText);
    }
}
