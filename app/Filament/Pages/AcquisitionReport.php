<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Support\Acquisition;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use UnitEnum;

/**
 * "Aquisição": where new users come from. Two lenses over the same sign-ups —
 * the tracked channel (?utm_source in the QR codes / ads) and what people answered to
 * "Como você conheceu o ZunMoto?" — with how many completed the sign-up and actually used the app.
 */
class AcquisitionReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Usuários';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Aquisição';

    protected static ?string $title = 'Aquisição de usuários';

    protected static ?string $slug = 'aquisicao';

    protected string $view = 'filament.pages.acquisition-report';

    public const PERIODS = [
        '7' => 'Últimos 7 dias',
        '30' => 'Últimos 30 dias',
        '90' => 'Últimos 90 dias',
        'all' => 'Desde o início',
    ];

    public const LENSES = [
        'channel' => 'Canal rastreado (link / QR code)',
        'asked' => 'Como conheceu (pergunta no cadastro)',
    ];

    public function table(Table $table): Table
    {
        return $table
            ->records(function (?array $filters): array {
                return self::rows(
                    (string) ($filters['period']['value'] ?? '30'),
                    (string) ($filters['lens']['value'] ?? 'channel'),
                )->all();
            })
            ->paginated(false)
            ->columns([
                TextColumn::make('name')->label('Origem')->weight('bold'),
                TextColumn::make('signups')->label('Cadastros')->numeric(),
                TextColumn::make('completed')->label('Cadastros completos')->numeric()
                    ->description(fn (array $record) => $record['completed_pct']),
                TextColumn::make('active')->label('Usaram o app')->numeric()
                    ->description(fn (array $record) => $record['active_pct'])
                    ->tooltip('Publicaram uma vaga ou se candidataram a alguma'),
            ])
            ->filters([
                SelectFilter::make('period')->label('Período')->options(self::PERIODS)->default('30')->selectablePlaceholder(false),
                SelectFilter::make('lens')->label('Ver por')->options(self::LENSES)->default('channel')->selectablePlaceholder(false),
            ], layout: \Filament\Tables\Enums\FiltersLayout::AboveContent)
            ->emptyStateHeading('Nenhum cadastro neste período');
    }

    /**
     * One row per origin (plus a total), most sign-ups first.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function rows(string $period = '30', string $lens = 'channel'): Collection
    {
        $query = User::where('is_admin', false)
            ->with(['acquisitionChannel', 'profile'])
            ->withExists(['shifts', 'applications']);

        if (ctype_digit($period)) {
            $query->where('created_at', '>=', now()->subDays((int) $period)->startOfDay());
        }

        $users = $query->get();

        $groups = $users->groupBy(fn (User $u) => $lens === 'asked'
            ? (Acquisition::HEARD_FROM[$u->heard_from] ?? 'Não respondeu')
            : Acquisition::label($u->utm_source, $u->acquisitionChannel?->name));

        $row = function (string $name, Collection $group): array {
            $signups = $group->count();
            $completed = $group->filter(fn (User $u) => $u->profile?->onboarded_at !== null)->count();
            $active = $group->filter(fn (User $u) => $u->shifts_exists || $u->applications_exists)->count();
            $pct = fn (int $n) => $signups > 0 ? round($n / $signups * 100).'% dos cadastros' : '—';

            return [
                'name' => $name,
                'signups' => $signups,
                'completed' => $completed,
                'completed_pct' => $pct($completed),
                'active' => $active,
                'active_pct' => $pct($active),
            ];
        };

        $rows = $groups->map(fn (Collection $group, string $name) => $row($name, $group))
            ->sortByDesc('signups')->values();

        return $rows->push($row('Total', $users))->values();
    }
}
