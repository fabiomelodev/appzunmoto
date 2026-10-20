<?php

namespace App\Filament\Resources\Users\Actions;

use App\Models\User;
use App\Support\Impersonation;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/** "Acessar como": opens the app logged in as the chosen user. */
class ImpersonateAction
{
    public static function make(): Action
    {
        return Action::make('impersonate')
            ->label('Acessar como este usuário')
            ->tooltip('Acessar o app como este usuário')
            ->icon(Heroicon::OutlinedArrowRightEndOnRectangle)
            ->color('warning')
            ->visible(fn (User $record) => Impersonation::canImpersonate(auth()->user(), $record))
            ->requiresConfirmation()
            ->modalHeading(fn (User $record) => 'Acessar como '.$record->name.'?')
            ->modalDescription('Você será levado ao app já logado nesta conta (funciona também para contas criadas com o Google). Tudo que fizer vale como se fosse o usuário, e o acesso fica registrado. Use "Voltar ao painel" na faixa do topo para sair.')
            ->modalSubmitActionLabel('Acessar')
            ->action(function (User $record) {
                if (! Impersonation::start(auth()->user(), $record)) {
                    Notification::make()->title('Não foi possível acessar esta conta.')->danger()->send();

                    return null;
                }

                return redirect()->route($record->profile?->isOnboarded() ? 'shifts.index' : 'onboarding');
            });
    }
}
