# custom-cancel-problem-subscriptions

Adds a menu page to WordPress tools. Allows for a fix of the problem described at https://woocommerce.com/document/subscriptions/faq/#section-43

### The cancelled date must occur after the last payment date.

When you see this error, the customer cancelled their subscription, but somehow, it became out of sync with PayPal and is now stuck in Pending Cancellation (or another) status.

The subscription has most likely already been cancelled in PayPal, but you’ll want to double-check that it is cancelled for this customer in the PayPal dashboard.

To fix this in WooCommerce, the Cancelled Date “_schedule_cancelled”, and End Date “_schedule_end” metadata needs to be deleted from the subscription in the database. Then the subscription can be cancelled from the WC dashboard.

Once the subscription is cancelled, the notification can be dismissed. It will return if the problem happens again.