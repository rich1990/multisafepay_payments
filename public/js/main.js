document.addEventListener("DOMContentLoaded", function() {
    // Get references to necessary DOM elements
    var checkoutBtn = document.getElementById("checkoutBtn");
    var submitPopupBtn = document.getElementById("submitPopupBtn");

    // Initialize Materialize components for modals
    var elems = document.querySelectorAll('.modal');
    var instances = M.Modal.init(elems, { dismissible: false });

    // Event listener for checkout button click
    checkoutBtn.addEventListener("click", function() {
        // Open the address modal
        var instance = M.Modal.getInstance(document.getElementById('modalAddress'));
        instance.open();
    });

    // Event listener for submit button click in the address modal
    submitPopupBtn.addEventListener("click", function() {
        // Retrieve values from form fields
        var quantity = document.getElementById('quantity').value;
        var price = document.getElementById('price').value;
        var total = document.getElementById('quantity').value * document.getElementById('price').value;

        var addressLine = document.getElementById('address_line').value;
        var city = document.getElementById('city').value;
        var postalCode = document.getElementById('postal_code').value;
        var firstname = document.getElementById('firstname').value;
        var lastname = document.getElementById('lastname').value;
        var number = document.getElementById('number').value;

        // Define an array of field objects for validation
        var fields = [
            { id: 'address_line', value: addressLine },
            { id: 'city', value: city },
            { id: 'postal_code', value: postalCode },
            { id: 'firstname', value: firstname },
            { id: 'lastname', value: lastname },
            { id: 'number', value: number },
        ];

        // Perform form field validation
        if (!validateFields(fields)) {
            return;
        }

        // Prepare form data for submission
        var formData = new FormData();
        formData.append('quantity', quantity);
        formData.append('price', price);
        formData.append('total', total);
        formData.append('address_line', addressLine);
        formData.append('number', number);
        formData.append('city', city);
        formData.append('postal_code', postalCode);
        formData.append('firstname', firstname);
        formData.append('lastname', lastname);

        // Send form data to the server for processing
        var url = '/process';
        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // Handle server response
            if (data.success) {
                // If success, open payment URL in a new tab and close the modal
                window.open(data.payment_url, '_blank');
                var instance = M.Modal.getInstance(document.getElementById('modalAddress'));
                instance.close();
            } else {
                // If errors, display validation error messages
                var errorMessages = data.errors.join(', ');
                M.toast({ html: errorMessages, classes: 'red' });
            }
        })
        .catch(error => {
            // Handle fetch errors
            console.error('Error:', error);
        });
    });

    // Function to validate form fields
    function validateFields(fields) {
        var isValid = true;

        // Iterate through fields and validate each one
        fields.forEach(function(field) {
            var element = document.getElementById(field.id);
            var value = element.value;

            // Check if value is empty
            if (!value) {
                element.classList.add('invalid');
                isValid = false;
            } else {
                element.classList.remove('invalid');
            }
        });

        return isValid;
    }
});
