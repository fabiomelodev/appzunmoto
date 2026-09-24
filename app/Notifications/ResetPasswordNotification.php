<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/** Laravel's reset-password e-mail, in Portuguese. */
class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $minutes = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Redefinição de senha — ZunMoto')
            ->greeting('Olá!')
            ->line('Recebemos um pedido para redefinir a senha da sua conta no ZunMoto.')
            ->action('Redefinir senha', $this->resetUrl($notifiable))
            ->line("Este link expira em {$minutes} minutos e só pode ser usado uma vez.")
            ->line('Se você não fez esse pedido, ignore este e-mail: sua senha continua a mesma.')
            ->salutation('Equipe ZunMoto');
    }
}
