<div class="rounded-md border border-gray-200 bg-white p-6 shadow-sm">

    <div class="flex items-center gap-3 mb-6">
        <div class="flex items-center justify-center">
            <i class="fa-solid fa-house-circle-check text-green-600 text-4xl"></i>
        </div>

        <div>
            <h2 class="text-xl font-medium text-gray-900">
                Smart EMI Calculator
            </h2>
            <p class="text-xs text-gray-500">
                 Calculate your monthly home loan EMI
            </p>
        </div>
    </div>

    <div class="grid gap-4">

        <label class="text-sm font-medium text-gray-700">
            Property Price
            <input
                id="propertyPrice"
                type="number"
                value="5000000"
                class="mt-2 h-12 w-full rounded-xl border border-gray-200 px-4">
        </label>

        <label class="text-sm font-medium text-gray-700">
            Down Payment
            <input
                id="downPayment"
                type="number"
                value="500000"
                class="mt-2 h-12 w-full rounded-xl border border-gray-200 px-4">
        </label>

        <label class="text-sm font-medium text-gray-700">
            Interest Rate (%)
            <input
                id="loanRate"
                type="number"
                step="0.1"
                value="8.5"
                class="mt-2 h-12 w-full rounded-xl border border-gray-200 px-4">
        </label>

        <label class="text-sm font-medium text-gray-700">
            Loan Tenure (Years)
            <input
                id="loanYears"
                type="number"
                value="20"
                class="mt-2 h-12 w-full rounded-xl border border-gray-200 px-4">
        </label>

    </div>

    <div class="grid grid-cols-2 gap-4 mt-8">

        <div class="rounded-2xl bg-gray-50 p-4">
            <p class="text-xs text-gray-500">
                Loan Amount
            </p>

            <h4 id="loanAmountResult"
                class="mt-2 text-xl font-medium">
                ₹0
            </h4>
        </div>

        <div class="rounded-2xl bg-primary p-4 text-white">
            <p class="text-xs text-white/70">
                Monthly EMI
            </p>

            <h4 id="emiResult"
                class="mt-2 text-xl font-medium">
                ₹0
            </h4>
        </div>

        <div class="rounded-2xl bg-gray-50 p-4">
            <p class="text-xs text-gray-500">
                Total Interest
            </p>

            <h4 id="interestResult"
                class="mt-2 text-xl font-medium">
                ₹0
            </h4>
        </div>

        <div class="rounded-2xl bg-gray-50 p-4">
            <p class="text-xs text-gray-500">
                Total Payment
            </p>

            <h4 id="paymentResult"
                class="mt-2 text-xl font-medium">
                ₹0
            </h4>
        </div>

    </div>

</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {

        const property = document.getElementById("propertyPrice");
        const down = document.getElementById("downPayment");
        const rate = document.getElementById("loanRate");
        const years = document.getElementById("loanYears");

        const loanAmount = document.getElementById("loanAmountResult");
        const emi = document.getElementById("emiResult");
        const interest = document.getElementById("interestResult");
        const payment = document.getElementById("paymentResult");

        function money(value) {
            return "₹" + Math.round(value).toLocaleString("en-IN");
        }

        function calculate() {

            let price = Number(property.value) || 0;
            let dp = Number(down.value) || 0;

            let principal = price - dp;

            if (principal < 0) {
                principal = 0;
            }

            let r = (Number(rate.value) || 0) / 12 / 100;
            let n = (Number(years.value) || 0) * 12;

            let EMI = 0;

            if (r > 0 && n > 0) {

                EMI =
                    principal * r * Math.pow(1 + r, n) /
                    (Math.pow(1 + r, n) - 1);

            }

            let totalPayment = EMI * n;
            let totalInterest = totalPayment - principal;

            loanAmount.innerHTML = money(principal);
            emi.innerHTML = money(EMI);
            interest.innerHTML = money(totalInterest);
            payment.innerHTML = money(totalPayment);

        }

        [property, down, rate, years].forEach(function(item) {

            item.addEventListener("input", calculate);

        });

        calculate();

    });
</script>