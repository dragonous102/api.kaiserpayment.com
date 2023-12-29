<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendEmail extends Mailable
{
  use Queueable, SerializesModels;

  public $template;  // Variable to store the template name

  /**
   * Create a new message instance.
   *
   * @param string $template
   * @return void
   */
  public function __construct($template)
  {
    $this->template = $template;
  }

  /**
   * Build the message.
   *
   * @return $this
   */
  public function build(): SendEmail
  {
    // Use a switch statement to set the view based on the template
    switch ($this->template) {
      case 'success':
        return $this->view('emails.success');
      case 'failure':
        return $this->view('emails.failure');
      case 'cancel':
        return $this->view('emails.cancel');
      default:
        return $this->view('emails.default');
    }
  }
}

