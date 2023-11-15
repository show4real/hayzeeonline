<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Thanks for Signing Up</title>
  <style>
    body {
      font-family: 'Arial', sans-serif;
      background-color: #f4f4f4;
      text-align: center;
      padding: 20px;
    }

    .container {
      max-width: 600px;
      margin: 0 auto;
      background-color: #ffffff;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }
     .logo {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo img {
            max-width: 150px;
            height: auto;
        }

    h1 {
      color: #333;
    }

    p {
      color: #555;
    }

    .footer {
      margin-top: 20px;
      color: #777;
    }

    .company-info {
      margin-top: 10px;
      font-size: 12px;
    }

    .company-link {
      color: #3498db;
      text-decoration: none;
    }
  </style>
</head>

<body>
  <div class="container">
      <div class="logo">
            <img src="https://hayzeeonline.com/_next/image?url=%2F_next%2Fstatic%2Fmedia%2Flogo5.4f8e477d.png&w=128&q=100" alt="Company Logo">
        </div>
    <?php
    if($referrer->status == 1){
    ?>
   <h1> Thanks for Signing Up!</h1>
   <p>Your account is currently under review. We will notify you once the necessary verification is completed.</p>
    <p>This process may take up to 72 hours. Thank you for your patience.</p>

    <?php
    } else {
    ?>
   <h1> Account Approved Notification</h1>
   <p>Your account has been approved.</p>
    <p>Welcome to Hayzee Computer Resources.<br/> </p>
    <p className="text-sm text-gray-600 mb-4">
              
              <a
                href="https://hayzeeonline-referral.hayzeeonline.com/auth/login"
                className="text-blue-500 underline"
              >
                Sign in here
              </a>
            </p>
    <?php
    }
    ?>
    
    

    <div class="footer">
      <p>This is an automated email. Please do not reply.</p>
      <div class="company-info">
        <p>Visit our website: <a href="http://hayzeeonline.com" class="company-link">hayzeeonline.com</a></p>
        <p>Company: Hayzee Computer Resources</p>
      </div>
    </div>
  </div>
</body>

</html>
