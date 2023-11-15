<!DOCTYPE html>
<html>

<head>
    <title>Email Confirmation</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
        }

        .logo {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo img {
            max-width: 150px;
            height: auto;
        }

        .header {
            background-color: #f0f0f0;
            padding: 10px;
            text-align: center;
        }

        .header h1 {
            color: #333333;
        }

        .content {
            margin-top: 20px;
            color: #333333;
        }

        .btn {
            display: inline-block;
            padding: 16px 36px;
            font-family: 'Source Sans Pro', Helvetica, Arial, sans-serif;
            font-size: 16px;
            color: #ffffff;
            background-color: #4CAF50;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 20px;
        }

        .footer {
            text-align: center;
            padding: 10px;
            background-color: #f0f0f0;
            margin-top: 20px;
        }

        .footer p {
            color: #99AAB5;
            font-family: Whitney, Helvetica Neue, Helvetica, Arial, Lucida Grande, sans-serif;
            font-size: 12px;
            line-height: 24px;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Company logos -->
        <div class="logo">
            <img src="https://hayzeeonline.com/_next/image?url=%2F_next%2Fstatic%2Fmedia%2Flogo5.4f8e477d.png&w=128&q=100" alt="Company Logo">
        </div>

        <!-- Header -->
        <div class="header">
            <h1>Welcome aboard to our Affiliate Market! </h1>
        </div>

        <!-- Content -->
        <div class="content">
            <p style="text-transform:capitalize;">Hello {{$referrer->name}},</p>
            <p>Thank you for signing up. Click the button below to confirm your email address and get started.</p>
            <a href="https://hayzeeonline.com/verify/{{$referrer->referral_code}}" class="btn">Confirm Email Address</a>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Contact us at support@hayzeeonline.com</p>
            <p>Sent by Hayzee Computer Resources • <a href="https://hayzeeonline.com" target="_blank">Visit our Website</a> • <br/>
            <a href="https://twitter.com/hayzeeonline" target="_blank">@hayzeeonline</a></p>
            <p>Spectral Business center, The Polytechnic Ibadan, Oyo State. NG</p>
        </div>
    </div>
</body>

</html>
