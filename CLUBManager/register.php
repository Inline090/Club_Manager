<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
                /* Style for the membership plan label */
                .membership-label {
            display: block; /* Ensure it takes its own line */
            text-align: left; /* Align text to the left */
            margin-bottom: 6px; /* Small space between label and select box */
            font-size: 0.95rem; /* Slightly smaller than input text */
            font-weight: 500; /* Medium weight - adjust if needed (400=normal, 600=semi-bold) */
            color: #333; /* Dark gray - looks softer than pure black */
            font-family: Georgia, 'Times New Roman', Times, serif; /* Match button font for consistency */
            line-height: 1.4; /* Adjust line height */
        }

        /* Ensure select still gets default styles */
        input, select {
            margin-bottom: 15px; /* This rule is fine */
            padding: 10px;
            width: 100%;
            border-radius: 10px;
            border: 1px solid #ccc;
            font-size: 1rem;
            background-color: white;
             /* Add the select arrow styling back if it was removed */
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%236c757d%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E'); /* Gray arrow */
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 10px auto;
            padding-right: 30px;
        }
        
        body {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-image: url('images/bgimage.jpg'); /* Replace with your image filename */
            background-size: 110% auto; /* Slightly stretch width */
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
        }
        .portal-title {
            font-family: 'Times New Roman', Times, serif;
            font-size: 2rem;
            position: absolute;
            top: 20px;
            left: 20px;
            text-align: left;
            padding: 10px 20px;
            border-radius: 10px; /* Curved edges */
            border: 3px solid black; /* Solid black border */
            background: transparent; /* Fully transparent background */
        }
        .container-box {
            background: rgba(255, 255, 255, 0.05); /* Almost transparent white */
            padding: 50px;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 350px;
            backdrop-filter: blur(10px); /* Glass effect */
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .login-title {
            font-family: 'Times New Roman', Times, serif;
            font-size: 2rem;
            margin-bottom: 30px;
        }
        .btn-custom {
            font-size: 1.2rem;
            padding: 12px 20px;
            font-family: Georgia, 'Times New Roman', Times, serif;
            border-radius: 10px;
            width: 100%;
            background-color: #d3d3d3;
            border: 1px solid black;
            margin-bottom: 20px; /* Keep margin below button */
            margin-top: 10px; /* Add some space above button */
        }
        /* Apply consistent styles to input and select */
        input, select {
            margin-bottom: 15px;
            padding: 10px;
            width: 100%;
            border-radius: 10px;
            border: 1px solid #ccc;
            font-size: 1rem;
            background-color: white; /* Ensure select has white background */
            /* Add other specific input styles here if needed */
        }
         /* Ensure select dropdown arrow is visible */
        select {
            -webkit-appearance: none; /* Remove default */
            -moz-appearance: none;
            appearance: none;
            /* Add custom arrow - Example using background image */
            background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23007bff%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E');
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 10px auto;
            padding-right: 30px; /* Make space for arrow */
        }
        h2 {
            font-family: 'Times New Roman', Times, serif;
            font-size: 2rem;
            margin-bottom: 30px;
        }
         /* Styles for messages (using Bootstrap alerts) */
        #message-area .alert {
            text-align: left;
            font-size: 0.9rem;
            margin-bottom: 15px; /* Space below message */
        }
         /* Optional: Style for label */
         label.form-label {
            display: block;
            text-align: left;
            margin-bottom: .25rem;
            font-size: 0.9rem;
            color: #444; /* Adjust as needed */
         }
    </style>
</head>
<body> <!-- Removed bg-gray-100 if you are not using Tailwind elsewhere -->
    <h1 class="portal-title">Manipal Club Portal</h1>
    <div class="container-box">
        <h2 class="login-title">Member Registration</h2>

        <!-- Area to display messages -->
        <div id="message-area"></div>

        <!-- Ensure action points to the PHP script -->
        <form action="backend/register_process.php" method="post">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email" required>

            <!-- Password Fields -->
            <input type="password" name="password" placeholder="Password" required>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required>

            <input type="text" name="phone" placeholder="Phone Number" required>

            <!-- **** MODIFIED: Membership Type Dropdown **** -->
                        <!-- **** MODIFIED: Membership Type Dropdown **** -->
                        <div style="margin-bottom: 15px;"> <!-- Use inline style matching input margin -->
                 <label for="membership_type" class="membership-label">Membership Plan</label> <!-- Removed asterisk, added custom class -->
                 <select id="membership_type" name="membership_type" required>
                     <option value="" selected disabled>-- Select Plan --</option>
                     <option value="Monthly">Monthly</option>
                     <option value="Quarterly">Quarterly (3 Months)</option>
                     <option value="Half Yearly">Half Yearly (6 Months)</option>
                     <option value="Yearly">Yearly (12 Months)</option>
                 </select>
            </div>
            <!-- **** END MODIFIED **** -->
            <!-- **** END MODIFIED **** -->

            <!-- **** Membership Expiry Input Field REMOVED **** -->

            <button type="submit" class="btn btn-custom">Register</button>
        </form>
        <p class="mt-3">
            Already registered? <a href="student.html">Login</a>
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- JavaScript to display messages based on URL parameters -->
    <script>
        function getQueryParam(param) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(param);
        }

        document.addEventListener('DOMContentLoaded', () => {
            const messageArea = document.getElementById('message-area');
            const errorStatus = getQueryParam('error');

            messageArea.innerHTML = ''; // Clear previous messages

            const errorMessages = {
                'db_connection': 'Database connection error. Please try again later.',
                'missing_fields': 'Please fill in all required fields.',
                'invalid_email': 'Please enter a valid email address.',
                'password_mismatch': 'Passwords do not match.',
                'hashing_error': 'Could not process password. Please try again.',
                'email_exists': 'This email address is already registered.',
                'email_check_failed': 'Error checking email. Please try again.',
                'registration_failed': 'Could not register member. Please try again later.',
                'prepare_failed': 'Error preparing registration. Please contact support.',
                'invalid_type': 'Please select a valid membership plan.' // Added error message
            };

            if (errorStatus && errorMessages[errorStatus]) {
                 // Using Bootstrap alert classes for styling
                 messageArea.innerHTML = `<div class="alert alert-danger" role="alert">${errorMessages[errorStatus]}</div>`;
            } else if (errorStatus) { // Fallback
                 messageArea.innerHTML = `<div class="alert alert-danger" role="alert">An unknown error occurred (${errorStatus}).</div>`;
            }
            // Success message shown on login page after redirect
        });
    </script>
</body>
</html>