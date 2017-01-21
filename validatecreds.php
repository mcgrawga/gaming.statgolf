
        <form action="/charge" method="POST">
	  <input type="hidden" name="email" value="<?php echo($_POST['email']); ?>">
	  <input type="hidden" name="password" value="<?php echo($_POST['password']); ?>">
	  <input type="hidden" name="confirmpassword" value="<?php echo($_POST['confirmpassword']); ?>">
          <script
            src="https://checkout.stripe.com/v2/checkout.js" class="stripe-button"
            data-key="pk_live_g6HOY0G0gWnYLZ9OhyshMXqK"
            data-name="2014 NFL Pick'em Pool"
            data-description="$5.00"
            data-label="Pay with Card ($5)"
            data-amount="500">
          </script>
        </form>

