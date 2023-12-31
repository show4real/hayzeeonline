<!DOCTYPE html>
<html>

<head>
    <title>Hayzeeonline Checkout Orders</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
    /* Styles from the previous template... */
    body,
    p,
    h1,
    h2,
    h3,
    h4,
    h5,
    h6 {
        margin: 0;
        padding: 0;
    }

    body {
        font-family: Arial, sans-serif;
        line-height: 1.6;
    }

    /* Container styles */
    .container {
        max-width: 600px;
        margin: 0 auto;
        padding: 20px;
    }

    /* Header styles */
    .header {
        background-color: #f0f0f0;
        padding: 10px;
        text-align: center;
    }

    .header h1 {
        color: #333333;
    }

    /* Product styles */
    .product {
        border: 1px solid #e0e0e0;
        margin-bottom: 20px;
    }

    .product-img {
        text-align: center;
        padding: 10px;
    }

    .product-img img {
        max-width: 100%;
        height: auto;
    }

    .product-info {
        padding: 10px;
    }

    .product-name {
        font-size: 18px;
        font-weight: bold;
        color: #333333;
        margin-bottom: 10px;
    }

    .product-price {
        font-size: 16px;
        color: #333333;
    }

    /* Total styles */
    .total {
        text-align: right;
        margin-top: 20px;
    }

    .total span {
        font-size: 18px;
        font-weight: bold;
        color: #333333;
    }

    /* Footer styles */
    .footer {
        text-align: center;
        padding: 10px;
        background-color: #f0f0f0;
    }

    /* Company logo */
    .logo {
        text-align: center;
        margin-bottom: 20px;
    }

    .logo img {
        max-width: 150px;
        height: auto;
    }

    /* Order details */
    .order-details {
        margin-bottom: 20px;
        text-align: right;
    }

    .order-details span {
        font-size: 14px;
        color: #666666;
    }

    /* Customer information */
    .customer-info {
        margin-bottom: 20px;
    }

    .customer-info h2 {
        font-size: 18px;
        font-weight: bold;
        color: #333333;
        margin-bottom: 10px;
    }

    .customer-info p {
        font-size: 14px;
        color: #666666;
    }

    /* Description or message */
    .description {
        margin-bottom: 20px;
    }

    .description p {
        font-size: 16px;
        color: #333333;
    }
    </style>
</head>

<body>
    <div class="container">
        <!-- Company logos -->
        <div class="logo">
            <img src="{{ asset('logo/logo5.png') }}" alt="Hayzeeonline">

        </div>

        <!-- Header -->
        <div class="header">
            <h1>Thank You for Your Order!</h1>
        </div>

        <!-- Order details -->
        <div class="order-details">
            <span>Order Date: {{now()}}</span>
        </div>

        <!-- Customer information -->
        <div class="customer-info">
            <h2>Customer Information</h2>
            <p>Name: {{$user->name}}</p>
            <p>Email: {{$user->email}}</p>
            <p>Address: {{$user->address}}</p>
            <p>Phone: {{$user->phone}}</p>
            <p>Order ID: {{$order_id}}</p>

        </div>

        <?php if (count($product_name) > 0) {
            for ($i = 0; $i < count($product_name); $i++) {

        ?>

        <div class="product">
            <div class="product-img">
                <img src="{{$product_image[$i]}}" alt="Product Image">
            </div>
            <div class="product-info">
                <div class="product-name">{{$product_name[$i]}}</div>
                <div class="product-price">X {{$quantity[$i]}}</div>
                <div class="product-price">{{$price[$i]}}</div>


            </div>
        </div>

        <?php
            }
        } ?>




        <!-- Total -->
        <div class="total">
            <span>Total: {{$total_price}}</span>
        </div>

        <!-- Description or message -->
        <div class="description">
            <p>Thank you for shopping with us. </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>08037586863, hayzeeonline.com</p>
        </div>
    </div>
</body>

</html>