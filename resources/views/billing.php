
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Store Billing - New Order</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <style>
        body {
            background: #f5f6fa;
            font-family: Arial, sans-serif;
            color: #28324b;
        }

        .billing-card {
            max-width: 1000px;
            margin: 35px auto;
            background: white;
            padding: 28px;
            border-radius: 8px;
            box-shadow: 0 3px 15px #00000012;
        }

        .topbar {
            background: #202641;
            color: white;
            padding: 15px 20px;
            font-weight: bold;
            margin-bottom: 25px;
        }

        .section-title {
            font-weight: bold;
            font-size: 15px;
            margin: 18px 0 10px;
        }

        .form-control,
        .form-select {
            font-size: 13px;
        }

        .table thead th {
            background: #e6eaf5;
            font-size: 12px;
        }

        .table td {
            vertical-align: middle;
            font-size: 13px;
        }

        .low-stock {
            background: #fffde8;
            border: 1px solid #e7a000;
            border-radius: 5px;
            padding: 15px;
            font-size: 13px;
        }

        .payment-box {
            background: #f4f6fb;
            border: 1px solid #aab6d1;
            border-radius: 5px;
            padding: 14px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            font-size: 13px;
        }

        .grand-total {
            font-weight: bold;
            border-top: 1px dashed #999;
            padding-top: 10px;
        }

        .balance {
            font-weight: bold;
            margin-top: 12px;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: white;
            }

            .billing-card {
                box-shadow: none;
                margin: 0;
            }
        }
    </style>
</head>

<body>

<div class="billing-card">

    <div class="topbar">
        Store Billing — New Order
    </div>

    <div class="row g-4">

        <div class="col-md-8">

            <!-- Customer Details -->
            <div class="section-title">Customer</div>

            <div class="row">

                <div class="col-md-6">
                    <label class="form-label small">Email</label>

                    <input type="email"
                           id="email"
                           class="form-control"
                           placeholder="e.g. thomas@example.com">
                </div>

                <div class="col-md-6">
                    <label class="form-label small">Name</label>

                    <input type="text"
                           id="customerName"
                           class="form-control"
                           placeholder="Customer name">
                </div>

            </div>

            <!-- Products -->
            <div class="section-title">Products</div>

            <div class="table-responsive">

                <table class="table table-bordered" id="productTable">

                    <thead>
                    <tr>
                        <th style="width:40%">Product</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Line Total</th>
                        <th class="no-print"></th>
                    </tr>
                    </thead>

                    <tbody id="productRows"></tbody>

                </table>

            </div>

            <button type="button"
                    class="btn btn-primary btn-sm no-print"
                    onclick="addProductRow()">
                + Add Product
            </button>

            <!-- Payment -->
            <div class="section-title">Payment</div>

            <div class="payment-box">

                <div class="total-row">
                    <span>Subtotal</span>
                    <strong id="subtotal">Rs. 0.00</strong>
                </div>

                <div class="total-row">
                    <span>Tax</span>
                    <strong id="tax">Rs. 0.00</strong>
                </div>

                <div class="total-row grand-total">
                    <span>Grand Total</span>
                    <strong id="grandTotal">Rs. 0.00</strong>
                </div>

                <hr>

                <label class="form-label small fw-bold">
                    Amount Given by Customer
                </label>

                <input type="number"
                       id="amountGiven"
                       class="form-control"
                       min="0"
                       step="0.01"
                       placeholder="Enter amount received"
                       oninput="calculateTotals()">

                <div class="balance">
                    Balance to Return:
                    <span id="balance">Rs. 0.00</span>
                </div>

            </div>

            <!-- Buttons -->
            <div class="mt-3 no-print">

                <button type="button"
                        class="btn btn-success"
                        id="generateButton"
                        onclick="generateBill()">
                    Generate Bill
                </button>

                <button type="button"
                        class="btn btn-outline-secondary btn-sm"
                        onclick="window.print()">
                    Print Bill
                </button>

            </div>

            <div id="message" class="mt-3"></div>

        </div>

        <!-- Low Stock -->
        <div class="col-md-4">

            <div class="low-stock">

                <strong>⚠ Low Stock Alert</strong>

                <ul id="lowStockList" class="mt-2 mb-0"></ul>

            </div>

        </div>

    </div>

</div>


<script>

// Safely pass Laravel data into JavaScript
const products = {!! json_encode($products, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!};

const customers = {!! json_encode($customers, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!};


// Currency formatter
const money = function(value) {
    return 'Rs. ' + Number(value).toFixed(2);
};


// Add Product Row
function addProductRow() {

    const tbody = document.getElementById('productRows');

    const row = document.createElement('tr');

    const productOptions = products.map(function(p) {

        return `<option value="${p.id}">
                    ${p.name} (${p.code})
                </option>`;

    }).join('');

    row.innerHTML = `
        <td>
            <select class="form-select product-select"
                    onchange="calculateTotals()">

                <option value="">Select product</option>

                ${productOptions}

            </select>
        </td>

        <td>
            <input type="number"
                   class="form-control quantity"
                   value="1"
                   min="1"
                   oninput="calculateTotals()">
        </td>

        <td class="price">Rs. 0.00</td>

        <td class="line-total">Rs. 0.00</td>

        <td class="no-print">
            <button type="button"
                    class="btn btn-sm btn-danger"
                    onclick="this.closest('tr').remove(); calculateTotals()">
                ×
            </button>
        </td>
    `;

    tbody.appendChild(row);

    calculateTotals();
}


// Calculate Subtotal, Tax and Grand Total
function calculateTotals() {

    let subtotal = 0;

    let tax = 0;

    document.querySelectorAll('#productRows tr').forEach(function(row) {

        const id = row.querySelector('.product-select').value;

        const quantity = Number(
            row.querySelector('.quantity').value
        );

        const product = products.find(function(p) {
            return p.id == id;
        });

        if (!product || quantity < 1 || !Number.isFinite(quantity)) {

            row.querySelector('.price').textContent = money(0);

            row.querySelector('.line-total').textContent = money(0);

            return;
        }

        const lineSubtotal = Number(product.price) * quantity;

        const lineTax = lineSubtotal *
                        Number(product.tax_percentage) / 100;

        subtotal += lineSubtotal;

        tax += lineTax;

        row.querySelector('.price').textContent =
            money(product.price);

        row.querySelector('.line-total').textContent =
            money(lineSubtotal + lineTax);

    });

    const grandTotal = subtotal + tax;

    const amount = Number(
        document.getElementById('amountGiven').value
    ) || 0;

    document.getElementById('subtotal').textContent =
        money(subtotal);

    document.getElementById('tax').textContent =
        money(tax);

    document.getElementById('grandTotal').textContent =
        money(grandTotal);

    document.getElementById('balance').textContent =
        money(Math.max(0, amount - grandTotal));
}


// Low Stock Alert
function showLowStock() {

    const lowStock = products.filter(function(p) {
        return Number(p.stock) <= 5;
    });

    const list = document.getElementById('lowStockList');

    if (lowStock.length > 0) {

        list.innerHTML = lowStock.map(function(p) {

            return `<li>${p.name} — ${p.stock} units left</li>`;

        }).join('');

    } else {

        list.innerHTML = '<li>No low-stock products</li>';

    }
}


// Customer Lookup by Email
document.getElementById('email').addEventListener('input', function() {

    const emailValue = this.value.trim().toLowerCase();

    const customer = customers.find(function(c) {

        return c.email.toLowerCase() === emailValue;

    });

    if (customer) {

        document.getElementById('customerName').value =
            customer.name;

    }

});


// Generate Bill
async function generateBill() {

    const email = document.getElementById('email').value.trim();

    const name = document.getElementById('customerName').value.trim();

    const rows = [
        ...document.querySelectorAll('#productRows tr')
    ];

    const selectedProducts = rows.map(function(row) {

        return {
            product_id: Number(
                row.querySelector('.product-select').value
            ),

            quantity: Number(
                row.querySelector('.quantity').value
            )
        };

    });

    if (!email || !name || !rows.length ||
        selectedProducts.some(function(p) {
            return !p.product_id ||
                   !Number.isInteger(p.quantity) ||
                   p.quantity < 1;
        })) {

        alert('Please enter customer details and select valid products.');

        return;
    }

    const button = document.getElementById('generateButton');

    button.disabled = true;

    try {

        const response = await fetch('/api/orders', {

            method: 'POST',

            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },

            body: JSON.stringify({

                customer: {
                    name: name,
                    email: email
                },

                products: selectedProducts

            })

        });

        const result = await response.json();

        if (!response.ok) {

            throw new Error(
                result.message ||
                JSON.stringify(result.errors || result)
            );

        }

        document.getElementById('message').innerHTML =

            `<div class="alert alert-success">
                Order #${result.order.id} created successfully.
                <br>
                Grand Total: ${money(result.order.grand_total)}
            </div>`;

        alert(
            'Bill generated successfully! Order ID: ' +
            result.order.id
        );

        // Update available stock on the page
        result.order.items.forEach(function(item) {

            const product = products.find(function(p) {
                return p.id == item.product_id;
            });

            if (product) {

                product.stock =
                    Number(product.stock) -
                    Number(item.quantity);

            }

        });

        showLowStock();

    } catch (error) {

        document.getElementById('message').innerHTML =

            `<div class="alert alert-danger">
                ${error.message}
            </div>`;

    } finally {

        button.disabled = false;

    }

}


// Initial page setup
addProductRow();

showLowStock();

</script>

</body>
</html>
