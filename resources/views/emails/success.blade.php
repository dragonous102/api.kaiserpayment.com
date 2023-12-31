<!DOCTYPE html>
<html lang="en">
<head>
  <meta http-equiv="Content-Type" content="text/html; ">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>KaiserPayment Transaction Alert: Payment Successful</title>
  <link rel="important stylesheet" href="chrome://messagebody/skin/messageBody.css">
  <style>body {
      font-family: 'Arial', sans-serif;
      background-color: #f4f4f4;
      margin: 0;
      padding: 0;
    }

    .content {
      margin-top: 20px;
      line-height: 1.5;
      padding-left: 30px;
      padding-right: 30px;
    }

    .transaction-details {
      font-size: 16px;
    }

    .footer {
      margin-top: 20px;
      color: #555;
      padding-left: 30px;
      padding-right: 30px;
      text-align: right;
      line-height: 1.5;
    }

    .title-center {
      text-align: center;
    }

    .item-label {
      color: darkblue;
    }

    .item-value {
      color: darkslateblue;
      font-size: large;
    }

    p {
      line-height: 1.5;
    }</style>
</head>
<body>
<div class="moz-text-html" lang="x-unicode">
  <div class="container">
    <h1 class="title-center">
      <span style="font-size: large;">KaiserPayment Transaction Alert:</span>
      <br>
      <span style="color: green">Payment Successful</span></h1>
    <hr/>
    <div class="content">
      <p>Dear Valued Customer,</p>

      <p>We are pleased to inform you that your recent transaction with KaiserPayment has been successfully processed.
        Details of the transaction are as follows:</p>

      <div class="transaction-details">
        <ul>
          <li class="item-label">Order Number: <strong class="item-value">{{ $transactionDetails['orderNo'] }}</strong>
          </li>
          <li class="item-label">Date and Time: <strong
              class="item-value">{{ $transactionDetails['created_at'] }}</strong></li>
          <li class="item-label">Amount: <strong class="item-value">{{ $transactionDetails['amount'] }} USD</strong>
          </li>
          <li class="item-label">Payment Method: <strong
              class="item-value">{{ $transactionDetails['paymentMethod'] }}</strong></li>
          <li class="item-label">Description: <strong
              class="item-value">{{ $transactionDetails['product_name'] }}</strong></li>
        </ul>
      </div>

      <p>For your convenience, a detailed invoice of the transaction is attached to this email.</p>

      <p>Should you have any questions or require further assistance, please do not hesitate to contact our customer
        support team.</p>

      <p>Thank you for choosing KaiserPayment. We appreciate your trust in our services and look forward to serving you
        again.</p>
    </div>
    <hr/>
    <div class="footer">
      Warm regards, <br> KaiserPayment Team
    </div>
  </div>
</div>
</body>
</html>
