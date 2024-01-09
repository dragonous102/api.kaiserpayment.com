<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendEmail extends Mailable
{
  use Queueable, SerializesModels;

  public $template;
  public $transactionDetails;
  public $pdfPath;

  /**
   * Create a new message instance.
   *
   * @param string $template
   * @return void
   */
  public function __construct($template, $transactionDetails = [], $pdfPath = null)
  {
    $this->template = $template;
    $this->transactionDetails = $transactionDetails;
    $this->pdfPath = $pdfPath;
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
        $mail = $this->view('emails.success')
          ->subject('KaiserPayment Transaction Alert: Payment Successful')
          ->bcc(env('MAIL_BCC'), 'alert@kaiserpayment.com')
          ->with('transactionDetails', $this->transactionDetails);

        // Conditionally attach the PDF file
        if ( $this->pdfPath ) {
          $mail->attach($this->pdfPath, [
            'as' => 'payment.pdf', // The name for the attached file
            'mime' => 'application/pdf', // MIME type for the attachment
          ]);
        }
        return $mail;
      case 'failure':
        return $this->view('emails.failure')
          ->subject('KaiserPayment Transaction Alert: Payment Failed')
          ->bcc(env('MAIL_BCC'), 'alert@kaiserpayment.com')
          ->with('transactionDetails', $this->transactionDetails);
      case 'cancel':
        return $this->view('emails.cancel')
          ->subject('KaiserPayment Transaction Alert: Payment Cancelled')
          ->bcc(env('MAIL_BCC'), 'alert@kaiserpayment.com')
          ->with('transactionDetails', $this->transactionDetails);
      default:
        return $this->view('emails.default')->with('transactionDetails', $this->transactionDetails);
    }
  }
}

