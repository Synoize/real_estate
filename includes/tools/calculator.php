<!-- SIMPLE SMART CALCULATOR -->

<div class="rounded-md border border-gray-200 bg-white p-6 shadow-sm">

    <div class="flex items-center gap-3 mb-6">
        <div class="flex items-center justify-center">
            <i class="fa-solid fa-calculator text-green-600 text-4xl"></i>
        </div>

        <div>
            <h2 class="text-xl font-medium text-gray-900">
                Smart Calculator
            </h2>
            <p class="text-xs text-gray-500">
                Supports calculations
            </p>
        </div>
    </div>

    <!-- DISPLAY -->

    <input
        type="text"
        id="display"
        readonly
        class="w-full h-16 bg-gray-50
        px-5 text-right text-3xl font-medium outline-none">

    <!-- BUTTONS -->

    <div class="grid grid-cols-4 gap-3 mt-6">

        <button onclick="clearDisplay()" class="h-12 rounded-md bg-red-100 text-red-600 font-bold">AC</button>

        <button onclick="removeLast()" class="h-12 rounded-md bg-gray-100 font-bold">
            <i class="fa-solid fa-delete-left"></i>
        </button>

        <button onclick="append('%')" class="h-12 rounded-md bg-gray-100 font-bold">%</button>

        <button onclick="append('/')" class="h-12 rounded-md bg-green-100 font-bold">÷</button>

        <button onclick="append('7')" class="h-12 rounded-md border">7</button>
        <button onclick="append('8')" class="h-12 rounded-md border">8</button>
        <button onclick="append('9')" class="h-12 rounded-md border">9</button>
        <button onclick="append('*')" class="h-12 rounded-md bg-green-100 font-bold">×</button>

        <button onclick="append('4')" class="h-12 rounded-md border">4</button>
        <button onclick="append('5')" class="h-12 rounded-md border">5</button>
        <button onclick="append('6')" class="h-12 rounded-md border">6</button>
        <button onclick="append('-')" class="h-12 rounded-md bg-green-100 font-bold">−</button>

        <button onclick="append('1')" class="h-12 rounded-md border">1</button>
        <button onclick="append('2')" class="h-12 rounded-md border">2</button>
        <button onclick="append('3')" class="h-12 rounded-md border">3</button>
        <button onclick="append('+')" class="h-12 rounded-md bg-green-100 font-bold">+</button>

        <button onclick="append('(')" class="h-12 rounded-md border">(</button>

        <button onclick="append('0')" class="h-12 rounded-md border">0</button>

        <button onclick="append(')')" class="h-12 rounded-md border">)</button>

        <button onclick="calculate()"
            class="h-12 rounded-md bg-green-600 text-white font-bold">
            =
        </button>

    </div>

</div>

<script>
    const display = document.getElementById("display");

    function append(value) {
        display.value += value;
    }

    function clearDisplay() {
        display.value = "";
    }

    function removeLast() {
        display.value = display.value.slice(0, -1);
    }

    function calculate() {

        try {

            let expression = display.value.replace(/%/g, "/100");

            display.value = eval(expression);

        } catch {

            display.value = "Error";

        }

    }

    // Keyboard Support

    document.addEventListener("keydown", function(e) {

        const key = e.key;

        if (
            "0123456789+-*/().%".includes(key)
        ) {
            append(key);
        }

        if (key === "Enter") {
            e.preventDefault();
            calculate();
        }

        if (key === "Backspace") {
            removeLast();
        }

        if (key === "Escape") {
            clearDisplay();
        }

    });
</script>