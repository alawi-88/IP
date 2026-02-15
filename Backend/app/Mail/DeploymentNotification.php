<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DeploymentNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $status;
    public $environment;
    public $server;
    public $details;
    public $updated_files;
    public $last_commit_message;
    /**
     * Create a new message instance.
     *
     * @param string $status      Success or failed
     * @param string $environment Branch or environment (e.g., staging, production)
     * @param string $server      Server name
     * @param string|null $details Additional info (error, duration, updated files)
     * @param string|null $time      Time of deployment
     * @param string|null $updated_files Updated files
     * @param string|null $last_commit_message Last commit message
     */
    public function __construct(string $status, string $environment, string $server, ?string $details = null, ?string $updated_files = null, ?string $last_commit_message = null)
    {
        $this->status = $status;
        $this->environment = $environment;
        $this->server = $server;
        $this->details = $details;
        $this->updated_files = $updated_files;
        $this->last_commit_message = $last_commit_message;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = $this->status === 'success' ? "Deployment Success on {$this->environment}" : "Deployment Failed on {$this->environment}";

        return $this->subject($subject)
                    ->view('emails.deployment') // HTML view
                    ->with([
                        'status' => $this->status,
                        'environment' => $this->environment,
                        'server' => $this->server,
                        'details' => $this->details,
                        'updated_files' => $this->updated_files,
                        'last_commit_message' => $this->last_commit_message,
                    ]);
    }
}
