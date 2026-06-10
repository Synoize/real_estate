<div class="rounded-md border border-gray-200 bg-white p-6 shadow-sm">

    <div class="flex items-center gap-3 mb-6">
        <div class="flex items-center justify-center">
            <i class="fa-solid fa-ruler-combined text-green-600 text-4xl"></i>
        </div>

        <div>
            <h2 class="text-xl font-medium text-gray-900">
                Area Converter
            </h2>
            <p class="text-xs text-gray-500">
                Convert land and property units
            </p>
        </div>
    </div>

    <div class="space-y-4">

        <div>

            <label class="text-sm font-medium text-gray-700">
                Area
            </label>

            <input
                id="areaValue"
                type="number"
                value="1000"
                class="mt-2 h-12 w-full rounded-xl border border-gray-200 px-4">

        </div>

        <div>

            <label class="text-sm font-medium text-gray-700">
                From
            </label>

            <select
                id="fromUnit"
                class="mt-2 h-12 w-full rounded-xl border border-gray-200 px-4">

                <option value="1">Square Feet</option>
                <option value="10.7639">Square Meter</option>
                <option value="9">Square Yard</option>
                <option value="43560">Acre</option>
                <option value="107639">Hectare</option>
                <option value="1089">Guntha</option>
                <option value="14400">Bigha</option>
                <option value="435.6">Cent</option>

            </select>

        </div>

        <div>

            <label class="text-sm font-medium text-gray-700">
                To
            </label>

            <select
                id="toUnit"
                class="mt-2 h-12 w-full rounded-xl border border-gray-200 px-4">

                <option value="1">Square Feet</option>
                <option value="10.7639">Square Meter</option>
                <option value="9">Square Yard</option>
                <option value="43560">Acre</option>
                <option value="107639">Hectare</option>
                <option value="1089">Guntha</option>
                <option value="14400">Bigha</option>
                <option value="435.6">Cent</option>

            </select>

        </div>

    </div>

    <div class="mt-6 rounded-2xl bg-primary p-5 text-white">

        <p class="text-xs text-white/70">
            Converted Area
        </p>

        <h2
            id="areaResult"
            class="mt-2 text-xl font-medium">

            1000

        </h2>

    </div>

</div>

<script>

document.addEventListener("DOMContentLoaded",function(){

    const value=document.getElementById("areaValue");
    const from=document.getElementById("fromUnit");
    const to=document.getElementById("toUnit");
    const result=document.getElementById("areaResult");

    function convert(){

        let area=parseFloat(value.value)||0;

        let sqFeet=
            area*
            parseFloat(from.value);

        let converted=
            sqFeet/
            parseFloat(to.value);

        result.innerHTML=
            converted.toLocaleString(
                "en-IN",
                {
                    maximumFractionDigits:4
                }
            );

    }

    value.addEventListener("input",convert);
    from.addEventListener("change",convert);
    to.addEventListener("change",convert);

    convert();

});

</script>