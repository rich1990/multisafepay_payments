document.addEventListener("DOMContentLoaded", function() {
    var checkoutBtn = document.getElementById("checkoutBtn");
    var submitPopupBtn = document.getElementById("submitPopupBtn");

    // Initialize Materialize components
    var elems = document.querySelectorAll('.modal');
    var instances = M.Modal.init(elems, { dismissible: false });

    
    checkoutBtn.addEventListener("click", function() {
        // Open the modal
        var instance = M.Modal.getInstance(document.getElementById('modalAddress'));
        instance.open();
    });

    submitPopupBtn.addEventListener("click", function() {
        
        var quantity = document.getElementById('quantity').value;
        var price = document.getElementById('price').value;
        var total = document.getElementById('quantity').value * document.getElementById('price').value;

        var addressLine = document.getElementById('address_line').value;
        var city = document.getElementById('city').value;
        var postalCode = document.getElementById('postal_code').value;
        var firstname = document.getElementById('firstname').value;
        var lastname = document.getElementById('lastname').value;
        var number = document.getElementById('number').value;

        var fields = [
            { id: 'address_line', value: addressLine },
            { id: 'city', value: city },
            { id: 'postal_code', value: postalCode },
            { id: 'firstname', value: firstname },
            { id: 'lastname', value: lastname },
            { id: 'number', value: number },

        ];

        if (!validateFields(fields)) {
            return;
        }

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

        var url = '/process';
        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.payment_url) {
                window.open(data.payment_url, '_blank');
                var instance = M.Modal.getInstance(document.getElementById('modalAddress'));
                instance.close();
            } 
        })
        .catch(error => {
            console.error('Error:', error);
        });
        
    });

    function validateFields(fields) {
        var isValid = true;
    
        fields.forEach(function(field) {
            var element = document.getElementById(field.id);
            var value = field.isSelect ? element.options[element.selectedIndex].value : element.value;
    
            if (!value || (field.isSelect && value === "")) {
                element.classList.add('invalid');
                isValid = false;
            } else {
                element.classList.remove('invalid');
            }
        });
    
        return isValid;
    }
});