<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends Notification
{
    use Queueable;

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        \Log::info('Preparando email de verificación para: '.$notifiable->email);

        $verificationUrl = $this->verificationUrl($notifiable);
        \Log::debug('URL de verificación: '.$verificationUrl);

        try {
            $mail = (new MailMessage)
                ->subject('Verifica tu cuenta')
                ->view('emails.verify-email', [
                    'user' => $notifiable,
                    'url' => $verificationUrl
                ]);

            \Log::info('Email generado correctamente');
            return $mail;
            
        } catch (\Exception $e) {
            \Log::error('Error generando email: '.$e->getMessage());
            throw $e;
        }
    }

    protected function verificationUrl($notifiable)
    {
        return \URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification())
            ]
        );
    }
}


