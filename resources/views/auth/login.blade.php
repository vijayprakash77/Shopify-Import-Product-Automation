<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopify CMS</title>
    @include('auth.header')
</head>
<body>
    @include('auth.session-message')
    <section class="banner_row">
        <div class="row">
            <div class="col-md-12">
                <div class="row header_row">
                    <div class="col-6">
                        <a href="#" class="header_logo">Shopify CMS</a>
                    </div>
                    <div class="col-6 text-end header_right_side">
                        <span>Don’t have an account?</span>
                        <a href="sign-up/index.html">Sign up</a>
                    </div>
                </div>
                <div class="row sign_in_form_row">
                    <div class="col-12">
                
                <div class="row banner_top_row">
                    <h2 class="text-center">Welcome Back</h2>
                    <p class="text-center">Login to your Account</p>
                </div>
    
                <!-- New Row: Social Login Buttons -->
               
    
                <!-- Centered Login Text -->
               
    
                <!-- Login Form -->
                <div class="row form_row">
                    <div class="col-12">
                        <form id="signin-form" method="POST" action="{{route('logged-in-user')}}">
                            @csrf
                            <div class="mb-3">
                                <!-- <label for="email" class="form-label">Email address</label> -->
                                <input type="email" class="form-control" placeholder="Email" id="email" name="email">
                                <span id="emailError" class="text-danger"></span>
                            </div>
                        
                            <div class="mb-3 position-relative">
                                <!-- <label for="password" class="form-label">Password</label> -->
                                <div class="input-group pass_group">
                                    <input type="password" name="password" placeholder="Password" class="form-control" id="password" required>
                                    <span class="input-group-text" onclick="togglePassword()">
                                        <i id="toggleIcon" class="bi bi-eye-slash"></i>
                                    </span>
                                </div>
                                <span id="passwordError" class="text-danger"></span>

                            </div>
                        
                            <div class="mb-3 d-flex justify-content-between align-items-center">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="rememberMe" >
                                    <label class="form-check-label" for="rememberMe">Remember me</label>
                                </div>
                                
                                <a href="#" class="text-decoration-none forget_pass">Forgot Password?</a>
                            </div>
                        
                            <button id="signin-submit" type="submit" class="btn mt-3 btn-primary w-100 submit_btn">Log In</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
            </div>
    
        </div>
    </section>

   @include('auth.footer')
</body>
<script type="text/javascript">

    function togglePassword() {
    let password = document.getElementById("password");
    let toggleIcon = document.getElementById("toggleIcon");
    if (password.type === "password") {
        password.type = "text";
        toggleIcon.classList.remove("bi-eye-slash");
        toggleIcon.classList.add("bi-eye");
    } else {
        password.type = "password";
        toggleIcon.classList.remove("bi-eye");
        toggleIcon.classList.add("bi-eye-slash");
    }
}
			$(document).ready(function () {
		    $('#signin-submit').on('click', function (e) {
		    e.preventDefault();

		    let isValid = true;

		    // Clear previous error messages
		    $('#emailError').text('');
		    $('#passwordError').text('');

		    // Validate email
		    const email = $('#email').val().trim();
		    if (!email) {
		        $('#emailError').text('Email is required.');
		        isValid = false;
		    } else if (!/^\S+@\S+\.\S+$/.test(email)) {
		        $('#emailError').text('Enter a valid email address.');
		        isValid = false;
		    }

		    // Validate password
		    const password = $('#password').val().trim();
		    if (!password) {
		        $('#passwordError').text('Password is required.');
		        isValid = false;
		    } else if (password.length < 8 || password.length > 15) {
		        $('#passwordError').text('Password must be between 8-15 characters.');
		        isValid = false;
		    }

		    if (isValid) {
		          $('#signin-form').off('submit').submit(); // Remove the submit handler to allow submission
		        }
		});
		    });
		</script>
</html>